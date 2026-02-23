<?php

namespace App\Service;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;

class PriceIntelligenceService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Analyze product price and provide intelligence.
     */
    public function analyzePrice(Product $product): array
    {
        $category = $product->getCategory();
        $currentPrice = $product->getPrice();

        // Get market data for this category
        $marketData = $this->getMarketData($category);

        // Calculate price position
        $pricePosition = $this->calculatePricePosition($currentPrice, $marketData);

        // Get price recommendations
        $recommendations = $this->getPriceRecommendations($currentPrice, $marketData, $product);

        // Determine price label
        $priceLabel = $this->getPriceLabel($currentPrice, $marketData);

        return [
            'current_price' => $currentPrice,
            'price_label' => $priceLabel,
            'market_position' => $pricePosition,
            'market_data' => $marketData,
            'recommendations' => $recommendations,
            'price_analysis' => [
                'is_competitive' => $pricePosition['percentile'] >= 25 && $pricePosition['percentile'] <= 75,
                'is_underpriced' => $pricePosition['percentile'] < 25,
                'is_overpriced' => $pricePosition['percentile'] > 75,
                'optimization_potential' => $this->calculateOptimizationPotential($currentPrice, $marketData),
            ],
        ];
    }

    /**
     * Get market data for a category.
     */
    public function getMarketData(string $category): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        $products = $qb->select('p.price, p.title, p.category')
            ->from(Product::class, 'p')
            ->where('p.category = :category')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('category', $category)
            ->getQuery()
            ->getResult();

        if (empty($products)) {
            return [
                'total_listings' => 0,
                'min_price' => 0,
                'max_price' => 0,
                'avg_price' => 0,
                'median_price' => 0,
                'price_distribution' => [],
                'price_trend' => 'stable',
            ];
        }

        $prices = array_column($products, 'price');
        sort($prices);

        $count = count($prices);
        $sum = array_sum($prices);

        // Calculate distribution
        $distribution = [
            'budget' => 0,    // < 25th percentile
            'standard' => 0,   // 25th-75th percentile
            'premium' => 0,     // > 75th percentile
        ];

        $p25 = $this->percentile($prices, 25);
        $p75 = $this->percentile($prices, 75);

        foreach ($prices as $price) {
            if ($price <= $p25) {
                ++$distribution['budget'];
            } elseif ($price <= $p75) {
                ++$distribution['standard'];
            } else {
                ++$distribution['premium'];
            }
        }

        return [
            'total_listings' => $count,
            'min_price' => min($prices),
            'max_price' => max($prices),
            'avg_price' => round($sum / $count, 2),
            'median_price' => $this->median($prices),
            'p25_price' => $p25,
            'p75_price' => $p75,
            'price_distribution' => $distribution,
            'price_trend' => $this->calculatePriceTrend($category),
        ];
    }

    /**
     * Get price recommendations for a product.
     */
    public function getPriceRecommendations(float $currentPrice, array $marketData, Product $product): array
    {
        $recommendations = [];

        // Fair price range
        $fairRange = [
            'min' => $marketData['p25_price'],
            'max' => $marketData['p75_price'],
            'optimal' => $marketData['median_price'],
        ];

        // Competitive pricing
        if ($currentPrice > $marketData['p75_price']) {
            $recommendations[] = [
                'type' => 'price_reduction',
                'priority' => 'high',
                'title' => 'Consider Price Reduction',
                'description' => 'Your price is in the top 25% of the market. Consider reducing to '.$fairRange['optimal'].' to be more competitive.',
                'suggested_price' => $fairRange['optimal'],
                'potential_impact' => 'Increase visibility by 40-60%',
                'confidence' => 85,
            ];
        } elseif ($currentPrice < $marketData['p25_price']) {
            $recommendations[] = [
                'type' => 'price_increase',
                'priority' => 'medium',
                'title' => 'Opportunity for Price Increase',
                'description' => 'Your price is in the bottom 25% of the market. You could increase to '.$fairRange['optimal'].' without losing competitiveness.',
                'suggested_price' => $fairRange['optimal'],
                'potential_impact' => 'Increase revenue by 20-30%',
                'confidence' => 75,
            ];
        } else {
            $recommendations[] = [
                'type' => 'price_optimal',
                'priority' => 'low',
                'title' => 'Competitive Pricing',
                'description' => 'Your price is well-positioned in the market. Consider small adjustments based on demand.',
                'suggested_price' => $currentPrice,
                'potential_impact' => 'Maintain current performance',
                'confidence' => 90,
            ];
        }

        // Category-specific recommendations
        $categoryRecommendations = $this->getCategorySpecificRecommendations($product, $marketData);
        $recommendations = array_merge($recommendations, $categoryRecommendations);

        // Dynamic pricing suggestions
        $dynamicPricing = $this->getDynamicPricingSuggestions($product, $marketData);
        if (!empty($dynamicPricing)) {
            $recommendations[] = $dynamicPricing;
        }

        return $recommendations;
    }

    /**
     * Get price label for a product.
     */
    public function getPriceLabel(float $price, array $marketData): array
    {
        $percentile = $this->calculatePercentile($price, $marketData);

        if ($percentile <= 20) {
            return [
                'label' => 'Best Value',
                'color' => '#10b981',
                'description' => 'Excellent price compared to market',
                'icon' => '💎',
            ];
        } elseif ($percentile <= 40) {
            return [
                'label' => 'Great Deal',
                'color' => '#3b82f6',
                'description' => 'Very competitive pricing',
                'icon' => '🎯',
            ];
        } elseif ($percentile <= 60) {
            return [
                'label' => 'Fair Price',
                'color' => '#6b7280',
                'description' => 'Reasonably priced',
                'icon' => '⚖️',
            ];
        } elseif ($percentile <= 80) {
            return [
                'label' => 'Premium',
                'color' => '#f59e0b',
                'description' => 'Higher-end pricing',
                'icon' => '⭐',
            ];
        }

        return [
            'label' => 'Above Market',
            'color' => '#ef4444',
            'description' => 'Significantly above average price',
            'icon' => '📈',
        ];
    }

    /**
     * Calculate price position in market.
     */
    private function calculatePricePosition(float $price, array $marketData): array
    {
        if (0 === $marketData['total_listings']) {
            return [
                'percentile' => 50,
                'rank' => 0,
                'total' => 0,
                'position' => 'unknown',
            ];
        }

        $percentile = $this->calculatePercentile($price, $marketData);

        // Determine position
        if ($percentile <= 25) {
            $position = 'budget';
        } elseif ($percentile <= 75) {
            $position = 'standard';
        } else {
            $position = 'premium';
        }

        return [
            'percentile' => round($percentile, 1),
            'rank' => $this->estimateRank($price, $marketData),
            'total' => $marketData['total_listings'],
            'position' => $position,
        ];
    }

    /**
     * Calculate percentile for a price.
     */
    private function calculatePercentile(float $price, array $marketData): float
    {
        if (0 === $marketData['total_listings']) {
            return 50;
        }

        // Simple percentile calculation
        if ($price <= $marketData['min_price']) {
            return 0;
        }
        if ($price >= $marketData['max_price']) {
            return 100;
        }

        $range = $marketData['max_price'] - $marketData['min_price'];
        $position = ($price - $marketData['min_price']) / $range;

        return $position * 100;
    }

    /**
     * Estimate rank for a price.
     */
    private function estimateRank(float $price, array $marketData): int
    {
        // This is a simplified estimation
        // In reality, you'd query the database for exact rank
        $percentile = $this->calculatePercentile($price, $marketData);
        $totalListings = $marketData['total_listings'] ?? 0;

        return max(1, (int) round((1 - $percentile / 100) * $totalListings));
    }

    /**
     * Calculate price trend for a category.
     */
    private function calculatePriceTrend(string $category): string
    {
        // This would analyze historical price data
        // For demo, return random trend
        $trends = ['increasing', 'decreasing', 'stable'];

        return $trends[array_rand($trends)];
    }

    /**
     * Get category-specific recommendations.
     */
    private function getCategorySpecificRecommendations(Product $product, array $marketData): array
    {
        $recommendations = [];
        $category = $product->getCategory();
        $categoryName = $category?->getName() ?? '';

        // Web Development specific
        if ('' !== $categoryName && (false !== strpos(strtolower($categoryName), 'web') || false !== strpos(strtolower($categoryName), 'development'))) {
            if ($product->getPrice() < 50) {
                $recommendations[] = [
                    'type' => 'category_specific',
                    'priority' => 'medium',
                    'title' => 'Web Development Pricing',
                    'description' => 'Web development services typically range from $100-500. Consider increasing your price to reflect expertise.',
                    'suggested_price' => 150,
                    'confidence' => 70,
                ];
            }
        }

        // Design specific
        if ('' !== $categoryName && false !== strpos(strtolower($categoryName), 'design')) {
            if ($product->getPrice() > 200) {
                $recommendations[] = [
                    'type' => 'category_specific',
                    'priority' => 'low',
                    'title' => 'Design Market Rates',
                    'description' => 'Design services are competitive. Ensure your portfolio justifies premium pricing.',
                    'suggested_price' => $marketData['median_price'],
                    'confidence' => 60,
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Get dynamic pricing suggestions.
     */
    private function getDynamicPricingSuggestions(Product $product, array $marketData): ?array
    {
        // This would consider factors like:
        // - Time of day/week
        // - Seasonal demand
        // - Competitor pricing changes
        // - Market demand

        $hour = (int) date('H');
        $dayOfWeek = (int) date('w');

        // Weekend pricing boost
        if ($dayOfWeek >= 5 && $dayOfWeek <= 6) {
            return [
                'type' => 'dynamic_pricing',
                'priority' => 'low',
                'title' => 'Weekend Demand',
                'description' => 'Consider increasing prices by 10-15% during weekends when demand is higher.',
                'suggested_price' => $product->getPrice() * 1.1,
                'valid_until' => 'Monday 9:00 AM',
                'confidence' => 65,
            ];
        }

        return null;
    }

    /**
     * Calculate optimization potential.
     */
    private function calculateOptimizationPotential(float $currentPrice, array $marketData): array
    {
        $optimalPrice = $marketData['median_price'];
        $difference = abs($currentPrice - $optimalPrice);
        $potentialChange = ($difference / $currentPrice) * 100;

        return [
            'potential_change_percent' => round($potentialChange, 1),
            'optimal_price' => $optimalPrice,
            'potential_revenue_impact' => $this->calculateRevenueImpact($currentPrice, $optimalPrice, $marketData),
            'confidence' => $this->calculateOptimizationConfidence($currentPrice, $marketData),
        ];
    }

    /**
     * Calculate potential revenue impact.
     */
    private function calculateRevenueImpact(float $currentPrice, float $optimalPrice, array $marketData): string
    {
        // Simplified calculation
        $priceDifference = $optimalPrice - $currentPrice;
        $impactPercent = ($priceDifference / $currentPrice) * 100;

        if ($impactPercent > 0) {
            return '+'.round($impactPercent, 1).'% potential revenue increase';
        }

        return round($impactPercent, 1).'% potential revenue decrease';
    }

    /**
     * Calculate optimization confidence.
     */
    private function calculateOptimizationConfidence(float $price, array $marketData): int
    {
        $confidence = 50; // Base confidence

        // More listings = higher confidence
        if ($marketData['total_listings'] > 100) {
            $confidence += 20;
        }

        return $confidence;
    }

    /**
     * Calculate median.
     */
    private function median(array $arr): float
    {
        $count = count($arr);
        $middle = floor($count / 2);

        if (0 === $count % 2) {
            return (float) (($arr[$middle - 1] + $arr[$middle]) / 2);
        }

        return (float) $arr[$middle];
    }
}
