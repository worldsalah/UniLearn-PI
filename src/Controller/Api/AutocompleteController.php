<?php

namespace App\Controller\Api;

use App\Entity\Course;
use FOS\ElasticaBundle\Finder\TransformedFinder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class AutocompleteController extends AbstractController
{
    private TransformedFinder $finder;

    public function __construct(
        #[Target('courses.finder')]
        TransformedFinder $finder
    ) {
        $this->finder = $finder;
    }

    /**
     * Autocomplete search for courses with category support
     */
    #[Route('/autocomplete/courses', name: 'api_autocomplete_courses', methods: ['GET'])]
    public function autocompleteCourses(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $category = $request->query->get('category', '');
        $limit = min(10, max(1, (int) $request->query->get('limit', 5)));

        if (strlen($query) < 2) {
            return new JsonResponse(['suggestions' => []]);
        }

        try {
            $finder = $this->finder;

            // Build search query with optional category filter
            $searchQuery = [
                'query' => [
                    'prefix' => [
                        'title' => $query
                    ]
                ]
            ];

            // Add category filter if provided
            if ($category) {
                $searchQuery['query']['bool'] = [
                    'must' => [
                        [
                            'match' => [
                                'title' => [
                                    'query' => $query,
                                    'operator' => 'and'
                                ]
                            ]
                        ]
                    ],
                    'filter' => [
                        [
                            'term' => [
                                'category.keyword' => $category
                            ]
                        ]
                    ]
                ];
            }

            $results = $finder->find($searchQuery, $limit);

            $suggestions = [];
            foreach ($results as $course) {
                $suggestions[] = [
                    'id' => $course->getId(),
                    'title' => $course->getTitle(),
                    'description' => substr($course->getDescription(), 0, 100) . '...',
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
