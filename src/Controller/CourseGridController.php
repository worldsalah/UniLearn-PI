<?php

namespace App\Controller;

use App\Entity\Course;
use App\Repository\CategoryRepository;
use App\Repository\CourseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CourseGridController extends AbstractController
{
    #[Route('/course-grid', name: 'app_course_grid_redirect')]
    public function redirectCourseGrid(): Response
    {
        return $this->redirectToRoute('app_course_grid');
    }

    #[Route('/courses', name: 'app_course_grid')]
    public function index(
        Request $request,
        CourseRepository $courseRepository,
        CategoryRepository $categoryRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        // Get filter parameters with safe defaults
        $search = $request->query->get('search', '');
        $sort = $request->query->get('sort', 'newest');
        $categories = $request->query->all('categories', []);
        $levels = $request->query->all('levels', []);
        $priceType = $request->query->get('price_type', '');
        $languages = $request->query->all('languages', []);

        // Ensure all filter parameters are arrays
        $categories = is_array($categories) ? $categories : [];
        $levels = is_array($levels) ? $levels : [];
        $languages = is_array($languages) ? $languages : [];

        // Clean up filter parameters - remove empty values and convert to strings
        $categories = array_filter($categories, fn($cat) => is_string($cat) && $cat !== '');
        $levels = array_filter($levels, fn($level) => is_string($level) && $level !== '');
        $languages = array_filter($languages, fn($lang) => is_string($lang) && $lang !== '');

        // Reset array keys to ensure clean indexed arrays
        $categories = array_values($categories);
        $levels = array_values($levels);
        $languages = array_values($languages);

        // Build query for courses
        $queryBuilder = $courseRepository->createQueryBuilder('c')
            ->where('c.status = :status')
            ->setParameter('status', 'live');

        // Apply search filter
        if ($search) {
            $queryBuilder->andWhere('c.title LIKE :search OR c.shortDescription LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        // Apply category filter
        if (!empty($categories)) {
            // Since Course has ManyToOne relationship with Category, we need to handle multiple categories differently
            $orConditions = [];
            foreach ($categories as $index => $categoryId) {
                $orConditions[] = "c.category = :category_$index";
                $queryBuilder->setParameter("category_$index", $categoryId);
            }
            $queryBuilder->andWhere('(' . implode(' OR ', $orConditions) . ')');
        }

        // Apply level filter
        if (!empty($levels)) {
            $queryBuilder->andWhere('c.level IN (:levels)')
                ->setParameter('levels', $levels);
        }

        // Apply price filter
        if ('free' === $priceType) {
            $queryBuilder->andWhere('c.price = :price')
                ->setParameter('price', 0);
        } elseif ('paid' === $priceType) {
            $queryBuilder->andWhere('c.price > :price')
                ->setParameter('price', 0);
        }

        // Apply language filter
        if (!empty($languages)) {
            $queryBuilder->andWhere('c.language IN (:languages)')
                ->setParameter('languages', $languages);
        }

        // Get all courses before sorting
        $courses = $queryBuilder->getQuery()->getResult();

        // Apply sorting
        switch ($sort) {
            case 'popular':
                usort($courses, fn($a, $b) => $b->getCreatedAt() <=> $a->getCreatedAt());
                break;
            case 'newest':
                usort($courses, fn($a, $b) => $b->getCreatedAt() <=> $a->getCreatedAt());
                break;
            case 'rating':
                usort($courses, fn($a, $b) => $b->getCreatedAt() <=> $a->getCreatedAt());
                break;
            case 'price_low':
                usort($courses, fn($a, $b) => $a->getPrice() <=> $b->getPrice());
                break;
            case 'price_high':
                usort($courses, fn($a, $b) => $b->getPrice() <=> $a->getPrice());
                break;
            default:
                usort($courses, fn($a, $b) => $b->getCreatedAt() <=> $a->getCreatedAt());
        }

        // Get filter data
        $categoriesWithCount = $categoryRepository->findCategoriesWithCourseCount();
        $availableLevels = $this->getAvailableLevels($entityManager);
        $availableLanguages = $this->getAvailableLanguages($entityManager);

        return $this->render('course/course-grid.html.twig', [
            'courses' => $courses,
            'categories' => $categoriesWithCount,
            'levels' => $availableLevels,
            'languages' => $availableLanguages,
            'currentFilters' => [
                'search' => $search,
                'sort' => $sort,
                'categories' => $categories,
                'levels' => $levels,
                'price_type' => $priceType,
                'languages' => $languages,
            ],
        ]);
    }

    /**
     * AJAX endpoint for filtering courses without page refresh
     */
    #[Route('/courses/filter', name: 'app_course_filter')]
    public function filterCourses(
        Request $request,
        CourseRepository $courseRepository,
        CategoryRepository $categoryRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        // Get filter parameters with safe defaults
        $search = $request->query->get('search', '');
        $sort = $request->query->get('sort', 'newest');
        $categories = $request->query->all('categories', []);
        $levels = $request->query->all('levels', []);
        $priceType = $request->query->get('price_type', '');
        $languages = $request->query->all('languages', []);

        // Ensure all filter parameters are arrays
        $categories = is_array($categories) ? $categories : [];
        $levels = is_array($levels) ? $levels : [];
        $languages = is_array($languages) ? $languages : [];

        // Clean up filter parameters - remove empty values and convert to strings
        $categories = array_filter($categories, fn($cat) => is_string($cat) && $cat !== '');
        $levels = array_filter($levels, fn($level) => is_string($level) && $level !== '');
        $languages = array_filter($languages, fn($lang) => is_string($lang) && $lang !== '');

        // Reset array keys to ensure clean indexed arrays
        $categories = array_values($categories);
        $levels = array_values($levels);
        $languages = array_values($languages);

        // Build query for courses (same logic as main method)
        $queryBuilder = $courseRepository->createQueryBuilder('c')
            ->where('c.status = :status')
            ->setParameter('status', 'live');

        // Apply search filter
        if ($search) {
            $queryBuilder->andWhere('c.title LIKE :search OR c.shortDescription LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        // Apply category filter
        if (!empty($categories)) {
            // Since Course has ManyToOne relationship with Category, we need to handle multiple categories differently
            $orConditions = [];
            foreach ($categories as $index => $categoryId) {
                $orConditions[] = "c.category = :category_$index";
                $queryBuilder->setParameter("category_$index", $categoryId);
            }
            $queryBuilder->andWhere('(' . implode(' OR ', $orConditions) . ')');
        }

        // Apply level filter
        if (!empty($levels)) {
            $queryBuilder->andWhere('c.level IN (:levels)')
                ->setParameter('levels', $levels);
        }

        // Apply price filter
        if ('free' === $priceType) {
            $queryBuilder->andWhere('c.price = :price')
                ->setParameter('price', 0);
        } elseif ('paid' === $priceType) {
            $queryBuilder->andWhere('c.price > :price')
                ->setParameter('price', 0);
        }

        // Apply language filter
        if (!empty($languages)) {
            $queryBuilder->andWhere('c.language IN (:languages)')
                ->setParameter('languages', $languages);
        }

        // Get all courses before sorting
        $courses = $queryBuilder->getQuery()->getResult();

        // Apply sorting (same logic as main method)
        switch ($sort) {
            case 'popular':
                usort($courses, fn($a, $b) => $b->getCreatedAt() <=> $a->getCreatedAt());
                break;
            case 'newest':
                usort($courses, fn($a, $b) => $b->getCreatedAt() <=> $a->getCreatedAt());
                break;
            case 'rating':
                usort($courses, fn($a, $b) => $b->getCreatedAt() <=> $a->getCreatedAt());
                break;
            case 'price_low':
                usort($courses, fn($a, $b) => $a->getPrice() <=> $b->getPrice());
                break;
            case 'price_high':
                usort($courses, fn($a, $b) => $b->getPrice() <=> $a->getPrice());
                break;
            default:
                usort($courses, fn($a, $b) => $b->getCreatedAt() <=> $a->getCreatedAt());
        }

        // Render the course grid HTML
        $gridHtml = $this->renderView('course/_course-grid.html.twig', [
            'courses' => $courses,
            'currentFilters' => [
                'search' => $search,
                'sort' => $sort,
                'categories' => $categories,
                'levels' => $levels,
                'price_type' => $priceType,
                'languages' => $languages,
            ],
        ]);

        // Return JSON response with HTML and metadata
        return new JsonResponse([
            'success' => true,
            'html' => $gridHtml,
            'count' => count($courses),
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'categories' => $categories,
                'levels' => $levels,
                'price_type' => $priceType,
                'languages' => $languages,
            ]
        ]);
    }

    /**
     * Get available levels with course counts.
     */
    private function getAvailableLevels(EntityManagerInterface $entityManager): array
    {
        $levels = [];
        $sql = '
            SELECT level, COUNT(*) as courseCount
            FROM course
            WHERE status = :status AND level IS NOT NULL
            GROUP BY level
            ORDER BY courseCount DESC
        ';

        $result = $entityManager->getConnection()->executeQuery($sql, ['status' => 'live']);

        while ($row = $result->fetchAssociative()) {
            $levels[] = [
                'name' => $row['level'],
                'count' => (int) $row['courseCount'],
            ];
        }

        return $levels;
    }

    /**
     * Get available languages with course counts.
     */
    private function getAvailableLanguages(EntityManagerInterface $entityManager): array
    {
        $languages = [];
        $sql = '
            SELECT language, COUNT(*) as courseCount
            FROM course
            WHERE status = :status AND language IS NOT NULL
            GROUP BY language
            ORDER BY courseCount DESC
        ';

        $result = $entityManager->getConnection()->executeQuery($sql, ['status' => 'live']);

        while ($row = $result->fetchAssociative()) {
            $languages[] = [
                'name' => $row['language'],
                'count' => (int) $row['courseCount'],
            ];
        }

        return $languages;
    }
}
