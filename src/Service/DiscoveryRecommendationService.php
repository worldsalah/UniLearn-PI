<?php

namespace App\Service;

use App\Entity\Product;
use App\Entity\Job;
use App\Entity\User;
use App\Repository\ProductRepository;
use App\Repository\JobRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class DiscoveryRecommendationService
{
    private ProductRepository $productRepository;
    private JobRepository $jobRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        ProductRepository $productRepository,
        JobRepository $jobRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->productRepository = $productRepository;
        $this->jobRepository = $jobRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * Get autocomplete suggestions for search
     */
    public function getAutocompleteSuggestions(string $query, string $type = 'all'): array
    {
        $suggestions = [];
        $query = strtolower(trim($query));
        
        if (strlen($query) < 2) {
            return $suggestions;
        }

        // Search products
        if ($type === 'all' || $type === 'products') {
            $products = $this->productRepository->createQueryBuilder('p')
                ->where('LOWER(p.title) LIKE :query')
                ->orWhere('LOWER(p.description) LIKE :query')
                ->orWhere('LOWER(p.category) LIKE :query')
                ->andWhere('p.deletedAt IS NULL')
                ->setParameter('query', '%' . $query . '%')
                ->setMaxResults(5)
                ->getQuery()
                ->getResult();

            foreach ($products as $product) {
                $suggestions[] = [
                    'id' => $product->getId(),
                    'title' => $product->getTitle() ?? 'Untitled Product',
                    'type' => 'product',
                    'category' => $product->getCategory() ?? 'Uncategorized',
                    'price' => $product->getPrice(),
                    'rating' => $this->calculateProductRating($product),
                    'url' => '/product/' . $product->getId()
                ];
            }
        }

        // Search jobs
        if ($type === 'all' || $type === 'jobs') {
            $jobs = $this->jobRepository->createQueryBuilder('j')
                ->where('LOWER(j.title) LIKE :query')
                ->orWhere('LOWER(j.description) LIKE :query')
                ->andWhere('j.status = :status')
                ->andWhere('j.deletedAt IS NULL')
                ->setParameter('query', '%' . $query . '%')
                ->setParameter('status', 'open')
                ->setMaxResults(5)
                ->getQuery()
                ->getResult();

            foreach ($jobs as $job) {
                $suggestions[] = [
                    'id' => $job->getId(),
                    'title' => $job->getTitle() ?? 'Untitled Job',
                    'type' => 'job',
                    'category' => 'Job Request',
                    'budget' => $job->getBudget(),
                    'url' => '/job/' . $job->getId()
                ];
            }
        }

        // Sort by relevance (title matches first, then description)
        usort($suggestions, function($a, $b) use ($query) {
            $aTitle = strtolower($a['title']);
            $bTitle = strtolower($b['title']);
            
            $aStarts = strpos($aTitle, $query) === 0;
            $bStarts = strpos($bTitle, $query) === 0;
            
            if ($aStarts && !$bStarts) return -1;
            if (!$aStarts && $bStarts) return 1;
            
            return strcmp($aTitle, $bTitle);
        });

        return array_slice($suggestions, 0, 8);
    }

    /**
     * Get personalized recommendations for a user
     */
    public function getPersonalizedRecommendations(\Symfony\Component\Security\Core\User\UserInterface|null $user, int $limit = 10): array
    {
        if (!$user) {
            return $this->getPopularListings($limit);
        }

        $recommendations = [];
        
        // Get user's interests based on their orders and favorites
        $userCategories = $this->getUserInterests($user);
        
        // Get products in user's preferred categories
        if (!empty($userCategories)) {
            $products = $this->productRepository->createQueryBuilder('p')
                ->where('p.category IN (:categories)')
                ->andWhere('p.deletedAt IS NULL')
                ->setParameter('categories', $userCategories)
                ->orderBy('p.createdAt', 'DESC')
                ->setMaxResults($limit / 2)
                ->getQuery()
                ->getResult();

            foreach ($products as $product) {
                $recommendations[] = [
                    'id' => $product->getId(),
                    'title' => $product->getTitle(),
                    'type' => 'product',
                    'category' => $product->getCategory(),
                    'price' => $product->getPrice(),
                    'rating' => $this->calculateProductRating($product),
                    'image' => $product->getImage(),
                    'reason' => 'Based on your interests',
                    'url' => '/product/' . $product->getId()
                ];
            }
        }

        // Get similar jobs based on user's job postings
        $userJobs = $this->jobRepository->findBy(['client' => $user], ['createdAt' => 'DESC'], 5);
        $jobCategories = [];
        
        foreach ($userJobs as $job) {
            $jobCategories[] = $this->extractJobCategory($job->getTitle());
        }

        if (!empty($jobCategories)) {
            $similarJobs = $this->jobRepository->createQueryBuilder('j')
                ->where('j.status = :status')
                ->andWhere('j.deletedAt IS NULL')
                ->andWhere('j.client != :user')
                ->setParameter('status', 'open')
                ->setParameter('user', $user)
                ->orderBy('j.createdAt', 'DESC')
                ->setMaxResults($limit / 2)
                ->getQuery()
                ->getResult();

            foreach ($similarJobs as $job) {
                $recommendations[] = [
                    'id' => $job->getId(),
                    'title' => $job->getTitle(),
                    'type' => 'job',
                    'category' => 'Job Request',
                    'budget' => $job->getBudget(),
                    'reason' => 'Similar to your job requests',
                    'url' => '/job/' . $job->getId()
                ];
            }
        }

        // Fill remaining slots with popular items
        if (count($recommendations) < $limit) {
            $popular = $this->getPopularListings($limit - count($recommendations));
            $recommendations = array_merge($recommendations, $popular);
        }

        return array_slice($recommendations, 0, $limit);
    }

    /**
     * Get trending services
     */
    public function getTrendingServices(int $limit = 10): array
    {
        $trending = [];
        
        // Get products with most views/orders in last 7 days
        $sevenDaysAgo = new \DateTime('-7 days');
        
        $products = $this->productRepository->createQueryBuilder('p')
            ->leftJoin('p.orders', 'o')
            ->where('o.createdAt >= :date OR p.createdAt >= :date')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('date', $sevenDaysAgo)
            ->groupBy('p.id')
            ->orderBy('COUNT(o.id)', 'DESC')
            ->addOrderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        foreach ($products as $product) {
            $trending[] = [
                'id' => $product->getId(),
                'title' => $product->getTitle(),
                'type' => 'product',
                'category' => $product->getCategory(),
                'price' => $product->getPrice(),
                'rating' => $this->calculateProductRating($product),
                'image' => $product->getImage(),
                'trend_score' => $this->calculateTrendScore($product),
                'url' => '/product/' . $product->getId()
            ];
        }

        return $trending;
    }

    /**
     * Get similar listings for a product
     */
    public function getSimilarListings(Product $product, int $limit = 6): array
    {
        $similar = [];
        
        // Get products in same category
        $categoryProducts = $this->productRepository->createQueryBuilder('p')
            ->where('p.category = :category')
            ->andWhere('p.id != :id')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('category', $product->getCategory())
            ->setParameter('id', $product->getId())
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        foreach ($categoryProducts as $similarProduct) {
            $similarity = $this->calculateSimilarity($product, $similarProduct);
            
            $similar[] = [
                'id' => $similarProduct->getId(),
                'title' => $similarProduct->getTitle(),
                'type' => 'product',
                'category' => $similarProduct->getCategory(),
                'price' => $similarProduct->getPrice(),
                'rating' => $this->calculateProductRating($similarProduct),
                'image' => $similarProduct->getImage(),
                'similarity_score' => $similarity,
                'url' => '/product/' . $similarProduct->getId()
            ];
        }

        // Sort by similarity score
        usort($similar, function($a, $b) {
            return $b['similarity_score'] <=> $a['similarity_score'];
        });

        return array_slice($similar, 0, $limit);
    }

    /**
     * Get popular listings (for non-logged in users)
     */
    private function getPopularListings(int $limit): array
    {
        $popular = [];
        
        $products = $this->productRepository->createQueryBuilder('p')
            ->where('p.deletedAt IS NULL')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        foreach ($products as $product) {
            $popular[] = [
                'id' => $product->getId(),
                'title' => $product->getTitle(),
                'type' => 'product',
                'category' => $product->getCategory(),
                'price' => $product->getPrice(),
                'rating' => $this->calculateProductRating($product),
                'image' => $product->getImage(),
                'reason' => 'Popular on marketplace',
                'url' => '/product/' . $product->getId()
            ];
        }

        return $popular;
    }

    /**
     * Get user's interests based on their activity
     */
    private function getUserInterests(User $user): array
    {
        $categories = [];
        
        // Get categories from user's orders
        $orders = $this->entityManager->createQuery('
            SELECT DISTINCT p.category 
            FROM App\Entity\Order o 
            JOIN o.items oi 
            JOIN oi.product p 
            WHERE o.user = :user
        ')
        ->setParameter('user', $user)
        ->getResult();

        foreach ($orders as $order) {
            $categories[] = $order['category'];
        }

        return array_unique($categories);
    }

    /**
     * Calculate product rating
     */
    private function calculateProductRating(Product $product): float
    {
        // This would typically calculate from reviews
        // For now, return a random rating for demo
        return round(mt_rand(30, 50) / 10, 1);
    }

    /**
     * Calculate trend score for a product
     */
    private function calculateTrendScore(Product $product): int
    {
        // This would calculate based on recent activity
        // For now, return a random score for demo
        return mt_rand(60, 95);
    }

    /**
     * Calculate similarity between two products
     */
    private function calculateSimilarity(Product $product1, Product $product2): int
    {
        $score = 0;
        
        // Same category
        if ($product1->getCategory() === $product2->getCategory()) {
            $score += 50;
        }
        
        // Similar price range
        $priceDiff = abs($product1->getPrice() - $product2->getPrice());
        if ($priceDiff < 20) {
            $score += 30;
        } elseif ($priceDiff < 50) {
            $score += 15;
        }
        
        // Similar title keywords
        $title1 = strtolower($product1->getTitle());
        $title2 = strtolower($product2->getTitle());
        $commonWords = array_intersect(explode(' ', $title1), explode(' ', $title2));
        $score += count($commonWords) * 10;
        
        return min($score, 100);
    }

    /**
     * Extract category from job title
     */
    private function extractJobCategory(string $title): string
    {
        $categories = [
            'web development' => ['web', 'website', 'development', 'developer', 'php', 'javascript', 'html'],
            'design' => ['design', 'logo', 'graphic', 'ui', 'ux', 'designer'],
            'marketing' => ['marketing', 'seo', 'social media', 'advertising'],
            'writing' => ['writing', 'content', 'blog', 'article', 'copy'],
            'mobile' => ['mobile', 'app', 'android', 'ios', 'application']
        ];

        $title = strtolower($title);
        
        foreach ($categories as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($title, $keyword) !== false) {
                    return $category;
                }
            }
        }
        
        return 'general';
    }
}
