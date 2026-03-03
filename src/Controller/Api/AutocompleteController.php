<?php

namespace App\Controller\Api;

use App\Entity\Course;
use App\Repository\CourseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class AutocompleteController extends AbstractController
{
    private CourseRepository $courseRepository;

    public function __construct(CourseRepository $courseRepository)
    {
        $this->courseRepository = $courseRepository;
    }

    /**
     * Autocomplete search for courses with category support (database-based)
     */
    #[Route('/autocomplete/courses', name: 'api_autocomplete_courses', methods: ['GET'])]
    public function autocompleteCourses(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $category = $request->query->get('category', '');
        $limit = min(10, max(1, (int) $request->query->get('limit', 5)));

        if (strlen((string) $query) < 2) {
            return new JsonResponse(['suggestions' => []]);
        }

        try {
            // Build database query
            $qb = $this->courseRepository->createQueryBuilder('c')
                ->where('c.title LIKE :query')
                ->andWhere('c.status = :status')
                ->setParameter('query', $query . '%')
                ->setParameter('status', 'active')
                ->setMaxResults($limit);

            // Add category filter if provided
            if ($category !== '' && $category !== null) {
                $qb->join('c.category', 'cat')
                   ->andWhere('cat.name = :category')
                   ->setParameter('category', $category);
            }

            $results = $qb->getQuery()->getResult();

            $suggestions = [];
            foreach ($results as $course) {
                $suggestions[] = [
                    'id' => $course->getId(),
                    'title' => $course->getTitle(),
                    'description' => substr($course->getDescription() ?? '', 0, 100) . '...',
                    'type' => 'course',
                    'level' => $course->getLevel(),
                    'price' => $course->getPrice(),
                    'url' => $this->generateUrl('app_course_show', ['id' => $course->getId()])
                ];
            }

            return new JsonResponse([
                'success' => true,
                'suggestions' => $suggestions
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
