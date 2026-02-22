<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\User;
use App\Repository\OrderRepository;
use Symfony\Component\Security\Core\User\UserInterface;

class SellerReputationService
{
    private OrderRepository $orderRepository;

    public function __construct(
        OrderRepository $orderRepository,
    ) {
        $this->orderRepository = $orderRepository;
    }

    /**
     * Calculate comprehensive seller reputation score.
     */
    public function calculateReputationScore(\Symfony\Component\Security\Core\User\UserInterface $seller): array
    {
        $stats = $this->getSellerStats($seller);

        $score = 0;
        $factors = [];

        // Order completion rate (40% weight)
        $completionRate = $stats['total_orders'] > 0 ? ($stats['completed_orders'] / $stats['total_orders']) * 100 : 0;
        $score += $completionRate * 0.4;
        $factors['completion_rate'] = [
            'value' => round($completionRate, 1),
            'weight' => 40,
            'score' => round($completionRate * 0.4, 1),
        ];

        // Average response time (25% weight)
        $responseScore = $this->calculateResponseScore($stats['avg_response_time']);
        $score += $responseScore * 0.25;
        $factors['response_time'] = [
            'value' => $stats['avg_response_time'],
            'weight' => 25,
            'score' => round($responseScore * 0.25, 1),
        ];

        // Review sentiment (25% weight)
        $reviewScore = $this->calculateReviewScore($stats['avg_rating'], $stats['total_reviews']);
        $score += $reviewScore * 0.25;
        $factors['reviews'] = [
            'rating' => $stats['avg_rating'],
            'count' => $stats['total_reviews'],
            'weight' => 25,
            'score' => round($reviewScore * 0.25, 1),
        ];

        // Account age and verification (10% weight)
        $accountScore = $this->calculateAccountScore($seller);
        $score += $accountScore * 0.1;
        $factors['account'] = [
            'age_days' => $stats['account_age_days'],
            'verified' => $stats['is_verified'],
            'weight' => 10,
            'score' => round($accountScore * 0.1, 1),
        ];

        return [
            'overall_score' => round($score, 1),
            'badge' => $this->getBadge($score),
            'level' => $this->getLevel($score),
            'factors' => $factors,
            'stats' => $stats,
        ];
    }

    /**
     * Get seller badge based on score.
     */
    public function getBadge(float $score): string
    {
        if ($score >= 90) {
            return 'elite';
        }
        if ($score >= 75) {
            return 'gold';
        }
        if ($score >= 60) {
            return 'silver';
        }
        if ($score >= 40) {
            return 'bronze';
        }

        return 'new';
    }

    /**
     * Get seller level with benefits.
     */
    public function getLevel(float $score): array
    {
        $levels = [
            'elite' => [
                'name' => 'Elite Seller',
                'color' => '#6366f1',
                'benefits' => ['Priority support', 'Featured listings', 'Lower fees', 'Advanced analytics'],
                'requirements' => ['90+ reputation score', '50+ completed orders', '4.8+ average rating'],
            ],
            'gold' => [
                'name' => 'Gold Seller',
                'color' => '#f59e0b',
                'benefits' => ['Enhanced visibility', 'Standard support', 'Reduced fees', 'Basic analytics'],
                'requirements' => ['75-89 reputation score', '25+ completed orders', '4.5+ average rating'],
            ],
            'silver' => [
                'name' => 'Silver Seller',
                'color' => '#6b7280',
                'benefits' => ['Improved visibility', 'Email support', 'Standard fees'],
                'requirements' => ['60-74 reputation score', '10+ completed orders', '4.0+ average rating'],
            ],
            'bronze' => [
                'name' => 'Bronze Seller',
                'color' => '#92400e',
                'benefits' => ['Basic visibility', 'Community support'],
                'requirements' => ['40-59 reputation score', '5+ completed orders', '3.5+ average rating'],
            ],
            'new' => [
                'name' => 'New Seller',
                'color' => '#d1d5db',
                'benefits' => ['Getting started support'],
                'requirements' => ['Build your reputation with first few orders'],
            ],
        ];

        return $levels[$this->getBadge($score)];
    }

