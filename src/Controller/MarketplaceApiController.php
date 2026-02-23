<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Order;
use App\Repository\ProductRepository;
use App\Repository\OrderRepository;
use App\Service\AIAnalystService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[Route('/api/marketplace')]
class MarketplaceApiController extends AbstractController
{
    public function __construct(
        private ProductRepository $productRepository,
        private OrderRepository $orderRepository,
        private EntityManagerInterface $entityManager,
        private CacheInterface $cache,
        private AIAnalystService $aiAnalystService
    ) {}

    #[Route('/trending', name: 'api_marketplace_trending', methods: ['GET'])]
    public function getTrendingProducts(Request $request): JsonResponse
    {
        $limit = min($request->query->getInt('limit', 15), 20);
        
        return $this->cache->get('trending_products_' . $limit, function (ItemInterface $item) use ($limit) {
            $item->expiresAfter(300); // Cache for 5 minutes
            
            // Get all products with their statistics
            $products = $this->productRepository->createQueryBuilder('p')
                ->leftJoin('p.category', 'c')
                ->leftJoin('p.freelancer', 'f')
                ->where('p.deletedAt IS NULL')
                ->orderBy('p.createdAt', 'DESC')
                ->getQuery()
                ->getResult();

            $trendingProducts = [];
            
            foreach ($products as $product) {
                $trendScore = $this->calculateTrendScore($product);
                $badge = $this->assignBadge($product, $trendScore);
                
                $trendingProducts[] = [
                    'id' => $product->getId(),
                    'title' => $product->getTitle(),
                    'description' => substr($product->getDescription(), 0, 150) . '...',
                    'price' => $product->getPrice(),
                    'image_url' => $product->getImage() ? '/uploads/products/' . $product->getImage() : null,
                    'trend_score' => $trendScore,
                    'badge' => $badge,
                    'category' => $product->getCategory()?->getName(),
                    'freelancer' => $product->getFreelancer()?->getFullName(),
                    'created_at' => $product->getCreatedAt()->format('Y-m-d H:i:s'),
                    'views_count' => $this->getProductViews($product),
                    'orders_count' => $this->getProductOrders($product),
                    'rating' => $this->getProductRating($product)
                ];
            }
            
            // Sort by trend score and limit
            usort($trendingProducts, function ($a, $b) {
                return $b['trend_score'] <=> $a['trend_score'];
            });
            
            return new JsonResponse(array_slice($trendingProducts, 0, $limit));
        });
    }

    #[Route('/recommendations', name: 'api_marketplace_recommendations', methods: ['GET'])]
    public function getRecommendations(Request $request): JsonResponse
    {
        $userId = $request->query->get('userId');
        
        if (!$userId) {
            return new JsonResponse(['error' => 'User ID is required'], 400);
        }
        
        return $this->cache->get('recommendations_' . $userId, function (ItemInterface $item) use ($userId) {
            $item->expiresAfter(600); // Cache for 10 minutes
            
            // Get user activity and generate embedding
            $userEmbedding = $this->generateUserEmbedding($userId);
            
            // Get all products and their embeddings
            $products = $this->productRepository->createQueryBuilder('p')
                ->leftJoin('p.category', 'c')
                ->leftJoin('p.freelancer', 'f')
                ->where('p.deletedAt IS NULL')
                ->getQuery()
                ->getResult();

            $recommendations = [];
            
            foreach ($products as $product) {
                $productEmbedding = $this->generateProductEmbedding($product);
                $relevanceScore = $this->calculateSimilarity($userEmbedding, $productEmbedding);
                
                $recommendations[] = [
                    'id' => $product->getId(),
                    'title' => $product->getTitle(),
                    'description' => substr($product->getDescription(), 0, 150) . '...',
                    'price' => $product->getPrice(),
                    'image_url' => $product->getImage() ? '/uploads/products/' . $product->getImage() : null,
                    'relevance_score' => $relevanceScore,
                    'category' => $product->getCategory()?->getName(),
                    'freelancer' => $product->getFreelancer()?->getFullName(),
                    'rating' => $this->getProductRating($product)
                ];
            }
            
            // Sort by relevance score and limit to 10
            usort($recommendations, function ($a, $b) {
                return $b['relevance_score'] <=> $a['relevance_score'];
            });
            
            return new JsonResponse(array_slice($recommendations, 0, 10));
        });
    }

    private function calculateTrendScore(Product $product): int
    {
        $score = 0;
        
        // Base score from views (0-30 points)
        $views = $this->getProductViews($product);
        $score += min($views * 2, 30);
        
        // Orders score (0-40 points)
        $orders = $this->getProductOrders($product);
        $score += min($orders * 10, 40);
        
        // Rating score (0-20 points)
        $rating = $this->getProductRating($product);
        $score += $rating * 4;
        
        // Recency bonus (0-10 points)
        $daysSinceCreated = (new \DateTime())->diff($product->getCreatedAt())->days;
        if ($daysSinceCreated <= 7) {
            $score += 10;
        } elseif ($daysSinceCreated <= 30) {
            $score += 5;
        }
        
        return min($score, 100);
    }

    private function assignBadge(Product $product, int $trendScore): string
    {
        $daysSinceCreated = (new \DateTime())->diff($product->getCreatedAt())->days;
        
        if ($daysSinceCreated <= 7) {
            return 'New';
        } elseif ($trendScore >= 80) {
            return 'Hot';
        } elseif ($product->getPrice() < 50 && $trendScore >= 60) {
            return 'Discount';
        }
        
        return '';
    }

