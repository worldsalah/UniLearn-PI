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
        $categories = $categoryRepository->findActiveCategories();

        // Debug: Check if categories are empty
        if (empty($categories)) {
            // If no categories found, create an empty array to avoid template errors
            $categories = [];
        } else {
            // Add course count to each category
            foreach ($categories as $category) {
                $courseCount = $courseRepository->count(['category' => $category, 'status' => 'live']);
                $category->courseCount = $courseCount;
            }
        }

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

        // Get active courses in this category
        $courses = $courseRepository->findBy(
            ['category' => $category, 'status' => 'live'],
            ['createdAt' => 'DESC']
        );

        return $this->render('category/detail.html.twig', [
            'category' => $category,
            'courses' => $courses,
        ]);
    }
}
