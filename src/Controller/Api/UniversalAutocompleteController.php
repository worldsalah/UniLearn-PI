<?php

namespace App\Controller\Api;

use App\Entity\Course;
use App\Entity\Job;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\CourseRepository;
use App\Repository\JobRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class UniversalAutocompleteController extends AbstractController
{
    private CourseRepository $courseRepository;
    private JobRepository $jobRepository;
    private ProductRepository $productRepository;
    private UserRepository $userRepository;

    public function __construct(
        CourseRepository $courseRepository,
        JobRepository $jobRepository,
        ProductRepository $productRepository,
        UserRepository $userRepository
    ) {
        $this->courseRepository = $courseRepository;
        $this->jobRepository = $jobRepository;
        $this->productRepository = $productRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Universal autocomplete search across all entities
     */
    #[Route('/autocomplete/universal', name: 'api_autocomplete_universal', methods: ['GET'])]
    public function autocompleteUniversal(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $limit = min(10, max(1, (int) $request->query->get('limit', 5)));

        if (strlen((string) $query) < 2) {
            return new JsonResponse(['suggestions' => []]);
        }

        try {
            $allSuggestions = [];

            // Search courses - database fallback
            $courses = $this->courseRepository->createQueryBuilder('c')
                ->where('c.title LIKE :query')
                ->andWhere('c.status = :status')
                ->setParameter('query', '%' . $query . '%')
                ->setParameter('status', 'live')
                ->setMaxResults((int) ceil($limit / 4))
                ->getQuery()
                ->getResult();
            
            foreach ($courses as $course) {
                $allSuggestions[] = [
                    'id' => $course->getId(),
                    'title' => $course->getTitle(),
                    'description' => substr($course->getDescription() ?? '', 0, 100) . '...',
                    'type' => 'course',
                    'level' => $course->getLevel(),
                    'price' => $course->getPrice(),
                    'url' => $this->generateUrl('app_course_show', ['id' => $course->getId()])
                ];
            }

            // Search jobs - database fallback
            $jobs = $this->jobRepository->createQueryBuilder('j')
                ->where('j.title LIKE :query OR j.description LIKE :query')
                ->setParameter('query', '%' . $query . '%')
                ->setMaxResults((int) ceil($limit / 4))
                ->getQuery()
                ->getResult();
            
            foreach ($jobs as $job) {
                $allSuggestions[] = [
                    'id' => $job->getId(),
                    'title' => $job->getTitle(),
                    'description' => substr($job->getDescription() ?? '', 0, 100) . '...',
                    'type' => 'job',
                    'url' => $this->generateUrl('app_job_show', ['id' => $job->getId()])
                ];
            }

            // Search products - database fallback
            $products = $this->productRepository->createQueryBuilder('p')
                ->where('p.title LIKE :query OR p.description LIKE :query')
                ->setParameter('query', '%' . $query . '%')
                ->setMaxResults((int) ceil($limit / 4))
                ->getQuery()
                ->getResult();
            
            foreach ($products as $product) {
                $allSuggestions[] = [
                    'id' => $product->getId(),
                    'title' => $product->getTitle(),
                    'description' => substr($product->getDescription() ?? '', 0, 100) . '...',
                    'type' => 'product',
                    'price' => $product->getPrice(),
                    'url' => $this->generateUrl('app_product_show', ['slug' => $product->getSlug()])
                ];
            }

            // Search users - database fallback
            $users = $this->userRepository->createQueryBuilder('u')
                ->where('u.fullName LIKE :query OR u.email LIKE :query')
                ->setParameter('query', '%' . $query . '%')
                ->setMaxResults((int) ceil($limit / 4))
                ->getQuery()
                ->getResult();
            
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