    /**
     * Get comprehensive seller statistics.
     */
    public function getSellerStats(\Symfony\Component\Security\Core\User\UserInterface $seller): array
    {
        // Total orders
        $totalOrders = $this->orderRepository->count(['freelancer' => $seller]);

        // Completed orders
        $completedOrders = $this->orderRepository->count([
            'freelancer' => $seller,
            'status' => 'completed',
        ]);

        // Cancelled/failed orders
        $failedOrders = $this->orderRepository->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.freelancer = :seller')
            ->andWhere('o.status IN (:statuses)')
            ->setParameter('seller', $seller)
            ->setParameter('statuses', ['cancelled', 'refunded', 'disputed'])
            ->getQuery()
            ->getSingleScalarResult();

        // Average response time (in hours)
        $avgResponseTime = $this->calculateAverageResponseTime($seller);

        // Review statistics (calculated from orders)
        $reviewStats = $this->getReviewStats($seller);

        // Account age
        $accountAge = $seller->getCreatedAt();
        $accountAgeDays = $accountAge ? (new \DateTime())->diff($accountAge)->days : 0;

        // Verification status
        $isVerified = $seller->isVerified() ?? false;

        return [
            'total_orders' => $totalOrders,
            'completed_orders' => $completedOrders,
            'failed_orders' => $failedOrders,
            'completion_rate' => $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100, 1) : 0,
            'avg_response_time' => $avgResponseTime,
            'avg_rating' => $reviewStats['avg_rating'],
            'total_reviews' => $reviewStats['total_reviews'],
            'account_age_days' => $accountAgeDays,
            'is_verified' => $isVerified,
            'dispute_rate' => $totalOrders > 0 ? round(($failedOrders / $totalOrders) * 100, 1) : 0,
        ];
    }

    /**
     * Get seller performance metrics for dashboard.
     */
    public function getPerformanceMetrics(\Symfony\Component\Security\Core\User\UserInterface $seller): array
    {
        $stats = $this->getSellerStats($seller);
        $reputation = $this->calculateReputationScore($seller);

        return [
            'trust_score' => $reputation['overall_score'],
            'badge_info' => $reputation['level'],
            'performance_indicators' => [
                [
                    'metric' => 'Order Completion',
                    'value' => $stats['completion_rate'].'%',
                    'status' => $stats['completion_rate'] >= 90 ? 'excellent' : ($stats['completion_rate'] >= 75 ? 'good' : 'needs_improvement'),
                    'trend' => $this->getCompletionTrend($seller),
                ],
                [
                    'metric' => 'Response Time',
                    'value' => $this->formatResponseTime($stats['avg_response_time']),
                    'status' => $stats['avg_response_time'] <= 2 ? 'excellent' : ($stats['avg_response_time'] <= 6 ? 'good' : 'needs_improvement'),
                    'trend' => $this->getResponseTrend($seller),
                ],
                [
                    'metric' => 'Customer Rating',
                    'value' => $stats['avg_rating'].'/5.0',
                    'status' => $stats['avg_rating'] >= 4.5 ? 'excellent' : ($stats['avg_rating'] >= 4.0 ? 'good' : 'needs_improvement'),
                    'trend' => $this->getRatingTrend($seller),
                ],
                [
                    'metric' => 'Dispute Rate',
                    'value' => $stats['dispute_rate'].'%',
                    'status' => $stats['dispute_rate'] <= 2 ? 'excellent' : ($stats['dispute_rate'] <= 5 ? 'good' : 'needs_improvement'),
                    'trend' => $this->getDisputeTrend($seller),
                ],
            ],
            'recent_achievements' => $this->getRecentAchievements($seller, $reputation),
            'improvement_tips' => $this->getImprovementTips($reputation),
        ];
    }

    /**
     * Calculate response time score.
     */
    private function calculateResponseScore(float $responseTime): float
    {
        if ($responseTime <= 1) {
            return 100;
        }
        if ($responseTime <= 2) {
            return 90;
        }
        if ($responseTime <= 4) {
            return 75;
        }
        if ($responseTime <= 8) {
            return 60;
        }
        if ($responseTime <= 24) {
            return 40;
        }

        return 20;
    }

    /**
     * Calculate review score based on rating and quantity.
     */
    private function calculateReviewScore(float $avgRating, int $totalReviews): float
    {
        if (0 === $totalReviews) {
            return 50;
        } // Neutral score for no reviews

        $ratingScore = ($avgRating / 5) * 100;

        // Bonus for having more reviews
        $reviewBonus = min($totalReviews * 2, 20); // Max 20 points bonus

        return min($ratingScore + $reviewBonus, 100);
    }

    /**
     * Calculate account score based on age and verification.
     */
    private function calculateAccountScore(UserInterface $seller): float
    {
        $score = 0;

        // Account age score
        $ageDays = $seller->getCreatedAt() !== null ? (new \DateTime())->diff($seller->getCreatedAt())->days : 0;
        if ($ageDays >= 365) {
            $score += 50;
        } elseif ($ageDays >= 180) {
            $score += 40;
        } elseif ($ageDays >= 90) {
            $score += 30;
        } elseif ($ageDays >= 30) {
            $score += 20;
        } else {
            $score += 10;
        }

        // Verification bonus
        if (true === $seller->isVerified()) {
            $score += 50;
        }

        return $score;
    }

    /**
     * Calculate average response time.
     */
    private function calculateAverageResponseTime(UserInterface $seller): float
    {
        // This would typically calculate from message timestamps
        // For demo, return a realistic value
        return mt_rand(1, 12);
    }

    /**
     * Get review statistics (calculated from completed orders).
     */
    private function getReviewStats(UserInterface $seller): array
    {
        // Get completed orders as a proxy for reviews
        $completedOrders = $this->orderRepository->findBy([
            'freelancer' => $seller,
            'status' => 'completed',
        ]);

        $totalReviews = count($completedOrders);

        // Calculate average rating from completed orders
        // This would typically come from actual reviews, but we'll simulate it
        $totalRating = 0;
        foreach ($completedOrders as $order) {
            // Simulate rating based on order completion
            $totalRating += mt_rand(35, 50) / 10; // Random rating between 3.5 and 5.0
        }

        return [
            'total_reviews' => $totalReviews,
            'avg_rating' => $totalReviews > 0 ? round($totalRating / $totalReviews, 1) : 0,
        ];
    }

    /**
     * Format response time for display.
     */
    private function formatResponseTime(float $hours): string
    {
        if ($hours < 1) {
            return round($hours * 60).' min';
        }

        return round($hours, 1).' hours';
    }

    /**
     * Get completion trend.
     */
    private function getCompletionTrend(UserInterface $seller): string
    {
        // This would calculate from historical data
        // For demo, return random trend
        $trends = ['up', 'down', 'stable'];

        return $trends[array_rand($trends)];
    }

    /**
     * Get response trend.
     */
    private function getResponseTrend(UserInterface $seller): string
    {
        $trends = ['up', 'down', 'stable'];

        return $trends[array_rand($trends)];
    }

    /**
     * Get rating trend.
     */
    private function getRatingTrend(UserInterface $seller): string
    {
        $trends = ['up', 'down', 'stable'];

        return $trends[array_rand($trends)];
    }

    /**
     * Get dispute trend.
     */
    private function getDisputeTrend(UserInterface $seller): string
    {
        $trends = ['up', 'down', 'stable'];

        return $trends[array_rand($trends)];
    }

    /**
     * Get recent achievements.
     */
    private function getRecentAchievements(UserInterface $seller, array $reputation): array
    {
        $achievements = [];

        if ($reputation['overall_score'] >= 90) {
            $achievements[] = [
                'title' => 'Elite Status Reached',
                'description' => 'Congratulations! You\'ve achieved Elite seller status',
                'icon' => '🏆',
                'date' => (new \DateTime())->format('Y-m-d'),
            ];
        }

        if ($reputation['stats']['completed_orders'] >= 50) {
            $achievements[] = [
                'title' => '50+ Orders Completed',
                'description' => 'Milestone: 50 successful orders delivered',
                'icon' => '📦',
                'date' => (new \DateTime())->format('Y-m-d'),
            ];
        }

        return $achievements;
    }

    /**
     * Get improvement tips.
     */
    private function getImprovementTips(array $reputation): array
    {
        $tips = [];

        if ($reputation['factors']['completion_rate']['value'] < 90) {
            $tips[] = [
                'area' => 'Order Completion',
                'tip' => 'Focus on completing more orders to improve your completion rate',
                'priority' => 'high',
            ];
        }

        if ($reputation['factors']['response_time']['value'] > 4) {
            $tips[] = [
                'area' => 'Response Time',
                'tip' => 'Respond to messages faster to improve customer satisfaction',
                'priority' => 'medium',
            ];
        }

        if ($reputation['factors']['reviews']['count'] < 10) {
            $tips[] = [
                'area' => 'Reviews',
                'tip' => 'Encourage satisfied customers to leave reviews',
                'priority' => 'low',
            ];
        }

        return $tips;
    }
}
