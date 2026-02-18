<?php

namespace App\Controller;

use App\Entity\Job;
use App\Entity\Order;
use App\Entity\Product;
use App\Repository\JobRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/marketplace')]
class MarketplaceController extends AbstractController
{
    #[Route('/', name: 'app_marketplace_index', methods: ['GET'])]
    public function index(
        ProductRepository $productRepository,
        JobRepository $jobRepository,
        OrderRepository $orderRepository,
        PaginatorInterface $paginator,
        Request $request,
    ): Response {
        // Get search parameters
        $search = $request->query->get('q', '');
        $category = $request->query->get('category', '');
        $minPrice = $request->query->get('minPrice', '');
        $maxPrice = $request->query->get('maxPrice', '');
        $sortBy = $request->query->get('sortBy', 'newest');

        // Build products query
        $queryBuilder = $productRepository->createQueryBuilder('p')
            ->leftJoin('p.freelancer', 'f')
            ->leftJoin('p.category', 'c')
            ->where('p.deletedAt IS NULL');

        // Apply filters
        if (!empty($search)) {
            $queryBuilder->andWhere('p.title LIKE :search OR p.description LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        if (!empty($category)) {
            $queryBuilder->andWhere('c.name = :category')
                ->setParameter('category', $category);
        }

        if (!empty($minPrice)) {
            $queryBuilder->andWhere('p.price >= :minPrice')
                ->setParameter('minPrice', $minPrice);
        }

        if (!empty($maxPrice)) {
            $queryBuilder->andWhere('p.price <= :maxPrice')
                ->setParameter('maxPrice', $maxPrice);
        }

        // Apply sorting
        switch ($sortBy) {
            case 'az':
                $queryBuilder->orderBy('p.title', 'ASC');
                break;
            case 'za':
                $queryBuilder->orderBy('p.title', 'DESC');
                break;
            case 'price_asc':
                $queryBuilder->orderBy('p.price', 'ASC');
                break;
            case 'price_desc':
                $queryBuilder->orderBy('p.price', 'DESC');
                break;
            case 'newest':
            default:
                $queryBuilder->orderBy('p.createdAt', 'DESC');
                break;
        }

        $query = $queryBuilder->getQuery();
        $products = $paginator->paginate($query, $request->query->getInt('page', 1), 12);

        // Fetch job requests
        $jobs = $jobRepository->createQueryBuilder('j')
            ->leftJoin('j.client', 'c')
            ->where('j.deletedAt IS NULL')
            ->orderBy('j.createdAt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        // Fetch orders for current user if logged in
        $orders = [];
        if ($this->getUser()) {
            $orders = $orderRepository->createQueryBuilder('o')
                ->leftJoin('o.product', 'p')
                ->leftJoin('o.buyer', 'b')
                ->where('o.buyer = :user')
                ->setParameter('user', $this->getUser())
                ->orderBy('o.createdAt', 'DESC')
                ->setMaxResults(10)
                ->getQuery()
                ->getResult();
        }

        // Get categories for filter dropdown
        $categories = $productRepository->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->select('DISTINCT c.name')
            ->where('c.name IS NOT NULL')
            ->getQuery()
            ->getSingleColumnResult();

        // Calculate stats
        $stats = [
            'students' => count($this->getUser()?->getStudent() ? [$this->getUser()->getStudent()] : []),
            'products' => $productRepository->count(['deletedAt' => null]),
            'jobs' => $jobRepository->count(['deletedAt' => null]),
            'revenue' => $orderRepository->createQueryBuilder('o')
                ->select('SUM(o.totalPrice)')
                ->where('o.status = :status')
                ->setParameter('status', 'paid')
                ->getQuery()
                ->getSingleScalarResult() ?? 0,
        ];

        return $this->render('marketplace/index.html.twig', [
            'products' => $products,
            'jobs' => $jobs,
            'orders' => $orders,
            'categories' => $categories,
            'stats' => $stats,
        ]);
    }

    #[Route('/shop', name: 'app_marketplace_shop', methods: ['GET'])]
    public function shop(
        ProductRepository $productRepository,
        JobRepository $jobRepository,
        Request $request,
    ): Response {
        // Fetch all products
        $products = $productRepository->createQueryBuilder('p')
            ->leftJoin('p.freelancer', 'f')
            ->leftJoin('p.category', 'c')
            ->where('p.deletedAt IS NULL')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        // Fetch all job requests
        $jobs = $jobRepository->createQueryBuilder('j')
            ->leftJoin('j.client', 'c')
            ->where('j.deletedAt IS NULL')
            ->orderBy('j.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('marketplace/shop.html.twig', [
            'products' => $products,
            'jobs' => $jobs,
        ]);
    }

    #[Route('/product/{slug}/order', name: 'app_order_new_from_product', methods: ['GET', 'POST'])]
    public function orderFromProduct(Product $product, EntityManagerInterface $entityManager): Response
    {
        if ($product->getDeletedAt()) {
            throw $this->createNotFoundException('Product not found');
        }

        $order = new Order();
        $order->setProduct($product);

        // Handle user assignment - if no user is logged in, use a default user
        $user = $this->getUser();
        if (!$user) {
            // Find or create a default user for demo purposes
            $user = $entityManager->getRepository(\App\Entity\User::class)->findOneBy(['email' => 'demo@unilearn.com']);
            if (!$user) {
                // Create a demo user if none exists
                $user = new \App\Entity\User();
                $user->setEmail('demo@unilearn.com');
                $user->setName('Demo User');
                $user->setPassword('demo');
                // Set role as entity
                $userRole = $entityManager->getRepository(\App\Entity\Role::class)->findOneBy(['name' => 'user']);
                $user->setRole($userRole);
                $entityManager->persist($user);
                $entityManager->flush();
            }
        }

        $order->setBuyer($user instanceof \App\Entity\User ? $user : null);
        $order->setTotalPrice($product->getPrice());
        $order->setStatus('pending');

        $entityManager->persist($order);
        $entityManager->flush();

        $this->addFlash('success', 'Order created successfully!');

        return $this->redirectToRoute('app_marketplace_index');
    }

    #[Route('/dashboard', name: 'app_marketplace_dashboard')]
    public function dashboard(
        ProductRepository $productRepository,
        JobRepository $jobRepository,
        OrderRepository $orderRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        // Get the currently logged-in user
        $user = $this->getUser();

        if (!$user) {
            // If no user is logged in, redirect to login
            return $this->redirectToRoute('app_login');
        }

        // Get user-specific data
        $userProducts = $productRepository->findBy(['freelancer' => $user, 'deletedAt' => null]);
        $userJobs = $jobRepository->findBy(['client' => $user]);
        $userOrders = $orderRepository->findBy(['buyer' => $user]);

        // Calculate statistics
        $stats = [
            'totalProducts' => count($userProducts),
            'totalJobs' => count($userJobs),
            'totalOrders' => count($userOrders),
            'totalRevenue' => array_sum(array_map(fn ($order): float => $order->getTotalPrice(), $userOrders)),
            'activeProducts' => count(array_filter($userProducts, fn ($product): bool => null === $product->getDeletedAt())),
            'pendingOrders' => count(array_filter($userOrders, fn ($order): bool => 'pending' === $order->getStatus())),
        ];

        return $this->render('marketplace/dashboard.html.twig', [
            'user' => $user,
            'stats' => $stats,
            'products' => array_slice($userProducts, 0, 5),
            'jobs' => array_slice($userJobs, 0, 5),
            'orders' => array_slice($userOrders, 0, 5),
        ]);
    }

    #[Route('/job/{id}/order', name: 'app_order_new_from_job', methods: ['GET', 'POST'])]
    public function orderFromJob(Job $job, EntityManagerInterface $entityManager): Response
    {
        if ($job->getDeletedAt()) {
            throw $this->createNotFoundException('Job not found');
        }

        // Create a product from the job for ordering
        $product = new Product();
        $product->setTitle($job->getTitle());
        $product->setDescription($job->getDescription());
        $product->setPrice($job->getBudget());
        $product->setCreatedAt(new \DateTimeImmutable());

        // Handle user assignment - if no user is logged in, use a default user
        $user = $this->getUser();
        if (!$user) {
            // Find or create a default user for demo purposes
            $user = $entityManager->getRepository(\App\Entity\User::class)->findOneBy(['email' => 'demo@unilearn.com']);
            if (!$user) {
                // Create a demo user if none exists
                $user = new \App\Entity\User();
                $user->setEmail('demo@unilearn.com');
                $user->setName('Demo User');
                $user->setPassword('demo');
                // Set role as entity
                $userRole = $entityManager->getRepository(\App\Entity\Role::class)->findOneBy(['name' => 'user']);
                $user->setRole($userRole);
                $entityManager->persist($user);
                $entityManager->flush();
            }
        }

        $product->setFreelancer($user instanceof \App\Entity\User ? $user : null);
        $entityManager->persist($product);

        $order = new Order();
        $order->setProduct($product);
        $order->setBuyer($user instanceof \App\Entity\User ? $user : null);
        $order->setTotalPrice($job->getBudget());
        $order->setStatus('pending');

        $entityManager->persist($order);
        $entityManager->flush();

        $this->addFlash('success', 'Order created successfully!');

        return $this->redirectToRoute('app_marketplace_index');
    }
}
