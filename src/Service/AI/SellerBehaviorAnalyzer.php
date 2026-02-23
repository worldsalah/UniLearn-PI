<?php

namespace App\Service\AI;

use App\Entity\User;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

class SellerBehaviorAnalyzer
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private OrderRepository $orderRepository,
        private ProductRepository $productRepository,
    ) {
    }

    public function analyzeBehavior(User $seller): array
    {
        $score = 100.0;
        $findings = [];
        $suggestions = [];

        // 1. Transaction Velocity Analysis
        $velocityScore = $this->analyzeTransactionVelocity($seller);
        $score -= (100 - $velocityScore) * 0.25;

        if ($velocityScore < 70) {
            $findings[] = [
                'category' => 'transaction_velocity',
                'severity' => 'medium',
                'message' => 'Unusual transaction velocity detected',
            ];
            $suggestions[] = [
                'area' => 'Transaction Pattern',
                'suggestion' => 'Maintain consistent service delivery pace',
                'priority' => 2,
            ];
        }

        // 2. Response Time Analysis
        $responseScore = $this->analyzeResponseTime($seller);
        $score -= (100 - $responseScore) * 0.20;

        if ($responseScore < 80) {
            $suggestions[] = [
                'area' => 'Communication',
                'suggestion' => 'Improve response time to buyer inquiries',
                'priority' => 1,
            ];
        }

        // 3. Cancellation/Dispute Rate
        $cancellationScore = $this->analyzeCancellationRate($seller);
        $score -= (100 - $cancellationScore) * 0.30;

        if ($cancellationScore < 70) {
            $findings[] = [
                'category' => 'cancellation_rate',
                'severity' => 'high',
                'message' => 'High cancellation or dispute rate detected',
            ];
            $suggestions[] = [
                'area' => 'Service Quality',
                'suggestion' => 'Review and improve service delivery process to reduce cancellations',
                'priority' => 1,
            ];
        }

        // 4. Account Age and Activity Consistency
        $consistencyScore = $this->analyzeActivityConsistency($seller);
        $score -= (100 - $consistencyScore) * 0.15;

        // 5. Anomaly Detection
        $anomalyScore = $this->detectAnomalies($seller);
        $score -= (100 - $anomalyScore) * 0.10;

        if ($anomalyScore < 60) {
            $findings[] = [
                'category' => 'anomaly',
                'severity' => 'high',
                'message' => 'Suspicious activity patterns detected',
            ];
        }

        return [
            'score' => max(0, min(100, $score)),
            'findings' => $findings,
            'suggestions' => $suggestions,
            'details' => [
                'transaction_velocity' => $velocityScore,
                'response_time' => $responseScore,
                'cancellation_rate' => $cancellationScore,
                'activity_consistency' => $consistencyScore,
                'anomaly_detection' => $anomalyScore,
            ],
        ];
    }

    private function analyzeTransactionVelocity(User $seller): float
    {
        // Get orders for this seller's products
        $products = $seller->getProducts();
        if ($products->isEmpty()) {
            return 100.0; // New seller, no penalty
        }

        $productIds = array_map(fn ($p) => $p->getId(), $products->toArray());

        // Count orders in last 7 days vs last 30 days
        try {
            $recentOrders = $this->orderRepository->createQueryBuilder('o')
                ->select('COUNT(o.id)')
                ->where('o.product IN (:products)')
                ->andWhere('o.createdAt >= :sevenDaysAgo')
                ->setParameter('products', $productIds)
                ->setParameter('sevenDaysAgo', new \DateTimeImmutable('-7 days'))
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Exception $e) {
            $recentOrders = 0;
        }

        try {
            $monthlyOrders = $this->orderRepository->createQueryBuilder('o')
                ->select('COUNT(o.id)')
                ->where('o.product IN (:products)')
                ->andWhere('o.createdAt >= :thirtyDaysAgo')
                ->setParameter('products', $productIds)
                ->setParameter('thirtyDaysAgo', new \DateTimeImmutable('-30 days'))
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Exception $e) {
            $monthlyOrders = 0;
        }

        // Detect sudden spikes (more than 3x normal rate)
        $normalRate = $monthlyOrders > 0 ? $monthlyOrders / 4 : 0; // Weekly average
        if ($recentOrders > $normalRate * 3 && $recentOrders > 10) {
            return 60.0; // Suspicious spike
        }

        return 100.0;
    }

    private function analyzeResponseTime(User $seller): float
    {
        // Simulated: In production, track message response times
        // For now, return high score for established sellers
        $createdAt = $seller->getCreatedAt();
        if (null === $createdAt) {
            return 85.0;
        }
        $accountAge = $createdAt->diff(new \DateTimeImmutable())->days;

        if ($accountAge < 7) {
            return 85.0; // New sellers get benefit of doubt
        }

        return 95.0; // Assume good response time
    }

    private function analyzeCancellationRate(User $seller): float
    {
        $products = $seller->getProducts();
        if ($products->isEmpty()) {
            return 100.0;
        }

        $productIds = array_map(fn ($p) => $p->getId(), $products->toArray());

        try {
            $totalOrders = $this->orderRepository->createQueryBuilder('o')
                ->select('COUNT(o.id)')
                ->where('o.product IN (:products)')
                ->setParameter('products', $productIds)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Exception $e) {
            $totalOrders = 0;
        }

        if (0 === $totalOrders) {
            return 100.0;
        }

        try {
            $cancelledOrders = $this->orderRepository->createQueryBuilder('o')
                ->select('COUNT(o.id)')
                ->where('o.product IN (:products)')
                ->andWhere('o.status = :cancelled')
                ->setParameter('products', $productIds)
                ->setParameter('cancelled', 'cancelled')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Exception $e) {
            $cancelledOrders = 0;
        }

        $cancellationRate = $totalOrders > 0 ? ($cancelledOrders / $totalOrders) * 100 : 0;

        // Score decreases with higher cancellation rate
        return max(0, 100 - ($cancellationRate * 5));
    }

    private function analyzeActivityConsistency(User $seller): float
    {
        $createdAt = $seller->getCreatedAt();
        if (null === $createdAt) {
            return 70.0;
        }
        $accountAge = $createdAt->diff(new \DateTimeImmutable())->days;

        // New accounts (< 30 days) get lower consistency score
        if ($accountAge < 30) {
            return 70.0;
        }

        // Established accounts get higher score
        if ($accountAge > 180) {
            return 95.0;
        }

        return 85.0;
    }

    private function detectAnomalies(User $seller): float
    {
        $score = 100.0;

        // Check for suspicious patterns
        $products = $seller->getProducts();

        // Too many products created in short time
        if ($products->count() > 20) {
            $createdAt = $seller->getCreatedAt();
            if (null !== $createdAt) {
                $accountAge = $createdAt->diff(new \DateTimeImmutable())->days;
                if ($accountAge < 30) {
                    $score -= 40; // Suspicious: too many products too quickly
                }
            }
        }

        // All products have same/similar prices (potential scam)
        if ($products->count() > 3) {
            $prices = array_map(fn ($p) => $p->getPrice(), $products->toArray());
            $avgPrice = array_sum($prices) / count($prices);
            $variance = 0;
            foreach ($prices as $price) {
                $variance += pow($price - $avgPrice, 2);
            }
            $variance /= count($prices);

            if ($variance < 10 && $avgPrice > 50) {
                $score -= 20; // Suspicious: all products same price
            }
        }

        return max(0, $score);
    }
}
