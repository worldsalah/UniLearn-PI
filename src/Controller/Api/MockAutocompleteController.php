<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class MockAutocompleteController extends AbstractController
{
    /**
     * Mock autocomplete for testing without Elasticsearch
     */
    #[Route('/autocomplete/courses-mock', name: 'api_autocomplete_courses_mock', methods: ['GET'])]
    public function mockAutocompleteCourses(Request $request): JsonResponse
    {
        $query = strtolower($request->query->get('q', ''));
        $limit = min(10, max(1, (int) $request->query->get('limit', 5)));

        // Mock course data
        $mockCourses = [
            [
                'id' => 1,
                'title' => 'PHP Fundamentals',
                'description' => 'Learn the basics of PHP programming from scratch',
                'level' => 'beginner',
                'price' => 49.99,
                'thumbnailUrl' => '/uploads/courses/php-thumb.jpg',
                'url' => '/course/php-fundamentals'
            ],
            [
                'id' => 2,
                'title' => 'Advanced JavaScript',
                'description' => 'Master advanced JavaScript concepts and modern frameworks',
                'level' => 'advanced',
                'price' => 89.99,
                'thumbnailUrl' => '/uploads/courses/js-thumb.jpg',
                'url' => '/course/advanced-javascript'
            ],
            [
                'id' => 3,
                'title' => 'Symfony Framework',
                'description' => 'Build web applications with Symfony PHP framework',
                'level' => 'intermediate',
                'price' => 79.99,
                'thumbnailUrl' => '/uploads/courses/symfony-thumb.jpg',
                'url' => '/course/symfony-framework'
            ],
            [
                'id' => 4,
                'title' => 'React Development',
                'description' => 'Create modern web applications with React',
                'level' => 'intermediate',
                'price' => 69.99,
                'thumbnailUrl' => '/uploads/courses/react-thumb.jpg',
                'url' => '/course/react-development'
            ],
            [
                'id' => 5,
                'title' => 'Database Design',
                'description' => 'Learn database design principles and SQL',
                'level' => 'beginner',
                'price' => 59.99,
                'thumbnailUrl' => '/uploads/courses/db-thumb.jpg',
                'url' => '/course/database-design'
            ],
            [
                'id' => 6,
                'title' => 'Python Programming',
                'description' => 'Complete Python programming course for beginners',
                'level' => 'beginner',
                'price' => 54.99,
                'thumbnailUrl' => '/uploads/courses/python-thumb.jpg',
                'url' => '/course/python-programming'
            ],
            [
                'id' => 7,
                'title' => 'Web Security',
                'description' => 'Essential web security practices and techniques',
                'level' => 'advanced',
                'price' => 99.99,
                'thumbnailUrl' => '/uploads/courses/security-thumb.jpg',
                'url' => '/course/web-security'
            ],
            [
                'id' => 8,
                'title' => 'API Development',
                'description' => 'Build and consume RESTful APIs',
                'level' => 'intermediate',
                'price' => 74.99,
                'thumbnailUrl' => '/uploads/courses/api-thumb.jpg',
                'url' => '/course/api-development'
            ],
            [
                'id' => 9,
                'title' => 'Mobile Development',
                'description' => 'Create mobile applications for iOS and Android',
                'level' => 'advanced',
                'price' => 94.99,
                'thumbnailUrl' => '/uploads/courses/mobile-thumb.jpg',
                'url' => '/course/mobile-development'
            ],
            [
                'id' => 10,
                'title' => 'DevOps Essentials',
                'description' => 'Learn DevOps practices and tools',
                'level' => 'intermediate',
                'price' => 84.99,
                'thumbnailUrl' => '/uploads/courses/devops-thumb.jpg',
                'url' => '/course/devops-essentials'
            ]
        ];

        // Filter mock courses based on query
        $suggestions = [];
        if (strlen($query) >= 2) {
            foreach ($mockCourses as $course) {
                if (strpos(strtolower($course['title']), $query) !== false || 
                    strpos(strtolower($course['description']), $query) !== false) {
                    $suggestions[] = $course;
                    if (count($suggestions) >= $limit) {
                        break;
                    }
                }
            }
        }

        return new JsonResponse([
            'success' => true,
            'suggestions' => $suggestions,
            'mock' => true,
            'message' => 'This is mock data for testing purposes'
        ]);
    }
}
