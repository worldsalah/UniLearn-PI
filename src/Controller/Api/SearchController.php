<?php

namespace App\Controller\Api;

use App\Entity\Course;
use App\Repository\CourseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

#[Route('/api/courses')]
class SearchController extends AbstractController
{
    private CourseRepository $courseRepository;

    public function __construct(CourseRepository $courseRepository)
    {
        $this->courseRepository = $courseRepository;
    }

    /**
     * Search courses with full-text search and optional filters (database-based)
     */
    #[Route('/search', name: 'api_courses_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->query->get('q', '');
            $level = $request->query->get('level');
            $page = max(1, (int) $request->query->get('page', 1));
            $limit = min(100, max(1, (int) $request->query->get('limit', 10)));

            if (empty($query)) {
                throw new BadRequestHttpException('Search query parameter "q" is required');
            }

            // Build database query
            $qb = $this->courseRepository->createQueryBuilder('c')
                ->where('c.status = :status')
                ->andWhere('c.title LIKE :query OR c.description LIKE :query')
                ->setParameter('status', 'live')
                ->setParameter('query', '%' . $query . '%')
                ->setFirstResult(($page - 1) * $limit)
                ->setMaxResults($limit);

            // Add level filter if provided
            if ($level !== null && $level !== '') {
                $qb->andWhere('c.level = :level')
                   ->setParameter('level', $level);
            }

            $results = $qb->getQuery()->getResult();

            // Format results
            $courses = [];
            foreach ($results as $course) {
                $courses[] = [
                    'id' => $course->getId(),
                    'title' => $course->getTitle(),
                    'description' => $course->getDescription(),
                    'level' => $course->getLevel(),
                    'price' => $course->getPrice(),
                    'language' => $course->getLanguage(),
                    'duration' => $course->getDuration(),
                    'category' => $course->getCategory() ? [
                        'id' => $course->getCategory()->getId(),
                        'name' => $course->getCategory()->getName()
                    ] : null,
                    'thumbnailUrl' => $course->getThumbnailUrl(),
                    'createdAt' => $course->getCreatedAt()?->format('Y-m-d H:i:s')
                ];
            }

            return new JsonResponse([
                'success' => true,
                'data' => [
                    'courses' => $courses,
                    'pagination' => [
                        'currentPage' => $page,
                        'itemsPerPage' => $limit,
                        'totalItems' => count($courses),
                        'totalPages' => 1
                    ],
                    'search' => [
                        'query' => $query,
                        'filters' => [
                            'level' => $level
                        ]
                    ]
                ]
            ]);

        } catch (BadRequestHttpException $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'An error occurred while searching courses'
            ], 500);
        }
    }

    /**
     * Get available course levels for filtering
     */
    #[Route('/levels', name: 'api_courses_levels', methods: ['GET'])]
    public function getLevels(): JsonResponse
    {
        try {
            // For now, return common levels
            $levels = ['beginner', 'intermediate', 'advanced'];

            return new JsonResponse([
                'success' => true,
                'data' => [
                    'levels' => $levels
                ]
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'An error occurred while fetching levels'
            ], 500);
        }
    }
}
