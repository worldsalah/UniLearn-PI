<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/product')]
// #[IsGranted('ROLE_ADMIN')]
class ProductController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'app_admin_product_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $search = $request->query->get('search', '');
        
        // Build query with search functionality
        $queryBuilder = $productRepository->createQueryBuilder('p');
        
        // Add search condition if search term is provided
        if (!empty($search)) {
            $queryBuilder->where('p.title LIKE :search OR p.description LIKE :search')
                     ->setParameter('search', '%' . $search . '%');
        }
        
        $queryBuilder->orderBy('p.createdAt', 'DESC');
        $query = $queryBuilder->getQuery();
        
        $products = $paginator->paginate($query, $request->query->getInt('page', 1), 10);

        return $this->render('admin/product/index.html.twig', [
            'products' => $products,
            'productStats' => $this->getProductStatistics(),
            'search' => $search,
        ]);
    }

    #[Route('/new', name: 'app_admin_product_new', methods: ['GET', 'POST'], priority: 2)]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $product = new Product();
        
        // Set the freelancer before form creation to avoid validation issues
        $user = $this->getUser();
        if (!$user) {
            // For admin, you might want to set a default user or handle differently
            $product->setFreelancer(null);
        } else {
            $product->setFreelancer($user);
        }
        
        $form = $this->createForm(\App\Form\Form\ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($product);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EntityManagerInterface $entityManager, ProductRepository $productRepository, int $id): Response
    {
        $product = $productRepository->find($id);
        
        if (!$product) {
            $this->addFlash('error', 'Product not found.');
            return $this->redirectToRoute('app_admin_product_index', [], Response::HTTP_SEE_OTHER);
        }
        
        $form = $this->createForm(\App\Form\Form\ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Product updated successfully.');

            return $this->redirectToRoute('app_admin_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_product_delete', methods: ['POST'])]
    public function delete(Request $request, EntityManagerInterface $entityManager, ProductRepository $productRepository, int $id): Response
    {
        $product = $productRepository->find($id);
        
        if (!$product) {
            $this->addFlash('error', 'Product not found.');
            return $this->redirectToRoute('app_admin_product_index', [], Response::HTTP_SEE_OTHER);
        }
        
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            $entityManager->remove($product);
            $entityManager->flush();
            $this->addFlash('success', 'Product deleted successfully.');
        }

        return $this->redirectToRoute('app_admin_product_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'app_admin_product_show', methods: ['GET'])]
    public function show(ProductRepository $productRepository, int $id): Response
    {
        $product = $productRepository->find($id);
        
        if (!$product) {
            $this->addFlash('error', 'Product not found.');
            return $this->redirectToRoute('app_admin_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/product/show.html.twig', [
            'product' => $product,
        ]);
    }

    /**
     * Get product statistics for dashboard display
     */
    private function getProductStatistics(): array
    {
        $productRepository = $this->entityManager->getRepository(Product::class);
        
        // Total products
        $totalProducts = $productRepository->count([]);
        
        // Active products (not deleted)
        $activeProductsQuery = $productRepository->createQueryBuilder('p')
            ->select('COUNT(p.id) as count')
            ->where('p.deletedAt IS NULL')
            ->getQuery();
        $activeProducts = $activeProductsQuery->getSingleScalarResult() ?: 0;
        
        // Total value of all products
        $totalValueQuery = $productRepository->createQueryBuilder('p')
            ->select('SUM(p.price) as total')
            ->where('p.deletedAt IS NULL')
            ->getQuery();
        $totalValue = $totalValueQuery->getSingleScalarResult() ?: 0;
        
        // Products by category
        $categoryStatsQuery = $productRepository->createQueryBuilder('p')
            ->select('c.name as categoryName, COUNT(p.id) as productCount')
            ->leftJoin('p.category', 'c')
            ->where('p.deletedAt IS NULL')
            ->groupBy('c.id')
            ->orderBy('productCount', 'DESC')
            ->getQuery();
        $categoryStats = $categoryStatsQuery->getResult();
        
        // Recent products (last 7 days)
        $sevenDaysAgo = new \DateTime('-7 days');
        $recentProductsQuery = $productRepository->createQueryBuilder('p')
            ->select('COUNT(p.id) as count')
            ->where('p.createdAt >= :date')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('date', $sevenDaysAgo)
            ->getQuery();
        $recentProducts = $recentProductsQuery->getSingleScalarResult() ?: 0;
        
        return [
            'totalProducts' => $totalProducts,
            'activeProducts' => $activeProducts,
            'totalValue' => $totalValue,
            'categoryStats' => $categoryStats,
            'recentProducts' => $recentProducts,
        ];
    }
}
