<?php

namespace App\Controller\Api;

use App\Entity\Course;
use App\Entity\Job;
use App\Entity\Product;
use App\Entity\User;
use FOS\ElasticaBundle\Finder\TransformedFinder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class UniversalAutocompleteController extends AbstractController
{
    private TransformedFinder $coursesFinder;
    private TransformedFinder $jobsFinder;
    private TransformedFinder $productsFinder;
    private TransformedFinder $usersFinder;

    public function __construct(
        #[Target('courses.finder')]
        TransformedFinder $coursesFinder,
        #[Target('jobs.finder')]
        TransformedFinder $jobsFinder,
        #[Target('products.finder')]
        TransformedFinder $productsFinder,
        #[Target('users.finder')]
        TransformedFinder $usersFinder
    ) {
        $this->coursesFinder = $coursesFinder;
        $this->jobsFinder = $jobsFinder;
        $this->productsFinder = $productsFinder;
        $this->usersFinder = $usersFinder;
    }

    /**
     * Universal autocomplete search across all entities
     */
    #[Route('/autocomplete/universal', name: 'api_autocomplete_universal', methods: ['GET'])]
    public function autocompleteUniversal(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $limit = min(10, max(1, (int) $request->query->get('limit', 5)));

        if (strlen($query) < 2) {
            return new JsonResponse(['suggestions' => []]);
        }

        try {
            $allSuggestions = [];

            // Search courses - use simple wildcard query
            $wildcardQuery = $query . '*';
            $courses = $this->coursesFinder->find($wildcardQuery, ceil($limit / 4));
            
            foreach ($courses as $course) {
                $allSuggestions[] = [
                    'id' => $course->getId(),
                    'title' => $course->getTitle(),
                    'description' => substr($course->getDescription(), 0, 100) . '...',
                    'type' => 'course',
                    'level' => $course->getLevel(),
                    'price' => $course->getPrice(),
                    'url' => $this->generateUrl('app_course_show', ['id' => $course->getId()])
                ];
            }

            // Search jobs - use simple wildcard query
            $jobs = $this->jobsFinder->find($wildcardQuery, ceil($limit / 4));
            
            foreach ($jobs as $job) {
                $allSuggestions[] = [
                    'id' => $job->getId(),
                    'title' => $job->getTitle(),
                    'description' => substr($job->getDescription(), 0, 100) . '...',
                    'type' => 'job',
                    'url' => $this->generateUrl('app_job_show', ['id' => $job->getId()])
                ];
            }

            // Search products - use simple wildcard query
            $products = $this->productsFinder->find($wildcardQuery, ceil($limit / 4));
            
            foreach ($products as $product) {
                $allSuggestions[] = [
                    'id' => $product->getId(),
                    'title' => $product->getTitle(),
                    'description' => substr($product->getDescription(), 0, 100) . '...',
                    'type' => 'product',
                    'price' => $product->getPrice(),
                    'url' => $this->generateUrl('app_product_show', ['slug' => $product->getSlug()])
                ];
            }

            // Search users - use simple wildcard query
            $users = $this->usersFinder->find($wildcardQuery, ceil($limit / 4));
            
            foreach ($users as $user) {
                $allSuggestions[] = [
                    'id' => $user->getId(),
                    'title' => $user->getFullName(),
                    'description' => $user->getEmail(),
                    'type' => 'user',
                    'url' => '#'
                ];
            }

            // Sort by type priority and limit results
            usort($allSuggestions, function($a, $b) {
                $priority = ['course' => 1, 'job' => 2, 'product' => 3, 'user' => 4];
                return $priority[$a['type']] - $priority[$b['type']];
            });

            $allSuggestions = array_slice($allSuggestions, 0, $limit);

            return new JsonResponse([
                'success' => true,
                'suggestions' => $allSuggestions
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
                'suggestions' => []
            ], 500);
        }
    }
}
