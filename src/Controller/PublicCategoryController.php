<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Repository\CourseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PublicCategoryController extends AbstractController
{
    // Public routes
    #[Route('/categories', name: 'app_categories')]
    public function publicCategories(CategoryRepository $categoryRepository, CourseRepository $courseRepository): Response
    {
        $categories = $categoryRepository->findCategoriesWithCourseCount();

        return $this->render('category/categories.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/course-categories', name: 'app_course_categories')]
    public function courseCategories(CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findCategoriesWithCourseCount();

        return $this->render('category/course-categories.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/category/{slug}', name: 'app_category_detail')]
    public function categoryDetail(Category $category, CourseRepository $courseRepository): Response
    {
        if (!$category->isIsActive()) {
            throw $this->createNotFoundException('Category not found');
        }

        // First, try to find all courses in this category regardless of status
        $allCourses = $courseRepository->findBy(
            ['category' => $category],
            ['createdAt' => 'DESC']
        );

        // Then find only live courses
        $liveCourses = $courseRepository->findBy(
            ['category' => $category, 'status' => 'live'],
            ['createdAt' => 'DESC']
        );

        // Debug: Log the results
        error_log('Category: '.$category->getName());
        error_log('All courses found: '.count($allCourses));
        error_log('Live courses found: '.count($liveCourses));

        foreach ($allCourses as $course) {
            error_log('Course: '.$course->getTitle().' | Status: '.$course->getStatus());
        }

        // Use all courses for now to see if they display
        $courses = $allCourses;

        return $this->render('category/detail.html.twig', [
            'category' => $category,
            'courses' => $courses,
        ]);
    }
}