    private function getProductViews(Product $product): int
    {
        // Simulate view count - in real implementation, track views in database
        return random_int(10, 1000);
    }

    private function getProductOrders(Product $product): int
    {
        return $this->orderRepository->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.product = :product')
            ->andWhere('o.status = :status')
            ->setParameter('product', $product)
            ->setParameter('status', 'paid')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }

    private function getProductRating(Product $product): float
    {
        // Simulate rating - in real implementation, calculate from reviews
        return round(3.5 + random_int(0, 15) / 10, 1);
    }

    private function generateUserEmbedding(string $userId): array
    {
        // Simulate user embedding based on activity
        // In real implementation, use ML model to generate embeddings
        return [
            'web_development' => random_int(0, 100) / 100,
            'design' => random_int(0, 100) / 100,
            'marketing' => random_int(0, 100) / 100,
            'writing' => random_int(0, 100) / 100,
            'consulting' => random_int(0, 100) / 100,
            'programming' => random_int(0, 100) / 100,
            'data_analysis' => random_int(0, 100) / 100,
            'translation' => random_int(0, 100) / 100
        ];
    }

    private function generateProductEmbedding(Product $product): array
    {
        $categoryName = $product->getCategory()?->getName() ?? '';
        
        return [
            'web_development' => $categoryName === 'Web Development' ? 1.0 : 0.0,
            'design' => $categoryName === 'Design' ? 1.0 : 0.0,
            'marketing' => $categoryName === 'Marketing' ? 1.0 : 0.0,
            'writing' => $categoryName === 'Writing' ? 1.0 : 0.0,
            'consulting' => $categoryName === 'Consulting' ? 1.0 : 0.0,
            'programming' => $categoryName === 'Programming' ? 1.0 : 0.0,
            'data_analysis' => $categoryName === 'Data Analysis' ? 1.0 : 0.0,
            'translation' => $categoryName === 'Translation' ? 1.0 : 0.0
        ];
    }

    private function calculateSimilarity(array $userEmbedding, array $productEmbedding): float
    {
        // Calculate cosine similarity
        $dotProduct = 0;
        $userMagnitude = 0;
        $productMagnitude = 0;
        
        foreach ($userEmbedding as $key => $userValue) {
            $productValue = $productEmbedding[$key] ?? 0;
            $dotProduct += $userValue * $productValue;
            $userMagnitude += $userValue * $userValue;
            $productMagnitude += $productValue * $productValue;
        }
        
        $userMagnitude = sqrt($userMagnitude);
        $productMagnitude = sqrt($productMagnitude);
        
        if ($userMagnitude == 0 || $productMagnitude == 0) {
            return 0;
        }
        
        return $dotProduct / ($userMagnitude * $productMagnitude);
    }

    #[Route('/ai-analysis', name: 'api_marketplace_ai_analysis', methods: ['GET'])]
    public function getAIAnalysis(Request $request): JsonResponse
    {
        try {
            // Get current statistics with error handling
            $stats = [
                'students' => 0,
                'products' => 0,
                'jobs' => 0,
                'orders' => 0,
                'revenue' => 0,
            ];

            // Safely get student count
            try {
                $stats['students'] = $this->entityManager->getRepository('App\Entity\Student')->count([]);
            } catch (\Exception $e) {
                $stats['students'] = 0;
            }

            // Safely get product count
            try {
                $stats['products'] = $this->productRepository->count(['deletedAt' => null]);
            } catch (\Exception $e) {
                $stats['products'] = 0;
            }

            // Safely get job count
            try {
                $stats['jobs'] = $this->entityManager->getRepository('App\Entity\Job')->count(['deletedAt' => null]);
            } catch (\Exception $e) {
                $stats['jobs'] = 0;
            }

            // Safely get order count
            try {
                $stats['orders'] = $this->orderRepository->count([]);
            } catch (\Exception $e) {
                $stats['orders'] = 0;
            }

            // Safely get revenue
            try {
                $revenue = $this->orderRepository->createQueryBuilder('o')
                    ->select('SUM(o.totalPrice)')
                    ->where('o.status = :status')
                    ->setParameter('status', 'completed')
                    ->getQuery()
                    ->getSingleScalarResult();
                $stats['revenue'] = $revenue ?: 0;
            } catch (\Exception $e) {
                $stats['revenue'] = 0;
            }

            // Generate some trend data
            $trends = [
                'Sales Growth' => '+22%',
                'Views Growth' => '+5%',
                'Cart Abandonment' => '40%',
                'Top Category' => 'Web Development'
            ];

            // Get AI analysis
            $analysis = $this->aiAnalystService->analyzeMarketplaceData($stats, $trends);

            return new JsonResponse([
                'stats' => $stats,
                'trends' => $trends,
                'analysis' => $analysis,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Failed to generate AI analysis',
                'message' => $e->getMessage(),
                'stats' => ['students' => 0, 'products' => 0, 'jobs' => 0, 'orders' => 0, 'revenue' => 0],
                'trends' => [],
                'analysis' => [
                    'assessment' => 'Fair',
                    'strengths' => 'Basic functionality working',
                    'weaknesses' => 'AI analysis temporarily unavailable',
                    'recommendations' => [
                        'Check system logs for errors',
                        'Verify database connections',
                        'Contact support if issues persist'
                    ],
                    'priority' => 'Medium'
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ], 500);
        }
    }
}
