<?php

namespace App\Controller\Api;

use App\Service\DiscoveryRecommendationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

#[Route('/api/discovery')]
class DiscoveryApiController extends AbstractController
{
    private DiscoveryRecommendationService $discoveryService;

    public function __construct(DiscoveryRecommendationService $discoveryService)
    {
        $this->discoveryService = $discoveryService;
    }

    /**
     * Get autocomplete suggestions
     */
    #[Route('/autocomplete', name: 'api_discovery_autocomplete', methods: ['GET'])]
    public function autocomplete(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $type = $request->query->get('type', 'all'); // all, products, jobs
        
        $suggestions = $this->discoveryService->getAutocompleteSuggestions($query, $type);
        
        return new JsonResponse([
            'success' => true,
            'data' => [
                'query' => $query,
                'type' => $type,
                'suggestions' => $suggestions,
                'count' => count($suggestions)
            ]
        ]);
    }

    /**
     * Get personalized recommendations
     */
    #[Route('/recommendations', name: 'api_discovery_recommendations', methods: ['GET'])]
    public function recommendations(Request $request): JsonResponse
    {
        $limit = min($request->query->getInt('limit', 10), 50);
        $user = $this->getUser();
        
        $recommendations = $this->discoveryService->getPersonalizedRecommendations($user, $limit);
        
        return new JsonResponse([
            'success' => true,
            'data' => [
                'user_id' => $user ? $user->getId() : null,
                'recommendations' => $recommendations,
                'count' => count($recommendations),
                'personalized' => $user !== null
            ]
        ]);
    }

    /**
     * Get trending services
     */
    #[Route('/trending', name: 'api_discovery_trending', methods: ['GET'])]
    public function trending(Request $request): JsonResponse
    {
        $limit = min($request->query->getInt('limit', 10), 50);
        $category = $request->query->get('category');
        
        $trending = $this->discoveryService->getTrendingServices($limit);
        
        // Filter by category if specified
        if ($category) {
            $trending = array_filter($trending, function($item) use ($category) {
                return strtolower($item['category']) === strtolower($category);
            });
        }
        
        return new JsonResponse([
            'success' => true,
            'data' => [
                'trending' => array_values($trending),
                'count' => count($trending),
                'category_filter' => $category
            ]
        ]);
    }

    /**
     * Get similar listings
     */
    #[Route('/similar/{id}', name: 'api_discovery_similar', methods: ['GET'])]
    public function similar(int $id, Request $request): JsonResponse
    {
        $limit = min($request->query->getInt('limit', 6), 20);
        
        // This would typically find the product by ID
        // For demo, we'll return empty similar items
        $similar = [];
        
        return new JsonResponse([
            'success' => true,
            'data' => [
                'product_id' => $id,
                'similar_items' => $similar,
                'count' => count($similar)
            ]
        ]);
    }

    /**
     * Search with advanced ranking
     */
    #[Route('/search', name: 'api_discovery_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $type = $request->query->get('type', 'all');
        $sort = $request->query->get('sort', 'relevance'); // relevance, popularity, rating, price_low, price_high
        $page = max($request->query->getInt('page', 1), 1);
        $limit = min($request->query->getInt('limit', 20), 100);
        
        if (strlen($query) < 2) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Query too short. Minimum 2 characters required.'
            ], 400);
        }
        
        $suggestions = $this->discoveryService->getAutocompleteSuggestions($query, $type);
        
        // Apply sorting
        $suggestions = $this->applySorting($suggestions, $sort);
        
        // Apply pagination
        $offset = ($page - 1) * $limit;
        $paginatedResults = array_slice($suggestions, $offset, $limit);
        
        return new JsonResponse([
            'success' => true,
            'data' => [
                'query' => $query,
                'type' => $type,
                'sort' => $sort,
                'results' => $paginatedResults,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => count($suggestions),
                    'pages' => ceil(count($suggestions) / $limit),
                    'has_next' => $page * $limit < count($suggestions),
                    'has_prev' => $page > 1
                ]
            ]
        ]);
    }

    /**
     * Get popular categories
     */
    #[Route('/categories', name: 'api_discovery_categories', methods: ['GET'])]
    public function categories(): JsonResponse
    {
        // This would typically fetch from database
        $categories = [
            ['name' => 'Web Development', 'count' => 245, 'icon' => '💻'],
            ['name' => 'Design', 'count' => 189, 'icon' => '🎨'],
            ['name' => 'Marketing', 'count' => 156, 'icon' => '📱'],
            ['name' => 'Writing', 'count' => 134, 'icon' => '✍️'],
            ['name' => 'Mobile Development', 'count' => 98, 'icon' => '📲'],
            ['name' => 'Data Science', 'count' => 87, 'icon' => '📊'],
            ['name' => 'Consulting', 'count' => 76, 'icon' => '💼'],
            ['name' => 'Video & Animation', 'count' => 65, 'icon' => '🎬']
        ];
        
        return new JsonResponse([
            'success' => true,
            'data' => [
                'categories' => $categories,
                'total_categories' => count($categories)
            ]
        ]);
    }

    /**
     * Apply sorting to search results
     */
    private function applySorting(array $results, string $sort): array
    {
        switch ($sort) {
            case 'popularity':
                // Sort by some popularity metric (would need to be implemented)
                usort($results, function($a, $b) {
                    return ($b['rating'] ?? 0) <=> ($a['rating'] ?? 0);
                });
                break;
                
            case 'rating':
                usort($results, function($a, $b) {
                    return ($b['rating'] ?? 0) <=> ($a['rating'] ?? 0);
                });
                break;
                
            case 'price_low':
                usort($results, function($a, $b) {
                    return ($a['price'] ?? PHP_INT_MAX) <=> ($b['price'] ?? PHP_INT_MAX);
                });
                break;
                
            case 'price_high':
                usort($results, function($a, $b) {
                    return ($b['price'] ?? 0) <=> ($a['price'] ?? 0);
                });
                break;
                
            case 'relevance':
            default:
                // Already sorted by relevance in the service
                break;
        }
        
        return $results;
    }
}
