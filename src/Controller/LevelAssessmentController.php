<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Course;
use App\Repository\CategoryRepository;
use App\Repository\CourseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LevelAssessmentController extends AbstractController
{
    #[Route('/level-assessment', name: 'app_level_assessment')]
    public function index(
        CategoryRepository $categoryRepository,
        CourseRepository $courseRepository
    ): Response {
        // Fetch active categories with their courses
        $categories = $categoryRepository->findBy(['isActive' => true], ['name' => 'ASC']);
        
        // Group courses by category
        $categoriesWithCourses = [];
        foreach ($categories as $category) {
            // Get courses with published or live status for this category using QueryBuilder
            $courses = $courseRepository->createQueryBuilder('c')
                ->where('c.category = :category')
                ->andWhere('c.status IN (:statuses)')
                ->setParameter('category', $category)
                ->setParameter('statuses', ['published', 'live'])
                ->orderBy('c.title', 'ASC')
                ->getQuery()
                ->getResult();
            
            if (!empty($courses)) {
                $categoriesWithCourses[] = [
                    'category' => $category,
                    'courses' => $courses
                ];
            }
        }

        return $this->render('level_assessment/index.html.twig', [
            'categoriesWithCourses' => $categoriesWithCourses,
        ]);
    }
}
