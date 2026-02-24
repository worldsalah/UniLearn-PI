<?php

namespace App\Controller\Api;

use App\Repository\ProductRepository;
use App\Repository\OrderRepository;
use App\Repository\StudentRepository;
use App\Repository\JobRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class PaginationApiController extends AbstractController
{
    public function __construct(
        private ProductRepository $productRepository,
        private OrderRepository $orderRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/products', name: 'api_products_paginated', methods: ['GET'])]
    public function getPaginatedProducts(Request $request): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = max(1, min(50, $request->query->getInt('limit', 10)));
        $search = $request->query->get('search', '');
        $category = $request->query->get('category', '');
        $minPrice = $request->query->get('min_price');
        $maxPrice = $request->query->get('max_price');
        $sortBy = $request->query->get('sort_by', 'createdAt');
        $sortOrder = $request->query->get('sort_order', 'DESC');

        try {
            $queryBuilder = $this->productRepository->createQueryBuilder('p')
                ->leftJoin('p.category', 'c')
                ->where('p.deletedAt IS NULL')
                ->andWhere('p.status = :status')
                ->setParameter('status', 'active');

            // Apply search filter
            if (!empty($search)) {
                $queryBuilder->andWhere('p.title LIKE :search OR p.description LIKE :search')
                    ->setParameter('search', '%' . $search . '%');
            }

            // Apply category filter
            if (!empty($category)) {
                $queryBuilder->andWhere('c.name = :category')
                    ->setParameter('category', $category);
            }

            // Apply price filters
            if ($minPrice !== null) {
                $queryBuilder->andWhere('p.price >= :minPrice')
                    ->setParameter('minPrice', $minPrice);
            }
            if ($maxPrice !== null) {
                $queryBuilder->andWhere('p.price <= :maxPrice')
                    ->setParameter('maxPrice', $maxPrice);
            }

            // Apply sorting
            $allowedSortFields = ['createdAt', 'title', 'price', 'rating', 'views'];
            if (in_array($sortBy, $allowedSortFields)) {
                $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';
                $queryBuilder->orderBy("p.{$sortBy}", $sortOrder);
            } else {
                $queryBuilder->orderBy('p.createdAt', 'DESC');
            }

            // Get total count
            $totalQuery = clone $queryBuilder;
            $total = count($totalQuery->getQuery()->getResult());

            // Apply pagination
            $offset = ($page - 1) * $limit;
            $queryBuilder->setMaxResults($limit)
                        ->setFirstResult($offset);

            $products = $queryBuilder->getQuery()->getResult();

            // Format products
            $formattedProducts = [];
            foreach ($products as $product) {
                $formattedProducts[] = [
                    'id' => $product->getId(),
                    'title' => $product->getTitle(),
                    'slug' => $product->getSlug(),
                    'description' => substr($product->getDescription(), 0, 200) . '...',
                    'price' => $product->getPrice(),
                    'category' => $product->getCategory() ? $product->getCategory()->getName() : 'Uncategorized',
                    'image' => $product->getImage() ? '/uploads/products/' . $product->getImage() : null,
                    'freelancer' => [
                        'id' => $product->getFreelancer()?->getId(),
                        'name' => $product->getFreelancer()?->getFullName() ?? 'Unknown',
                        'email' => $product->getFreelancer()?->getEmail() ?? 'unknown@example.com'
                    ],
                    'rating' => $product->getRating() ?? 0,
                    'views' => $product->getViews() ?? 0,
                    'created_at' => $product->getCreatedAt()->format('Y-m-d H:i:s'),
                    'updated_at' => $product->getUpdatedAt()->format('Y-m-d H:i:s')
                ];
            }

            $totalPages = ceil($total / $limit);

            return new JsonResponse([
                'success' => true,
                'data' => $formattedProducts,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'total_pages' => $totalPages,
                    'has_next_page' => $page < $totalPages,
                    'has_previous_page' => $page > 1,
                    'next_page' => $page < $totalPages ? $page + 1 : null,
                    'previous_page' => $page > 1 ? $page - 1 : null,
                    'first_page' => 1,
                    'last_page' => $totalPages,
                    'from' => $total > 0 ? $offset + 1 : null,
                    'to' => min($offset + $limit, $total)
                ],
                'filters' => [
                    'search' => $search,
                    'category' => $category,
                    'min_price' => $minPrice,
                    'max_price' => $maxPrice,
                    'sort_by' => $sortBy,
                    'sort_order' => $sortOrder
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to fetch products',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/orders', name: 'api_orders_paginated', methods: ['GET'])]
    public function getPaginatedOrders(Request $request): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = max(1, min(50, $request->query->getInt('limit', 10)));
        $status = $request->query->get('status', '');
        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');
        $sortBy = $request->query->get('sort_by', 'createdAt');
        $sortOrder = $request->query->get('sort_order', 'DESC');

        try {
            $queryBuilder = $this->orderRepository->createQueryBuilder('o')
                ->leftJoin('o.user', 'u')
                ->leftJoin('o.items', 'oi')
                ->leftJoin('oi.product', 'p');

            // Apply status filter
            if (!empty($status)) {
                $queryBuilder->andWhere('o.status = :status')
                    ->setParameter('status', $status);
            }

            // Apply date filters
            if ($startDate) {
                $queryBuilder->andWhere('o.createdAt >= :startDate')
                    ->setParameter('startDate', new \DateTime($startDate));
            }
            if ($endDate) {
                $queryBuilder->andWhere('o.createdAt <= :endDate')
                    ->setParameter('endDate', new \DateTime($endDate . ' 23:59:59'));
            }

            // Apply sorting
            $allowedSortFields = ['createdAt', 'totalPrice', 'status', 'id'];
            if (in_array($sortBy, $allowedSortFields)) {
                $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';
                $queryBuilder->orderBy("o.{$sortBy}", $sortOrder);
            } else {
                $queryBuilder->orderBy('o.createdAt', 'DESC');
            }

            // Get total count
            $totalQuery = clone $queryBuilder;
            $total = count($totalQuery->getQuery()->getResult());

            // Apply pagination
            $offset = ($page - 1) * $limit;
            $queryBuilder->setMaxResults($limit)
                        ->setFirstResult($offset);

            $orders = $queryBuilder->getQuery()->getResult();

            // Format orders
            $formattedOrders = [];
            foreach ($orders as $order) {
                $items = [];
                foreach ($order->getItems() as $item) {
                    $items[] = [
                        'id' => $item->getId(),
                        'product' => [
                            'id' => $item->getProduct()->getId(),
                            'title' => $item->getProduct()->getTitle(),
                            'price' => $item->getProduct()->getPrice()
                        ],
                        'quantity' => $item->getQuantity(),
                        'subtotal' => $item->getSubtotal()
                    ];
                }

                $formattedOrders[] = [
                    'id' => $order->getId(),
                    'user' => [
                        'id' => $order->getUser()?->getId(),
                        'name' => $order->getUser()?->getFullName() ?? 'Unknown',
                        'email' => $order->getUser()?->getEmail() ?? 'unknown@example.com'
                    ],
                    'items' => $items,
                    'total_price' => $order->getTotalPrice(),
                    'status' => $order->getStatus(),
                    'created_at' => $order->getCreatedAt()->format('Y-m-d H:i:s'),
                    'updated_at' => $order->getUpdatedAt()->format('Y-m-d H:i:s')
                ];
            }

            $totalPages = ceil($total / $limit);

            return new JsonResponse([
                'success' => true,
                'data' => $formattedOrders,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'total_pages' => $totalPages,
                    'has_next_page' => $page < $totalPages,
                    'has_previous_page' => $page > 1,
                    'next_page' => $page < $totalPages ? $page + 1 : null,
                    'previous_page' => $page > 1 ? $page - 1 : null,
                    'first_page' => 1,
                    'last_page' => $totalPages,
                    'from' => $total > 0 ? $offset + 1 : null,
                    'to' => min($offset + $limit, $total)
                ],
                'filters' => [
                    'status' => $status,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'sort_by' => $sortBy,
                    'sort_order' => $sortOrder
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to fetch orders',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/students', name: 'api_students_paginated', methods: ['GET'])]
    public function getPaginatedStudents(Request $request): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = max(1, min(50, $request->query->getInt('limit', 10)));
        $search = $request->query->get('search', '');
        $sortBy = $request->query->get('sort_by', 'createdAt');
        $sortOrder = $request->query->get('sort_order', 'DESC');

        try {
            // Use Doctrine to query students
            $queryBuilder = $this->entityManager->createQueryBuilder('s')
                ->from('App\Entity\Student', 's');

            // Apply search filter
            if (!empty($search)) {
                $queryBuilder->andWhere('s.fullName LIKE :search OR s.email LIKE :search')
                    ->setParameter('search', '%' . $search . '%');
            }

            // Apply sorting
            $allowedSortFields = ['createdAt', 'fullName', 'email', 'id'];
            if (in_array($sortBy, $allowedSortFields)) {
                $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';
                $queryBuilder->orderBy("s.{$sortBy}", $sortOrder);
            } else {
                $queryBuilder->orderBy('s.createdAt', 'DESC');
            }

            // Get total count
            $totalQuery = clone $queryBuilder;
            $total = count($totalQuery->getQuery()->getResult());

            // Apply pagination
            $offset = ($page - 1) * $limit;
            $queryBuilder->setMaxResults($limit)
                        ->setFirstResult($offset);

            $students = $queryBuilder->getQuery()->getResult();

            // Format students
            $formattedStudents = [];
            foreach ($students as $student) {
                $formattedStudents[] = [
                    'id' => $student->getId(),
                    'full_name' => $student->getFullName(),
                    'email' => $student->getEmail(),
                    'phone' => $student->getPhone(),
                    'bio' => $student->getBio(),
                    'skills' => $student->getSkills(),
                    'rating' => $student->getRating() ?? 0,
                    'created_at' => $student->getCreatedAt()->format('Y-m-d H:i:s'),
                    'updated_at' => $student->getUpdatedAt()->format('Y-m-d H:i:s')
                ];
            }

            $totalPages = ceil($total / $limit);

            return new JsonResponse([
                'success' => true,
                'data' => $formattedStudents,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'total_pages' => $totalPages,
                    'has_next_page' => $page < $totalPages,
                    'has_previous_page' => $page > 1,
                    'next_page' => $page < $totalPages ? $page + 1 : null,
                    'previous_page' => $page > 1 ? $page - 1 : null,
                    'first_page' => 1,
                    'last_page' => $totalPages,
                    'from' => $total > 0 ? $offset + 1 : null,
                    'to' => min($offset + $limit, $total)
                ],
                'filters' => [
                    'search' => $search,
                    'sort_by' => $sortBy,
                    'sort_order' => $sortOrder
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to fetch students',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/jobs', name: 'api_jobs_paginated', methods: ['GET'])]
    public function getPaginatedJobs(Request $request): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = max(1, min(50, $request->query->getInt('limit', 10)));
        $search = $request->query->get('search', '');
        $status = $request->query->get('status', '');
        $minBudget = $request->query->get('min_budget');
        $maxBudget = $request->query->get('max_budget');
        $sortBy = $request->query->get('sort_by', 'createdAt');
        $sortOrder = $request->query->get('sort_order', 'DESC');

        try {
            // Use Doctrine to query jobs
            $queryBuilder = $this->entityManager->createQueryBuilder('j')
                ->leftJoin('j.client', 'c');

            // Apply search filter
            if (!empty($search)) {
                $queryBuilder->andWhere('j.title LIKE :search OR j.description LIKE :search')
                    ->setParameter('search', '%' . $search . '%');
            }

            // Apply status filter
            if (!empty($status)) {
                $queryBuilder->andWhere('j.status = :status')
                    ->setParameter('status', $status);
            }

            // Apply budget filters
            if ($minBudget !== null) {
                $queryBuilder->andWhere('j.budget >= :minBudget')
                    ->setParameter('minBudget', $minBudget);
            }
            if ($maxBudget !== null) {
                $queryBuilder->andWhere('j.budget <= :maxBudget')
                    ->setParameter('maxBudget', $maxBudget);
            }

            // Apply sorting
            $allowedSortFields = ['createdAt', 'title', 'budget', 'deadline', 'id'];
            if (in_array($sortBy, $allowedSortFields)) {
                $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';
                $queryBuilder->orderBy("j.{$sortBy}", $sortOrder);
            } else {
                $queryBuilder->orderBy('j.createdAt', 'DESC');
            }

            // Get total count
            $totalQuery = clone $queryBuilder;
            $total = count($totalQuery->getQuery()->getResult());

            // Apply pagination
            $offset = ($page - 1) * $limit;
            $queryBuilder->setMaxResults($limit)
                        ->setFirstResult($offset);

            $jobs = $queryBuilder->getQuery()->getResult();

            // Format jobs
            $formattedJobs = [];
            foreach ($jobs as $job) {
                $formattedJobs[] = [
                    'id' => $job->getId(),
                    'title' => $job->getTitle(),
                    'description' => substr($job->getDescription(), 0, 200) . '...',
                    'budget' => $job->getBudget(),
                    'status' => $job->getStatus(),
                    'deadline' => $job->getDeadline() ? $job->getDeadline()->format('Y-m-d') : null,
                    'client' => [
                        'id' => $job->getClient()?->getId(),
                        'name' => $job->getClient()?->getFullName() ?? 'Unknown',
                        'email' => $job->getClient()?->getEmail() ?? 'unknown@example.com'
                    ],
                    'created_at' => $job->getCreatedAt()->format('Y-m-d H:i:s'),
                    'updated_at' => $job->getUpdatedAt()->format('Y-m-d H:i:s')
                ];
            }

            $totalPages = ceil($total / $limit);

            return new JsonResponse([
                'success' => true,
                'data' => $formattedJobs,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'total_pages' => $totalPages,
                    'has_next_page' => $page < $totalPages,
                    'has_previous_page' => $page > 1,
                    'next_page' => $page < $totalPages ? $page + 1 : null,
                    'previous_page' => $page > 1 ? $page - 1 : null,
                    'first_page' => 1,
                    'last_page' => $totalPages,
                    'from' => $total > 0 ? $offset + 1 : null,
                    'to' => min($offset + $limit, $total)
                ],
                'filters' => [
                    'search' => $search,
                    'status' => $status,
                    'min_budget' => $minBudget,
                    'max_budget' => $maxBudget,
                    'sort_by' => $sortBy,
                    'sort_order' => $sortOrder
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to fetch jobs',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
