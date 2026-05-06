<?php

namespace App\Controller;

use App\Repository\CourseRepository;
use App\Repository\CategoryRepository;
use App\Repository\UserPointsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    private CourseRepository $courseRepository;
    private CategoryRepository $categoryRepository;
    private UserPointsRepository $userPointsRepository;

    public function __construct(CourseRepository $courseRepository, CategoryRepository $categoryRepository, UserPointsRepository $userPointsRepository)
    {
        $this->courseRepository = $courseRepository;
        $this->categoryRepository = $categoryRepository;
        $this->userPointsRepository = $userPointsRepository;
    }

    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        // Fetch only active categories that have live courses
        $categories = $this->categoryRepository->findCategoriesWithCourses(5);

        // If no categories with courses, get all active categories
        if (empty($categories)) {
            $categories = $this->categoryRepository->findActiveCategories();
        }

        // Get category IDs
        $categoryIds = array_column($categories, 'id');

        // Initialize coursesByCategory with empty arrays for ALL categories
        $coursesByCategory = array_fill_keys($categoryIds, []);

        // Fetch all courses for all categories in ONE query (fixes N+1)
        $allCourses = $this->courseRepository->findByCategoryIds($categoryIds, 'live', 4);

        // Merge results (keeps empty arrays for categories without courses)
        $coursesByCategory = array_replace($coursesByCategory, $allCourses);

        // Fallback: get courses with any status if no active courses found
        $categoryIdsWithoutCourses = array_filter($categoryIds, fn($id) => empty($coursesByCategory[$id]));
        if (!empty($categoryIdsWithoutCourses)) {
            $fallbackCourses = $this->courseRepository->findByCategoryIds($categoryIdsWithoutCourses, null, 4);
            $coursesByCategory = array_replace($coursesByCategory, $fallbackCourses);
        }

        // If still no courses, get any available courses
        $hasCourses = !empty(array_filter($coursesByCategory));
        if (!$hasCourses && !empty($categories)) {
            $firstCategoryId = is_array($categories[0]) ? $categories[0]['id'] : $categories[0]->getId();
            $coursesByCategory[$firstCategoryId] = $this->courseRepository->findPopular(4);
        }

        // Get user points
        $userTotalPoints = 0;
        $user = $this->getUser();
        
        // Debug logging
        error_log('DEBUG: User = ' . ($user ? 'FOUND (ID: ' . $user->getId() . ')' : 'NULL'));
        
        if ($user !== null) {
            $userPoints = $this->userPointsRepository->findByUser($user->getId());
            $userTotalPoints = $userPoints !== null ? $userPoints->getTotalPoints() : 0;
            
            // Debug logging
            error_log('DEBUG: UserPoints = ' . ($userPoints ? 'FOUND' : 'NULL'));
            error_log('DEBUG: TotalPoints = ' . $userTotalPoints);
        }

        return $this->render('home/index.html.twig', [
            'categories' => $categories,
            'coursesByCategory' => $coursesByCategory,
            'userTotalPoints' => $userTotalPoints,
        ]);
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function dashboard(): Response
    {
        // Get the currently logged-in user
        $user = $this->getUser();

        if ($user === null) {
            // If no user is logged in, redirect to login
            return $this->redirectToRoute('app_login');
        }

        // Redirect all users to instructor dashboard (now accessible by all)
        return $this->redirectToRoute('app_instructor_dashboard');
    }

    #[Route('/become-instructor', name: 'app_become_instructor')]
    public function becomeInstructor(): Response
    {
        return $this->render('become-instructor.html.twig');
    }
}
