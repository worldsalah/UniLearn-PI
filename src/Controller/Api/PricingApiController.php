<?php

namespace App\Controller\Api;

use App\Service\PriceIntelligenceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/pricing')]
class PricingApiController extends AbstractController
{
    private PriceIntelligenceService $priceService;

    public function __construct(PriceIntelligenceService $priceService)
    {
        $this->priceService = $priceService;
    }

    /**
     * Analyze product price.
     */
    #[Route('/analyze/{id}', name: 'api_pricing_analyze', methods: ['GET'])]
    public function analyzePrice(int $id): JsonResponse
    {
        // This would typically find the product by ID
        // For demo, we'll return mock analysis
        $mockAnalysis = [
            'current_price' => 150.00,
            'price_label' => [
                'label' => 'Fair Price',
                'color' => '#6b7280',
                'description' => 'Reasonably priced',
                'icon' => '⚖️',
            ],
            'market_position' => [
                'percentile' => 55.0,
                'rank' => 12,
                'total' => 25,
                'position' => 'standard',
            ],
            'market_data' => [
                'total_listings' => 25,
                'min_price' => 50.00,
                'max_price' => 300.00,
                'avg_price' => 145.00,
                'median_price' => 140.00,
                'p25_price' => 80.00,
                'p75_price' => 200.00,
                'price_distribution' => [
                    'budget' => 6,
                    'standard' => 15,
                    'premium' => 4,
                ],
                'price_trend' => 'stable',
            ],
            'recommendations' => [
                [
                    'type' => 'price_optimal',
                    'priority' => 'low',
                    'title' => 'Competitive Pricing',
                    'description' => 'Your price is well-positioned in the market. Consider small adjustments based on demand.',
                    'suggested_price' => 150.00,
                    'potential_impact' => 'Maintain current performance',
                    'confidence' => 90,
                ],
            ],
            'price_analysis' => [
                'is_competitive' => true,
                'is_underpriced' => false,
                'is_overpriced' => false,
                'optimization_potential' => [
                    'potential_change_percent' => 0.0,
                    'optimal_price' => 140.00,
                    'potential_revenue_impact' => '0.0% potential revenue decrease',
                    'confidence' => 85,
                ],
            ],
        ];

        return new JsonResponse([
            'success' => true,
            'data' => [
                'product_id' => $id,
                'analysis' => $mockAnalysis,
                'analyzed_at' => (new \DateTime())->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Get market data for category.
     */
    #[Route('/market-data/{category}', name: 'api_pricing_market_data', methods: ['GET'])]
    public function marketData(string $category): JsonResponse
    {
        $marketData = $this->priceService->getMarketData($category);

        return new JsonResponse([
            'success' => true,
            'data' => [
                'category' => $category,
                'market_data' => $marketData,
                'generated_at' => (new \DateTime())->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Get price recommendations.
     */
    #[Route('/recommendations/{id}', name: 'api_pricing_recommendations', methods: ['GET'])]
    public function priceRecommendations(int $id): JsonResponse
    {
        // Mock recommendations
        $recommendations = [
            [
                'type' => 'price_optimal',
                'priority' => 'low',
                'title' => 'Competitive Pricing',
                'description' => 'Your price is well-positioned in the market. Consider small adjustments based on demand.',
                'suggested_price' => 150.00,
                'potential_impact' => 'Maintain current performance',
                'confidence' => 90,
                'action_items' => [
                    'Monitor competitor pricing weekly',
                    'Consider seasonal adjustments',
                    'Track conversion rates by price point',
                ],
            ],
            [
                'type' => 'dynamic_pricing',
                'priority' => 'low',
                'title' => 'Weekend Demand',
                'description' => 'Consider increasing prices by 10-15% during weekends when demand is higher.',
                'suggested_price' => 165.00,
                'valid_until' => 'Monday 9:00 AM',
                'confidence' => 65,
                'action_items' => [
                    'Implement weekend pricing schedule',
                    'Monitor sales performance during peak times',
                    'A/B test different weekend price points',
                ],
            ],
        ];

        return new JsonResponse([
            'success' => true,
            'data' => [
                'product_id' => $id,
                'recommendations' => $recommendations,
                'total_recommendations' => count($recommendations),
                'generated_at' => (new \DateTime())->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Get price optimization suggestions.
     */
    #[Route('/optimize/{id}', name: 'api_pricing_optimize', methods: ['POST'])]
    public function optimizePrice(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $targetPrice = $data['target_price'] ?? null;
        $strategy = $data['strategy'] ?? 'balanced'; // aggressive, balanced, conservative

        if (null === $targetPrice || '' === $targetPrice) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Target price is required',
            ], 400);
        }

        // Mock optimization analysis
        $optimization = [
            'target_price' => $targetPrice,
            'strategy' => $strategy,
            'analysis' => [
                'market_position' => [
                    'percentile' => 45.0,
                    'position' => 'standard',
                ],
                'expected_impact' => [
                    'visibility_change' => '+5%',
                    'conversion_rate_change' => '-2%',
                    'revenue_change' => '+8%',
                    'competition_level' => 'Medium',
                ],
                'risk_assessment' => [
                    'risk_level' => 'Low',
                    'confidence' => 85,
                    'time_to_see_results' => '2-3 weeks',
                ],
            ],
            'implementation_plan' => [
                'step_1' => [
                    'action' => 'Update listing price',
                    'timing' => 'Immediately',
                    'monitoring' => 'Track daily views and inquiries',
                ],
                'step_2' => [
                    'action' => 'Monitor conversion rates',
                    'timing' => 'First week',
                    'monitoring' => 'Compare with previous conversion data',
                ],
                'step_3' => [
                    'action' => 'Evaluate performance',
                    'timing' => 'After 2 weeks',
                    'monitoring' => 'Assess overall revenue impact',
                ],
            ],
            'alternatives' => [
                [
                    'price' => $targetPrice * 0.95,
                    'expected_impact' => 'Higher conversion, lower revenue per sale',
                    'risk_level' => 'Very Low',
                ],
                [
                    'price' => $targetPrice * 1.05,
                    'expected_impact' => 'Lower conversion, higher revenue per sale',
                    'risk_level' => 'Medium',
                ],
            ],
        ];

        return new JsonResponse([
            'success' => true,
            'data' => [
                'product_id' => $id,
                'optimization' => $optimization,
                'generated_at' => (new \DateTime())->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Get price comparison with competitors.
     */
    #[Route('/compare/{id}', name: 'api_pricing_compare', methods: ['GET'])]
    public function comparePrices(int $id): JsonResponse
    {
        // Mock competitor comparison
        $comparison = [
            'your_price' => 150.00,
            'competitors' => [
                [
                    'id' => 1,
                    'name' => 'Competitor A',
                    'price' => 145.00,
                    'price_difference' => '-3.3%',
                    'rating' => 4.7,
                    'orders' => 89,
                    'market_position' => 'Slightly lower',
                ],
                [
                    'id' => 2,
                    'name' => 'Competitor B',
                    'price' => 165.00,
                    'price_difference' => '+10.0%',
                    'rating' => 4.5,
                    'orders' => 67,
                    'market_position' => 'Higher',
                ],
                [
                    'id' => 3,
                    'name' => 'Competitor C',
                    'price' => 140.00,
                    'price_difference' => '-6.7%',
                    'rating' => 4.8,
                    'orders' => 102,
                    'market_position' => 'Lower',
                ],
            ],
            'summary' => [
                'average_competitor_price' => 150.00,
                'price_vs_average' => '0.0%',
                'market_position' => 'Competitive',
                'recommendation' => 'Your pricing is well-positioned against competitors',
                'opportunity' => 'Consider value-added services to justify premium pricing',
            ],
            'price_distribution' => [
                'budget_range' => ['min' => 100, 'max' => 130, 'count' => 2],
                'standard_range' => ['min' => 130, 'max' => 170, 'count' => 5],
                'premium_range' => ['min' => 170, 'max' => 200, 'count' => 1],
            ],
        ];

        return new JsonResponse([
            'success' => true,
            'data' => [
                'product_id' => $id,
                'comparison' => $comparison,
                'analyzed_at' => (new \DateTime())->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Get pricing insights and trends.
     */
    #[Route('/insights/{category}', name: 'api_pricing_insights', methods: ['GET'])]
    public function pricingInsights(string $category, Request $request): JsonResponse
    {
        $period = $request->query->get('period', 'month'); // week, month, quarter, year

        // Mock insights
        $insights = [
            'category' => $category,
            'period' => $period,
            'trends' => [
                [
                    'metric' => 'Average Price',
                    'current' => 145.00,
                    'previous' => 140.00,
                    'change' => '+3.6%',
                    'trend' => 'increasing',
                    'insight' => 'Prices are trending upward due to increased demand',
                ],
                [
                    'metric' => 'Price Range',
                    'current_min' => 80.00,
                    'current_max' => 220.00,
                    'previous_min' => 75.00,
                    'previous_max' => 200.00,
                    'insight' => 'Price range is expanding, indicating market segmentation',
                ],
                [
                    'metric' => 'Market Competition',
                    'current_listings' => 25,
                    'previous_listings' => 22,
                    'change' => '+13.6%',
                    'insight' => 'Competition is increasing, pricing strategy becomes more important',
                ],
            ],
            'opportunities' => [
                [
                    'type' => 'price_gap',
                    'description' => 'Gap between $100-120 with low competition',
                    'potential' => 'Medium',
                    'action' => 'Consider creating service packages in this range',
                ],
                [
                    'type' => 'premium_segment',
                    'description' => 'High demand for premium services ($200+)',
                    'potential' => 'High',
                    'action' => 'Develop premium offerings with enhanced features',
                ],
            ],
            'recommendations' => [
                'Monitor competitor pricing weekly',
                'Consider dynamic pricing based on demand',
                'Focus on value proposition rather than price competition',
                'Develop tiered pricing strategies',
            ],
            'generated_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ];

        return new JsonResponse([
            'success' => true,
            'data' => $insights,
        ]);
    }

    /**
     * Get pricing calculator.
     */
    #[Route('/calculator', name: 'api_pricing_calculator', methods: ['POST'])]
    public function pricingCalculator(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $category = $data['category'] ?? '';
        $targetRevenue = $data['target_revenue'] ?? null;
        $expectedOrders = $data['expected_orders'] ?? null;
        $costs = $data['costs'] ?? 0;

        if ('' === $category || (null === $targetRevenue && null === $expectedOrders)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Category and either target revenue or expected orders are required',
            ], 400);
        }

        $marketData = $this->priceService->getMarketData($category);
        $recommendedPrice = $marketData['median_price'];

        // Calculate pricing scenarios
        $scenarios = [];

        if (null !== $targetRevenue) {
            $priceForRevenue = ($targetRevenue + $costs) / ($expectedOrders ?? 10);
            $scenarios[] = [
                'name' => 'Revenue Target',
                'price' => round($priceForRevenue, 2),
                'expected_orders' => $expectedOrders ?? 10,
                'expected_revenue' => $targetRevenue,
                'profit_margin' => round((($priceForRevenue - $costs) / $priceForRevenue) * 100, 1),
                'market_position' => $this->calculateMarketPosition($priceForRevenue, $marketData),
            ];
        }

        if (null !== $expectedOrders) {
            $scenarios[] = [
                'name' => 'Market Average',
                'price' => $recommendedPrice,
                'expected_orders' => $expectedOrders,
                'expected_revenue' => $recommendedPrice * $expectedOrders,
                'profit_margin' => round((($recommendedPrice - $costs) / $recommendedPrice) * 100, 1),
                'market_position' => 'Standard',
            ];
        }

        // Budget scenario
        $budgetPrice = $marketData['p25_price'];
        $scenarios[] = [
            'name' => 'Budget Pricing',
            'price' => $budgetPrice,
            'expected_orders' => null !== $expectedOrders ? round($expectedOrders * 1.3) : 13,
            'expected_revenue' => $budgetPrice * (null !== $expectedOrders ? round($expectedOrders * 1.3) : 13),
            'profit_margin' => round((($budgetPrice - $costs) / $budgetPrice) * 100, 1),
            'market_position' => 'Budget',
        ];

        // Premium scenario
        $premiumPrice = $marketData['p75_price'];
        $scenarios[] = [
            'name' => 'Premium Pricing',
            'price' => $premiumPrice,
            'expected_orders' => null !== $expectedOrders ? round($expectedOrders * 0.7) : 7,
            'expected_revenue' => $premiumPrice * (null !== $expectedOrders ? round($expectedOrders * 0.7) : 7),
            'profit_margin' => round((($premiumPrice - $costs) / $premiumPrice) * 100, 1),
            'market_position' => 'Premium',
        ];

        return new JsonResponse([
            'success' => true,
            'data' => [
                'input' => [
                    'category' => $category,
                    'target_revenue' => $targetRevenue,
                    'expected_orders' => $expectedOrders,
                    'costs' => $costs,
                ],
                'market_data' => $marketData,
                'scenarios' => $scenarios,
                'recommendation' => $this->getBestScenario($scenarios),
                'calculated_at' => (new \DateTime())->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Calculate market position for a price.
     */
    private function calculateMarketPosition(float $price, array $marketData): string
    {
        if ($price <= $marketData['p25_price']) {
            return 'Budget';
        }
        if ($price <= $marketData['p75_price']) {
            return 'Standard';
        }

        return 'Premium';
    }

    /**
     * Get the best pricing scenario.
     */
    private function getBestScenario(array $scenarios): array
    {
        // Simple logic: recommend scenario with highest expected revenue
        $best = null;
        $maxRevenue = 0;

        foreach ($scenarios as $scenario) {
            if ($scenario['expected_revenue'] > $maxRevenue) {
                $maxRevenue = $scenario['expected_revenue'];
                $best = $scenario;
            }
        }

        return $best;
    }
}
