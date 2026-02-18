<?php

namespace App\Service\AI;

use App\Entity\Product;
use App\Repository\ProductRepository;

class PricingAnalyzer
{
    public function __construct(
        private ProductRepository $productRepository
    ) {}

    public function analyzePricing(Product $product): array
    {
        $score = 100.0;
        $findings = [];
        $suggestions = [];

        // 1. Category Price Benchmarking
        $benchmarkResult = $this->benchmarkAgainstCategory($product);
        $score -= (100 - $benchmarkResult['score']) * 0.40;
        
        if ($benchmarkResult['score'] < 70) {
            $findings[] = [
                'category' => 'pricing',
                'severity' => $benchmarkResult['severity'],
                'message' => $benchmarkResult['message']
            ];
            $suggestions[] = [
                'area' => 'Pricing Strategy',
                'suggestion' => $benchmarkResult['suggestion'],
                'priority' => 1
            ];
        }

        // 2. Outlier Detection
        $outlierResult = $this->detectPriceOutliers($product);
        $score -= (100 - $outlierResult['score']) * 0.30;
        
        if ($outlierResult['is_outlier']) {
            $findings[] = [
                'category' => 'price_outlier',
                'severity' => 'medium',
                'message' => $outlierResult['message']
            ];
        }

        // 3. Value-for-Money Scoring
        $valueScore = $this->calculateValueScore($product);
        $score -= (100 - $valueScore) * 0.30;

        return [
            'score' => max(0, min(100, $score)),
            'findings' => $findings,
            'suggestions' => $suggestions,
            'details' => [
                'benchmark_score' => $benchmarkResult['score'],
                'outlier_detection' => $outlierResult['score'],
                'value_score' => $valueScore,
                'market_average' => $benchmarkResult['market_average'] ?? 0,
                'suggested_price_range' => $benchmarkResult['suggested_range'] ?? [],
            ]
        ];
    }

    private function benchmarkAgainstCategory(Product $product): array
    {
        $category = $product->getCategory();
        $price = $product->getPrice();

        // Get all products in same category
        $categoryProducts = $this->productRepository->createQueryBuilder('p')
            ->where('p.category = :category')
            ->andWhere('p.deletedAt IS NULL')
            ->andWhere('p.id != :currentId')
            ->setParameter('category', $category)
            ->setParameter('currentId', $product->getId())
            ->getQuery()
            ->getResult();

        if (count($categoryProducts) < 3) {
            return [
                'score' => 85.0,
                'message' => 'Insufficient market data for accurate benchmarking',
                'severity' => 'low',
                'suggestion' => 'Price appears reasonable for new category',
                'market_average' => $price
            ];
        }

        // Calculate market statistics
        $prices = array_map(fn($p) => $p->getPrice(), $categoryProducts);
        $avgPrice = array_sum($prices) / count($prices);
        $minPrice = min($prices);
        $maxPrice = max($prices);

        // Calculate standard deviation
        $variance = 0;
        foreach ($prices as $p) {
            $variance += pow($p - $avgPrice, 2);
        }
        $stdDev = sqrt($variance / count($prices));

        // Determine if price is reasonable
        $deviation = abs($price - $avgPrice);
        $deviationPercent = ($deviation / $avgPrice) * 100;

        $score = 100.0;
        $severity = 'low';
        $message = 'Price is within market range';
        $suggestion = 'Current pricing is competitive';

        if ($price < $avgPrice - ($stdDev * 2)) {
            // Significantly underpriced
            $score = 50.0;
            $severity = 'high';
            $message = 'Price is significantly below market average';
            $suggestion = sprintf(
                'Consider increasing price to $%.2f - $%.2f (market average: $%.2f)',
                $avgPrice * 0.8,
                $avgPrice,
                $avgPrice
            );
        } elseif ($price > $avgPrice + ($stdDev * 2)) {
            // Significantly overpriced
            $score = 60.0;
            $severity = 'medium';
            $message = 'Price is significantly above market average';
            $suggestion = sprintf(
                'Consider reducing price to $%.2f - $%.2f (market average: $%.2f)',
                $avgPrice,
                $avgPrice * 1.2,
                $avgPrice
            );
        } elseif ($deviationPercent > 30) {
            $score = 75.0;
            $severity = 'medium';
            $message = sprintf('Price deviates %.1f%% from market average', $deviationPercent);
            $suggestion = sprintf('Market average is $%.2f', $avgPrice);
        }

        return [
            'score' => $score,
            'message' => $message,
            'severity' => $severity,
            'suggestion' => $suggestion,
            'market_average' => $avgPrice,
            'suggested_range' => [
                'min' => max($minPrice, $avgPrice * 0.7),
                'max' => min($maxPrice, $avgPrice * 1.3),
                'optimal' => $avgPrice
            ]
        ];
    }

    private function detectPriceOutliers(Product $product): array
    {
        $price = $product->getPrice();
        
        // Detect suspiciously round numbers (potential lazy pricing)
        if ($price > 100 && $price % 100 === 0) {
            return [
                'score' => 85.0,
                'is_outlier' => true,
                'message' => 'Price is a round number - consider more precise pricing'
            ];
        }

        // Detect extremely low prices (potential scam)
        if ($price < 5) {
            return [
                'score' => 40.0,
                'is_outlier' => true,
                'message' => 'Price is unusually low - may indicate low quality or scam'
            ];
        }

        // Detect extremely high prices
        if ($price > 1000) {
            return [
                'score' => 70.0,
                'is_outlier' => true,
                'message' => 'Price is very high - ensure value justifies premium pricing'
            ];
        }

        return [
            'score' => 100.0,
            'is_outlier' => false,
            'message' => 'Price appears normal'
        ];
    }

    private function calculateValueScore(Product $product): float
    {
        $score = 100.0;
        
        // Value = Quality of description + Seller rating vs Price
        $description = $product->getDescription();
        $descriptionLength = $description !== null ? strlen($description) : 0;
        $sellerRating = $product->getFreelancer()->getRating();
        $price = $product->getPrice();

        // Poor description for high price
        if ($descriptionLength < 100 && $price > 50) {
            $score -= 30;
        }

        // Low seller rating for high price
        if ($sellerRating < 3.0 && $price > 100) {
            $score -= 40;
        }

        // Good value indicators
        if ($descriptionLength > 200 && $sellerRating > 4.0) {
            $score = min(100, $score + 10);
        }

        return max(0, $score);
    }
}
