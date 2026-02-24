<?php

namespace App\Controller\Api;

use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class RecommendationApiController extends AbstractController
{
    public function __construct(
        private ProductRepository $productRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/products/recently-viewed', name: 'api_recently_viewed', methods: ['GET'])]
    public function getRecentlyViewed(Request $request, SessionInterface $session): JsonResponse
    {
        try {
            $userId = $request->query->get('user_id');
            $limit = max(1, min(20, $request->query->getInt('limit', 5)));

            $recentlyViewed = [];
            
            if ($userId) {
                // Get recently viewed for logged-in user
                $user = $this->userRepository->find($userId);
                if ($user) {
                    $recentlyViewed = $this->getUserRecentlyViewed($user, $limit);
                }
            } else {
                // Get recently viewed from session
                $sessionViewed = $session->get('recently_viewed_products', []);
                $recentlyViewed = $this->getSessionRecentlyViewed($sessionViewed, $limit);
            }

            return new JsonResponse([
                'success' => true,
                'data' => $recentlyViewed,
                'meta' => [
                    'type' => $userId ? 'user_based' : 'session_based',
                    'count' => count($recentlyViewed),
                    'limit' => $limit
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to fetch recently viewed products',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/products/recommended', name: 'api_recommended_products', methods: ['GET'])]
    public function getRecommendedProducts(Request $request, SessionInterface $session): JsonResponse
    {
        try {
            $userId = $request->query->get('user_id');
            $limit = max(1, min(20, $request->query->getInt('limit', 5)));

            $recommended = [];
            
            if ($userId) {
                // Get personalized recommendations for logged-in user
                $user = $this->userRepository->find($userId);
                if ($user) {
                    $recommended = $this->getPersonalizedRecommendations($user, $limit);
                }
            } else {
                // Get recommendations based on session and popular products
                $sessionViewed = $session->get('recently_viewed_products', []);
                $recommended = $this->getSessionBasedRecommendations($sessionViewed, $limit);
            }

            return new JsonResponse([
                'success' => true,
                'data' => $recommended,
                'meta' => [
                    'type' => $userId ? 'personalized' : 'session_based',
                    'count' => count($recommended),
                    'limit' => $limit,
                    'algorithm' => $userId ? 'collaborative_filtering' : 'popular_in_category'
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to fetch recommended products',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/products/track-view', name: 'api_track_product_view', methods: ['POST'])]
    public function trackProductView(Request $request, SessionInterface $session): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $productId = $data['product_id'] ?? null;
            $userId = $data['user_id'] ?? null;

            if (!$productId) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Product ID is required'
                ], 400);
            }

            // Track in session
            $this->trackInViewSession($session, $productId);

            // Track for logged-in user
            if ($userId) {
                $this->trackForUser($userId, $productId);
            }

            return new JsonResponse([
                'success' => true,
                'message' => 'Product view tracked successfully'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to track product view',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/products/also-bought/{id}', name: 'api_also_bought_products', methods: ['GET'])]
    public function getAlsoBoughtProducts(int $id, Request $request): JsonResponse
    {
        try {
            $limit = max(1, min(20, $request->query->getInt('limit', 5)));
            
            $product = $this->productRepository->find($id);
            if (!$product) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Product not found'
                ], 404);
            }

            $alsoBought = $this->getAlsoBoughtProductsPrivate($product, $limit);

            return new JsonResponse([
                'success' => true,
                'data' => $alsoBought,
                'meta' => [
                    'product_id' => $id,
                    'product_title' => $product->getTitle(),
                    'count' => count($alsoBought),
                    'limit' => $limit
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to fetch also bought products',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function getUserRecentlyViewed($user, int $limit): array
    {
        // This would typically use a UserViewHistory entity
        // For now, we'll simulate with recent orders and popular products
        $qb = $this->productRepository->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->where('p.deletedAt IS NULL')
            ->andWhere('p.status = :status')
            ->setParameter('status', 'active')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit);

        $products = $qb->getQuery()->getResult();

        return $this->formatProducts($products);
    }

    private function getSessionRecentlyViewed(array $sessionViewed, int $limit): array
    {
        if (empty($sessionViewed)) {
            return $this->getPopularProducts($limit);
        }

        $productIds = array_slice(array_unique($sessionViewed), -$limit, $limit, true);
        
        $qb = $this->productRepository->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->where('p.id IN (:ids)')
            ->andWhere('p.deletedAt IS NULL')
            ->andWhere('p.status = :status')
            ->setParameter('ids', $productIds)
            ->setParameter('status', 'active')
            ->orderBy('p.createdAt', 'DESC');

        $products = $qb->getQuery()->getResult();

        return $this->formatProducts($products);
    }

    private function getPersonalizedRecommendations($user, int $limit): array
    {
        // Get products from user's viewed categories
        $qb = $this->productRepository->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->where('p.deletedAt IS NULL')
            ->andWhere('p.status = :status')
            ->setParameter('status', 'active')
            ->orderBy('p.views', 'DESC')
            ->setMaxResults($limit);

        $products = $qb->getQuery()->getResult();

        return $this->formatProducts($products);
    }

    private function getSessionBasedRecommendations(array $sessionViewed, int $limit): array
    {
        if (empty($sessionViewed)) {
            return $this->getPopularProducts($limit);
        }

        // Get products from recently viewed categories
        $productIds = array_slice($sessionViewed, -3, 3);
        
        $qb = $this->productRepository->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->where('p.id NOT IN (:viewedIds)')
            ->andWhere('p.deletedAt IS NULL')
            ->andWhere('p.status = :status')
            ->setParameter('viewedIds', $sessionViewed)
            ->setParameter('status', 'active')
            ->orderBy('p.rating', 'DESC')
            ->setMaxResults($limit);

        $products = $qb->getQuery()->getResult();

        return $this->formatProducts($products);
    }

    private function getAlsoBoughtProductsPrivate($product, int $limit): array
    {
        // Get products from the same category
        $qb = $this->productRepository->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->where('p.id != :currentId')
            ->andWhere('p.category = :category')
            ->andWhere('p.deletedAt IS NULL')
            ->andWhere('p.status = :status')
            ->setParameter('currentId', $product->getId())
            ->setParameter('category', $product->getCategory())
            ->setParameter('status', 'active')
            ->orderBy('p.rating', 'DESC')
            ->setMaxResults($limit);

        $products = $qb->getQuery()->getResult();

        return $this->formatProducts($products);
    }

    private function getPopularProducts(int $limit): array
    {
        $qb = $this->productRepository->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->where('p.deletedAt IS NULL')
            ->andWhere('p.status = :status')
            ->setParameter('status', 'active')
            ->orderBy('p.views', 'DESC')
            ->setMaxResults($limit);

        $products = $qb->getQuery()->getResult();

        return $this->formatProducts($products);
    }

    private function trackInViewSession(SessionInterface $session, int $productId): void
    {
        $viewed = $session->get('recently_viewed_products', []);
        
        // Add product to recently viewed
        if (!in_array($productId, $viewed)) {
            $viewed[] = $productId;
        }
        
        // Keep only last 20 products
        $session->set('recently_viewed_products', array_slice($viewed, -20));
    }

    private function trackForUser(int $userId, int $productId): void
    {
        // This would typically save to UserViewHistory entity
        // For now, we'll just log it
        error_log("User {$userId} viewed product {$productId}");
    }

    private function formatProducts(array $products): array
    {
        $formatted = [];
        foreach ($products as $product) {
            $formatted[] = [
                'id' => $product->getId(),
                'title' => $product->getTitle(),
                'slug' => $product->getSlug(),
                'description' => substr($product->getDescription(), 0, 150) . '...',
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
        return $formatted;
    }
}
