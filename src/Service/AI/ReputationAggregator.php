<?php

namespace App\Service\AI;

use App\Entity\Student;
use App\Repository\OrderRepository;

class ReputationAggregator
{
    public function __construct(
        private OrderRepository $orderRepository
    ) {}

    public function aggregateReputation(Student $seller): array
    {
        $score = 0.0;
        $findings = [];
        $suggestions = [];

        // 1. Internal Ratings (40% weight)
        $internalScore = $this->calculateInternalRating($seller);
        $score += $internalScore * 0.40;

        // 2. Transaction History (30% weight)
        $transactionScore = $this->analyzeTransactionHistory($seller);
        $score += $transactionScore * 0.30;
        
        if ($transactionScore < 70) {
            $suggestions[] = [
                'area' => 'Transaction History',
                'suggestion' => 'Complete more successful transactions to build reputation',
                'priority' => 2
            ];
        }

        // 3. Account Verification (20% weight)
        $verificationScore = $this->checkVerificationStatus($seller);
        $score += $verificationScore * 0.20;
        
        if ($verificationScore < 100) {
            $suggestions[] = [
                'area' => 'Account Verification',
                'suggestion' => 'Complete profile verification to increase trust score',
                'priority' => 1
            ];
        }

        // 4. Community Engagement (10% weight)
        $engagementScore = $this->measureCommunityEngagement($seller);
        $score += $engagementScore * 0.10;

        return [
            'score' => min(100, $score),
            'findings' => $findings,
            'suggestions' => $suggestions,
            'details' => [
                'internal_rating' => $internalScore,
                'transaction_history' => $transactionScore,
                'verification_status' => $verificationScore,
                'community_engagement' => $engagementScore,
            ]
        ];
    }

    private function calculateInternalRating(Student $seller): float
    {
        $rating = $seller->getRating();
        
        // Convert 0-5 rating to 0-100 score
        return ($rating / 5.0) * 100;
    }

    private function analyzeTransactionHistory(Student $seller): float
    {
        $products = $seller->getProducts();
        if ($products->isEmpty()) {
            return 50.0; // New seller, neutral score
        }

        $productIds = array_map(fn($p) => $p->getId(), $products->toArray());
        
        // Count completed orders
        $completedOrders = $this->orderRepository->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.product IN (:products)')
            ->andWhere('o.status = :completed')
            ->setParameter('products', $productIds)
            ->setParameter('completed', 'completed')
            ->getQuery()
            ->getSingleScalarResult();

        $totalOrders = $this->orderRepository->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.product IN (:products)')
            ->setParameter('products', $productIds)
            ->getQuery()
            ->getSingleScalarResult();

        if ($totalOrders == 0) {
            return 50.0;
        }

        // Calculate completion rate
        $completionRate = ($completedOrders / $totalOrders) * 100;
        
        // Bonus for volume
        $volumeBonus = min(20, $totalOrders * 2);
        
        return min(100, $completionRate + $volumeBonus);
    }

    private function checkVerificationStatus(Student $seller): float
    {
        $score = 0.0;
        
        // Has bio
        if ($seller->getBio()) {
            $score += 25;
        }
        
        // Has skills listed
        if (!empty($seller->getSkills())) {
            $score += 25;
        }
        
        // Has products
        if ($seller->getProducts()->count() > 0) {
            $score += 25;
        }
        
        // Account age (older = more trusted)
        $accountAge = $seller->getCreatedAt()->diff(new \DateTimeImmutable())->days;
        if ($accountAge > 90) {
            $score += 25;
        } elseif ($accountAge > 30) {
            $score += 15;
        } elseif ($accountAge > 7) {
            $score += 10;
        }
        
        return min(100, $score);
    }

    private function measureCommunityEngagement(Student $seller): float
    {
        $score = 50.0; // Base score
        
        // Number of products (engagement indicator)
        $productCount = $seller->getProducts()->count();
        $score += min(30, $productCount * 5);
        
        // Skills diversity
        $skillCount = count($seller->getSkills());
        $score += min(20, $skillCount * 4);
        
        return min(100, $score);
    }
}
