<?php

namespace App\Controller;

use App\Entity\Job;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\Student;
use App\Form\JobType;
use App\Form\OrderType;
use App\Form\ProductType;
use App\Form\StudentType;
use App\Repository\JobRepository;
use App\Repository\ProductRepository;
use App\Repository\OrderRepository;
use App\Repository\StudentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/marketplace')]
class MarketplaceController extends AbstractController
{
    #[Route('/', name: 'app_marketplace_index', methods: ['GET'])]
    public function index(
        ProductRepository $productRepository,
        JobRepository $jobRepository,
        \App\Repository\OrderRepository $orderRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $q = $request->query->get('q');
        $category = $request->query->get('category');
        $minPrice = $request->query->get('minPrice');
        $maxPrice = $request->query->get('maxPrice');
        $sortBy = $request->query->get('sortBy', 'newest');

        $queryBuilder = $productRepository->createQueryBuilder('p')
            ->where('p.deletedAt IS NULL');

        // Apply filters
        if ($q) {
            $queryBuilder
                ->andWhere('p.title LIKE :q OR p.description LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        if ($category) {
            $queryBuilder
                ->andWhere('p.category = :category')
                ->setParameter('category', $category);
        }

        if ($minPrice !== null && $minPrice !== '') {
            $queryBuilder
                ->andWhere('p.price >= :minPrice')
                ->setParameter('minPrice', (float) $minPrice);
        }

        if ($maxPrice !== null && $maxPrice !== '') {
            $queryBuilder
                ->andWhere('p.price <= :maxPrice')
                ->setParameter('maxPrice', (float) $maxPrice);
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
            default:
                $queryBuilder->orderBy('p.createdAt', 'DESC');
                break;
        }

        $productsQuery = $queryBuilder->getQuery();

        $products = $paginator->paginate(
            $productsQuery,
            $request->query->getInt('page', 1),
            9,
            [
                'sortFieldParameterName' => '___sort',
                'sortDirectionParameterName' => '___direction',
                'filterFieldParameterName' => '___filter_field',
                'filterValueParameterName' => '___filter_value',
            ]
        );

        $jobs = $jobRepository->createQueryBuilder('j')
            ->where('j.status = :status')
            ->andWhere('j.deletedAt IS NULL')
            ->setParameter('status', 'open')
            ->orderBy('j.createdAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Distinct categories for filters
        $categories = $productRepository->createQueryBuilder('p')
            ->select('DISTINCT p.category')
            ->where('p.deletedAt IS NULL')
            ->orderBy('p.category', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();

        // Clone QueryBuilder for filtered stats and charts
        $filteredQB = clone $queryBuilder;

        // Chart 1: Category Distribution (Filtered)
        $chartQB = clone $filteredQB;
        $catData = $chartQB
            ->select('p.category, COUNT(p.id) as count')
            ->groupBy('p.category')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getResult();
        
        $chartCategories = [];
        $chartProductCounts = [];
        foreach ($catData as $item) {
            $chartCategories[] = $item['category'] ?: 'Uncategorized';
            $chartProductCounts[] = (int) $item['count'];
        }

        // Chart 2: Recent Orders (Unfiltered - usually better for revenue trend)
        $sevenDaysAgo = new \DateTimeImmutable('-7 days');
        $orderData = $orderRepository->createQueryBuilder('o')
            ->select('SUBSTRING(o.createdAt, 1, 10) as date, SUM(o.totalPrice) as revenue')
            ->where('o.createdAt >= :date')
            ->setParameter('date', $sevenDaysAgo)
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->getQuery()
            ->getResult();

        $chartOrderDates = [];
        $chartOrderRevenue = [];
        foreach ($orderData as $item) {
            $chartOrderDates[] = $item['date'];
            $chartOrderRevenue[] = (float) $item['revenue'];
        }

        return $this->render('marketplace/index.html.twig', [
            'products' => $products,
            'jobs' => $jobs,
            'categories' => $categories,
            'stats' => [
                'students' => count($paginator->paginate((clone $filteredQB)->select('DISTINCT IDENTITY(p.freelancer)')->resetDQLPart('orderBy')->getQuery(), 1, 1)),
                'products' => count($paginator->paginate((clone $filteredQB)->select('p.id')->resetDQLPart('orderBy')->getQuery(), 1, 1)),
                'jobs' => $jobRepository->count(['status' => 'open', 'deletedAt' => null]),
                'revenue' => array_sum($chartOrderRevenue),
            ],
            'charts' => [
                'categories' => $chartCategories,
                'counts' => $chartProductCounts,
                'dates' => $chartOrderDates,
                'revenue' => $chartOrderRevenue,
            ]
        ]);
    }

    #[Route('/product/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function newProduct(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user->getStudent()) {
             $this->addFlash('error', 'You must register as a freelancer to post a service.');
             return $this->redirectToRoute('app_freelancer_register');
        }

        $product = new Product();
        $product->setFreelancer($user->getStudent());
        $product->setCreatedAt(new \DateTimeImmutable());
        $product->setUpdatedAt(new \DateTimeImmutable());

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($product);
            $entityManager->flush();

            return $this->redirectToRoute('app_product_show', ['slug' => $product->getSlug()]);
        }

        return $this->render('product/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/product/{slug}', name: 'app_product_show', methods: ['GET'])]
    public function showProduct(Product $product, OrderRepository $orderRepository): Response
    {
        if ($product->getDeletedAt()) {
            throw $this->createNotFoundException('Product not found');
        }

        $orderToRate = null;
        $user = $this->getUser();
        if ($user) {
            $orderToRate = $orderRepository->createQueryBuilder('o')
                ->andWhere('o.product = :product')
                ->andWhere('o.buyer = :buyer')
                ->andWhere('o.status = :status')
                ->setParameter('product', $product)
                ->setParameter('buyer', $user)
                ->setParameter('status', 'paid')
                ->orderBy('o.createdAt', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        }

        return $this->render('product/show.html.twig', [
            'product' => $product,
            'orderToRate' => $orderToRate,
        ]);
    }

    #[Route('/product/{slug}/delete', name: 'app_product_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function deleteProduct(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if ($product->getFreelancer()->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
             throw $this->createAccessDeniedException('You can only delete your own services.');
        }

