<?php

namespace App\Controller\Api;

use App\Service\SellerReputationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/reputation')]
class ReputationApiController extends AbstractController
{
    private SellerReputationService $reputationService;

    public function __construct(SellerReputationService $reputationService)
    {
        $this->reputationService = $reputationService;
    }

    /**
     * Get seller reputation score
     */
    #[Route('/seller/{id}', name: 'api_reputation_seller', methods: ['GET'])]
    public function sellerReputation(int $id): JsonResponse
    {
        // This would typically find the user by ID
        // For demo, we'll return mock data
        $seller = $this->getUser(); // Simplified for demo
        
        if (!$seller) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Seller not found'
            ], 404);
        }
        
        $reputation = $this->reputationService->calculateReputationScore($seller);
        
        return new JsonResponse([
            'success' => true,
            'data' => [
                'seller_id' => $id,
                'reputation' => $reputation,
                'badge_info' => $this->reputationService->getLevel($reputation['overall_score']),
                'last_updated' => (new \DateTime())->format('Y-m-d H:i:s')
            ]
        ]);
    }

    /**
     * Get seller performance metrics
     */
    #[Route('/seller/{id}/performance', name: 'api_reputation_performance', methods: ['GET'])]
    public function sellerPerformance(int $id): JsonResponse
    {
        $seller = $this->getUser(); // Simplified for demo
        
        if (!$seller) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Seller not found'
            ], 404);
        }
        
        $performance = $this->reputationService->getPerformanceMetrics($seller);
        
        return new JsonResponse([
            'success' => true,
            'data' => [
                'seller_id' => $id,
                'performance' => $performance,
                'generated_at' => (new \DateTime())->format('Y-m-d H:i:s')
            ]
        ]);
    }

    /**
     * Get seller statistics
     */
    #[Route('/seller/{id}/stats', name: 'api_reputation_stats', methods: ['GET'])]
    public function sellerStats(int $id): JsonResponse
    {
        $seller = $this->getUser(); // Simplified for demo
        
        if (!$seller) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Seller not found'
            ], 404);
        }
        
        $stats = $this->reputationService->getSellerStats($seller);
        
        return new JsonResponse([
            'success' => true,
            'data' => [
                'seller_id' => $id,
                'statistics' => $stats,
                'calculated_at' => (new \DateTime())->format('Y-m-d H:i:s')
            ]
        ]);
    }

    /**
     * Get badge information
     */
    #[Route('/badges', name: 'api_reputation_badges', methods: ['GET'])]
    public function badges(): JsonResponse
    {
        $badges = [
            'elite' => [
                'name' => 'Elite Seller',
                'description' => 'Top performers with exceptional track records',
                'requirements' => [
                    '90+ reputation score',
                    '50+ completed orders',
                    '4.8+ average rating',
                    'Less than 2% dispute rate'
                ],
                'benefits' => [
                    'Priority customer support',
                    'Featured placement in search results',
                    'Lower transaction fees (2%)',
                    'Advanced analytics dashboard',
                    'Elite seller badge on profile'
                ],
                'color' => '#6366f1',
                'icon' => '🏆'
            ],
            'gold' => [
                'name' => 'Gold Seller',
                'description' => 'Experienced sellers with strong performance',
                'requirements' => [
                    '75-89 reputation score',
                    '25-49 completed orders',
                    '4.5-4.7 average rating',
                    'Less than 5% dispute rate'
                ],
                'benefits' => [
                    'Enhanced visibility in marketplace',
                    'Standard priority support',
                    'Reduced transaction fees (3%)',
                    'Basic analytics dashboard',
                    'Gold seller badge on profile'
                ],
                'color' => '#f59e0b',
                'icon' => '🥇'
            ],
            'silver' => [
                'name' => 'Silver Seller',
                'description' => 'Reliable sellers building their reputation',
                'requirements' => [
                    '60-74 reputation score',
                    '10-24 completed orders',
                    '4.0-4.4 average rating',
                    'Less than 8% dispute rate'
                ],
                'benefits' => [
                    'Improved search visibility',
                    'Email support',
                    'Standard transaction fees (4%)',
                    'Silver seller badge on profile'
                ],
                'color' => '#6b7280',
                'icon' => '🥈'
            ],
            'bronze' => [
                'name' => 'Bronze Seller',
                'description' => 'New sellers establishing their presence',
                'requirements' => [
                    '40-59 reputation score',
                    '5-9 completed orders',
                    '3.5-3.9 average rating',
                    'Less than 10% dispute rate'
                ],
                'benefits' => [
                    'Basic marketplace access',
                    'Community support',
                    'Standard transaction fees (5%)',
                    'Bronze seller badge on profile'
                ],
                'color' => '#92400e',
                'icon' => '🥉'
            ],
            'new' => [
                'name' => 'New Seller',
                'description' => 'Just getting started on the platform',
                'requirements' => [
                    'Building reputation',
                    'First few orders'
                ],
                'benefits' => [
                    'Getting started guide',
                    'Community forum access',
                    'Standard transaction fees (5%)'
                ],
                'color' => '#d1d5db',
                'icon' => '🌱'
            ]
        ];
        
        return new JsonResponse([
            'success' => true,
            'data' => [
                'badges' => $badges,
                'total_levels' => count($badges)
            ]
        ]);
    }

    /**
     * Get top sellers by reputation
     */
    #[Route('/top-sellers', name: 'api_reputation_top', methods: ['GET'])]
    public function topSellers(Request $request): JsonResponse
    {
        $limit = min($request->query->getInt('limit', 20), 100);
        $category = $request->query->get('category');
        
        // This would typically query the database for top sellers
        // For demo, return mock data
        $topSellers = [
            [
                'id' => 1,
                'name' => 'John Developer',
                'avatar' => '/images/avatars/john.jpg',
                'reputation_score' => 95.2,
                'badge' => 'elite',
                'completed_orders' => 127,
                'avg_rating' => 4.9,
                'category' => 'Web Development'
            ],
            [
                'id' => 2,
                'name' => 'Sarah Designer',
                'avatar' => '/images/avatars/sarah.jpg',
                'reputation_score' => 88.7,
                'badge' => 'gold',
                'completed_orders' => 89,
                'avg_rating' => 4.8,
                'category' => 'Design'
            ],
            [
                'id' => 3,
                'name' => 'Mike Writer',
                'avatar' => '/images/avatars/mike.jpg',
                'reputation_score' => 76.3,
                'badge' => 'gold',
                'completed_orders' => 56,
                'avg_rating' => 4.6,
                'category' => 'Writing'
            ]
        ];
        
        // Filter by category if specified
        if ($category) {
            $topSellers = array_filter($topSellers, function($seller) use ($category) {
                return strtolower($seller['category']) === strtolower($category);
            });
        }
        
        return new JsonResponse([
            'success' => true,
            'data' => [
                'top_sellers' => array_values($topSellers),
                'count' => count($topSellers),
                'category_filter' => $category,
                'limit' => $limit
            ]
        ]);
    }

    /**
     * Get reputation leaderboard
     */
    #[Route('/leaderboard', name: 'api_reputation_leaderboard', methods: ['GET'])]
    public function leaderboard(Request $request): JsonResponse
    {
        $period = $request->query->get('period', 'all'); // week, month, all
        $category = $request->query->get('category');
        
        // Mock leaderboard data
        $leaderboard = [
            [
                'rank' => 1,
                'seller' => [
                    'id' => 1,
                    'name' => 'John Developer',
                    'avatar' => '/images/avatars/john.jpg'
                ],
                'reputation_score' => 95.2,
                'badge' => 'elite',
                'change' => '+2.3'
            ],
            [
                'rank' => 2,
                'seller' => [
                    'id' => 2,
                    'name' => 'Sarah Designer',
                    'avatar' => '/images/avatars/sarah.jpg'
                ],
                'reputation_score' => 88.7,
                'badge' => 'gold',
                'change' => '+1.1'
            ],
            [
                'rank' => 3,
                'seller' => [
                    'id' => 3,
                    'name' => 'Mike Writer',
                    'avatar' => '/images/avatars/mike.jpg'
                ],
                'reputation_score' => 76.3,
                'badge' => 'gold',
                'change' => '-0.5'
            ]
        ];
        
        return new JsonResponse([
            'success' => true,
            'data' => [
                'leaderboard' => $leaderboard,
                'period' => $period,
                'category_filter' => $category,
                'total_sellers' => count($leaderboard)
            ]
        ]);
    }
}
