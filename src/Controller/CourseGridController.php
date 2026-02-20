<?php

namespace App\Controller;

use App\Entity\Course;
use App\Repository\CategoryRepository;
use App\Repository\CourseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        EntityManagerInterface $entityManager
    ): Response {
        // Get filter parameters
        $search = $request->query->get('search');
        $sort = $request->query->get('sort', 'newest');
        $categories = $request->query->all('categories');
        $levels = $request->query->all('levels');
        $priceType = $request->query->get('price_type');
        $languages = $request->query->all('languages');

        // Build query
        $queryBuilder = $courseRepository->createQueryBuilder('c')
            ->leftJoin('c.category', 'cat')
            ->where('c.status = :status')
            ->setParameter('status', 'live');

        // Apply search filter
        if ($search) {
            $queryBuilder->andWhere('c.title LIKE :search OR c.shortDescription LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        // Apply category filter
        if (!empty($categories)) {
            $queryBuilder->andWhere('c.category IN (:categories)')
                ->setParameter('categories', $categories);
        }

        // Apply level filter
        if (!empty($levels)) {
            $queryBuilder->andWhere('c.level IN (:levels)')
                ->setParameter('levels', $levels);
        }

        // Apply price filter
        if ($priceType) {
            switch ($priceType) {
                case 'free':
                    $queryBuilder->andWhere('c.price = :price')
                        ->setParameter('price', 0);
                    break;
                case 'paid':
                    $queryBuilder->andWhere('c.price > :price')
                        ->setParameter('price', 0);
                    break;
            }
        }

        // Apply language filter
        if (!empty($languages)) {
            $queryBuilder->andWhere('c.language IN (:languages)')
                ->setParameter('languages', $languages);
        }

        // Get all courses before sorting
        $courses = $queryBuilder->getQuery()->getResult();

        // Debug: Log the number of courses found
        error_log('Found ' . count($courses) . ' courses with current filters');
        error_log('Query: ' . $queryBuilder->getDQL());
        
        if (count($courses) === 0) {
            error_log('No courses found. Filters applied:');
            error_log('Search: ' . ($search ?? 'none'));
            error_log('Categories: ' . json_encode($categories ?? []));
            error_log('Levels: ' . json_encode($levels ?? []));
            error_log('Price type: ' . ($priceType ?? 'none'));
            error_log('Languages: ' . json_encode($languages ?? []));
        }

        // Apply sorting
        switch ($sort) {
            case 'popular':
                // Sort by enrollment (placeholder - would need enrollment tracking)
                usort($courses, function($a, $b) {
                    return $b->getCreatedAt() <=> $a->getCreatedAt();
                });
                break;
            case 'newest':
                usort($courses, function($a, $b) {
                    return $b->getCreatedAt() <=> $a->getCreatedAt();
                });
                break;
            case 'rating':
                // Sort by rating (placeholder - would need rating system)
                usort($courses, function($a, $b) {
                    return $b->getCreatedAt() <=> $a->getCreatedAt();
                });
                break;
            case 'price_low':
                usort($courses, function($a, $b) {
                    return $a->getPrice() <=> $b->getPrice();
                });
                break;
            case 'price_high':
                usort($courses, function($a, $b) {
                    return $b->getPrice() <=> $a->getPrice();
                });
                break;
            default:
                // Default sorting (newest)
                usort($courses, function($a, $b) {
                    return $b->getCreatedAt() <=> $a->getCreatedAt();
                });
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
            ]
        ]);
    }

    /**
     * Get available levels with course counts
     */
    private function getAvailableLevels(EntityManagerInterface $entityManager): array
    {
        $sql = '
            SELECT level, COUNT(*) as courseCount
            FROM course
            WHERE status = :status AND level IS NOT NULL
            GROUP BY level
            ORDER BY courseCount DESC
        ';

        $stmt = $entityManager->getConnection()->prepare($sql);
        $result = $stmt->executeQuery(['status' => 'live']);

        $levels = [];
        while ($row = $result->fetchAssociative()) {
            $levels[] = [
                'name' => $row['level'],
                'count' => (int) $row['courseCount']
            ];
        }

        return $levels;
    }

    /**
     * Get available languages with course counts
     */
    private function getAvailableLanguages(EntityManagerInterface $entityManager): array
    {
        $sql = '
            SELECT language, COUNT(*) as courseCount
            FROM course
            WHERE status = :status AND language IS NOT NULL
            GROUP BY language
            ORDER BY courseCount DESC
        ';

        $stmt = $entityManager->getConnection()->prepare($sql);
        $result = $stmt->executeQuery(['status' => 'live']);

        $languages = [];
        while ($row = $result->fetchAssociative()) {
            $languages[] = [
                'name' => $row['language'],
                'count' => (int) $row['courseCount']
            ];
        }

        return $languages;
    }
}