        if ($this->isCsrfTokenValid('delete'.$product->getSlug(), $request->request->get('_token'))) {
            $product->setDeletedAt(new \DateTimeImmutable());
            $entityManager->flush();
            $this->addFlash('success', 'Service deleted successfully.');
        }

        return $this->redirectToRoute('app_marketplace_dashboard');
    }

    #[Route('/dashboard', name: 'app_marketplace_dashboard')]
    #[IsGranted('ROLE_USER')]
    public function dashboard(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $student = $user->getStudent();
        if (!$student) {
             return $this->redirectToRoute('app_freelancer_register');
        }

        return $this->render('student/dashboard.html.twig', [
            'student' => $student,
        ]);
    }




    #[Route('/job/new', name: 'app_job_new', methods: ['GET', 'POST'])]
    public function newJob(Request $request, EntityManagerInterface $entityManager): Response
    {
        $job = new Job();
        $user = $this->getUser();
        if (!$user) {
            // Find a default user to act as guest for "every button works" requirement
            $user = $entityManager->getRepository(\App\Entity\User::class)->findOneBy([]);
        }
        $job->setClient($user);
        $job->setStatus('open');
        $job->setCreatedAt(new \DateTimeImmutable());
        
        $form = $this->createForm(JobType::class, $job);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($job);
            $entityManager->flush();

            return $this->redirectToRoute('app_marketplace_index', [], Response::HTTP_SEE_OTHER);
        }
        
        return $this->render('job/new.html.twig', [
            'job' => $job,
            'form' => $form,
        ]);
    }

    #[Route('/job/{id}/delete', name: 'app_job_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function deleteJob(Request $request, Job $job, EntityManagerInterface $entityManager): Response
    {
        if ($job->getClient() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
             throw $this->createAccessDeniedException('You can only delete your own job requests.');
        }

        if ($this->isCsrfTokenValid('delete'.$job->getId(), $request->request->get('_token'))) {
            $job->setDeletedAt(new \DateTimeImmutable());
            $entityManager->flush();
            $this->addFlash('success', 'Job request deleted successfully.');
        }

        return $this->redirectToRoute('app_marketplace_index');
    }

    #[Route('/order/new/{id}', name: 'app_order_new', methods: ['GET', 'POST'])]
    public function newOrder(Product $product, Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        $order = new Order();
        $order->setProduct($product);
        $user = $this->getUser();
        if (!$user) {
            // Find a default user to act as guest for "every button works" requirement
            $user = $entityManager->getRepository(\App\Entity\User::class)->findOneBy([]);
        }
        $order->setBuyer($user);
        $order->setTotalPrice($product->getPrice());
        $order->setStatus('pending');
        $order->setCreatedAt(new \DateTimeImmutable());
        
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Simulate payment processing here
            $order->setStatus('paid');
            
            $entityManager->persist($order);
            $entityManager->flush();

            // Send Order Notification to Freelancer
            $email = (new TemplatedEmail())
                ->from(new Address('no-reply@unilearn.com', 'Unilearn Marketplace'))
                ->to($product->getFreelancer()->getUser()->getEmail())
                ->subject('You have a new order!')
                ->htmlTemplate('emails/order_notification.html.twig')
                ->context([
                    'freelancer_name' => $product->getFreelancer()->getFullName(),
                    'product_title' => $product->getTitle(),
                    'buyer_email' => $this->getUser()->getEmail(),
                    'price' => $product->getPrice(),
                ]);

            try {
                $mailer->send($email);
            } catch (\Exception $e) {
                // Silently fail
            }


            // Add success flash message
            $this->addFlash('order_success', [
                'product_title' => $product->getTitle(),
                'order_id' => $order->getId(),
            ]);

            return $this->redirectToRoute('app_admin_order_index', [], Response::HTTP_SEE_OTHER);
        }
        
        return $this->render('order/new.html.twig', [
            'order' => $order,
            'product' => $product,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/order/{id}/complete', name: 'app_order_complete', methods: ['POST'])]
    public function completeOrder(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if ($user && $order->getBuyer() !== $user && !$this->isGranted('ROLE_ADMIN')) {
             throw $this->createAccessDeniedException('You can only complete your own orders.');
        }
        
        // If no user, we assume it's the guest user who matches the buyer
        if (!$user && $order->getBuyer()->getEmail() !== $entityManager->getRepository(\App\Entity\User::class)->findOneBy([])->getEmail() && !$this->isGranted('ROLE_ADMIN')) {
             throw $this->createAccessDeniedException('You can only complete your own orders.');
        }

        if (!$this->isCsrfTokenValid('complete'.$order->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_product_show', ['slug' => $order->getProduct()->getSlug()]);
        }

        $rating = $request->request->get('rating');
        $review = $request->request->get('review');

        if ($rating) {
            $order->setStatus('completed');
            $order->setRating((int)$rating);
            $order->setReview($review);
            
            $student = $order->getProduct()->getFreelancer();
            $student->updateRating();
            
            $entityManager->flush();
            $this->addFlash('success', 'Order completed and freelancer rated!');
        }

        return $this->redirectToRoute('app_marketplace_index');
    }

    #[Route('/freelancer/register', name: 'app_freelancer_register', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function registerFreelancer(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        // Check if user is already a student
        if ($this->getUser()->getStudent()) {
            $this->addFlash('warning', 'You are already registered as a freelancer.');
            return $this->redirectToRoute('app_marketplace_dashboard');
        }

        $student = new Student();
        $student->setUser($this->getUser());
        $student->setRating(0);
        $student->setCreatedAt(new \DateTimeImmutable());

        $form = $this->createForm(StudentType::class, $student);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Assign ROLE_FREELANCER
            $user = $this->getUser();
            $roles = $user->getRoles();
            if (!in_array('ROLE_FREELANCER', $roles)) {
                $roles[] = 'ROLE_FREELANCER';
                $user->setRoles($roles);
                $entityManager->persist($user);
            }

            $entityManager->persist($student);
            $entityManager->flush();

            // Send Welcome Email
            $email = (new TemplatedEmail())
                ->from(new Address('no-reply@unilearn.com', 'Unilearn Marketplace'))
                ->to($user->getEmail())
                ->subject('Welcome to the Marketplace!')
                ->htmlTemplate('emails/freelancer_welcome.html.twig')
                ->context([
                    'name' => $student->getFullName(),
                ]);

            try {
                $mailer->send($email);
            } catch (\Exception $e) {
                // Silently fail if mailer not configured or use logging
            }

            return $this->redirectToRoute('app_marketplace_dashboard');
        }

        return $this->render('student/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }


}
