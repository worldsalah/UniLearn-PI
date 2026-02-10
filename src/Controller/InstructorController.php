<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\CourseRepository;
use App\Repository\UserRepository;
use App\Repository\QuizResultRepository;
use App\Form\ProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class InstructorController extends AbstractController
{
    #[Route('/instructor/dashboard', name: 'app_instructor_dashboard')]
    public function dashboard(CourseRepository $courseRepository, UserRepository $userRepository): Response
    {
        // Get the currently logged-in user
        $user = $this->getUser();
        
        if (!$user) {
            // If no user is logged in, redirect to login
            return $this->redirectToRoute('app_login');
        }
        
        // Allow all users to access the dashboard, not just teachers
        $teacher = $userRepository->find($user->getId());
        
        if (!$teacher) {
            throw $this->createNotFoundException('User not found');
        }

        // Get courses associated with this user (if any)
        $courses = $courseRepository->findBy(['user' => $teacher]);
        
        // Calculate statistics
        $totalCourses = count($courses);
        $totalStudents = $this->calculateTotalStudents($courses);
        $totalEarnings = $this->calculateTotalEarnings($courses);
        $averageRating = $this->calculateAverageRating($courses);
        
        // Get recent courses for display
        $recentCourses = array_slice($courses, -3);
        
        // Earnings data for chart (mock data for now, can be enhanced with real order data)
        $earningsData = [
            'currentMonth' => $totalEarnings * 0.3, // 30% of total earnings as current month
            'lastMonth' => $totalEarnings * 0.25,   // 25% of total earnings as last month
            'average' => $totalEarnings * 0.28,     // Average of current and last month
            'chartData' => [31, 40, 28, 51, 42, 109, 100], // Mock data for chart
            'chartLabels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul']
        ];

        return $this->render('instructor/dashboard.html.twig', [
            'teacher' => $teacher,
            'courses' => $courses,
            'recentCourses' => $recentCourses,
            'totalCourses' => $totalCourses,
            'totalStudents' => $totalStudents,
            'totalEarnings' => $totalEarnings,
            'averageRating' => $averageRating,
            'earningsData' => $earningsData
        ]);
    }

    #[Route('/instructor/manage-courses', name: 'app_instructor_manage_courses')]
    public function manageCourses(CourseRepository $courseRepository, UserRepository $userRepository): Response
    {
        // Get the currently logged-in user
        $user = $this->getUser();
        
        if (!$user) {
            // If no user is logged in, redirect to login
            return $this->redirectToRoute('app_login');
        }
        
        // Allow all users to access course management
        $teacher = $userRepository->find($user->getId());
        
        if (!$teacher) {
            throw $this->createNotFoundException('User not found');
        }

        // Get courses belonging to the logged-in user (if any)
        $courses = $courseRepository->findBy(['user' => $teacher]);

        return $this->render('instructor/manage-courses.html.twig', [
            'courses' => $courses,
            'teacher' => $teacher
        ]);
    }

    #[Route('/instructor/course/edit/{id}', name: 'app_instructor_edit_course', methods: ['GET', 'POST'])]
    public function editCourse(Course $course, CourseRepository $courseRepository, CategoryRepository $categoryRepository, UserRepository $userRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Get the currently logged-in user
        $user = $this->getUser();
        
        if (!$user) {
            // If no user is logged in, redirect to login
            return $this->redirectToRoute('app_login');
        }
        
        // Allow all users to edit courses, but check ownership
        $teacher = $userRepository->find($user->getId());
        
        if (!$teacher) {
            throw $this->createNotFoundException('User not found');
        }
        
        // Check if the course belongs to the logged-in user
        if ($course->getUser()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('You can only edit your own courses');
        }

        if ($request->isMethod('POST')) {
            // Update course with form data
            $course->setTitle($request->request->get('course_title'));
            $course->setShortDescription($request->request->get('short_description'));
            
            // Handle category - fetch entity from repository
            $categoryId = $request->request->get('course_category');
            if ($categoryId) {
                $category = $categoryRepository->find($categoryId);
                if ($category) {
                    $course->setCategory($category);
                }
            }
            
            $course->setLevel($request->request->get('course_level'));
            $course->setPrice((float) $request->request->get('course_price'));
            $course->setLanguage($request->request->get('language'));
            $course->setDuration((float) $request->request->get('duration'));
            $course->setRequirements($request->request->get('requirements'));
            $course->setLearningOutcomes($request->request->get('learning_outcomes'));
            $course->setTargetAudience($request->request->get('target_audience'));
            
            $entityManager->flush();
            
            // Show success notification
            $this->addFlash('course_updated', [
                'title' => 'Course Updated Successfully!',
                'message' => "The course '{$course->getTitle()}' has been updated.",
                'type' => 'success',
                'icon' => 'fas fa-edit'
            ]);
            
            return $this->redirectToRoute('app_instructor_manage_courses');
        }

        // Show edit form for GET request
        return $this->render('instructor/edit-course.html.twig', [
            'course' => $course,
            'teacher' => $teacher
        ]);
    }

    #[Route('/instructor/course/detail/{id}', name: 'app_instructor_course_detail')]
    public function courseDetail(Course $course, UserRepository $userRepository): Response
    {
        // Get the currently logged-in user
        $user = $this->getUser();
        
        if (!$user) {
            // If no user is logged in, redirect to login
            return $this->redirectToRoute('app_login');
        }
        
        // Allow all users to view courses, but check ownership for editing
        $teacher = $userRepository->find($user->getId());
        
        if (!$teacher) {
            throw $this->createNotFoundException('User not found');
        }
        
        // Check if the course belongs to the logged-in user
        if ($course->getUser()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('You can only view your own courses');
        }

        return $this->render('instructor/course-detail.html.twig', [
            'course' => $course,
            'teacher' => $teacher
        ]);
    }

    #[Route('/instructor/course/delete/{id}', name: 'app_instructor_delete_course')]
    public function deleteCourse(Course $course, EntityManagerInterface $entityManager, UserRepository $userRepository): RedirectResponse
    {
        // Get the currently logged-in user
        $user = $this->getUser();
        
        if (!$user) {
            // If no user is logged in, redirect to login
            return $this->redirectToRoute('app_login');
        }
        
        // Allow all users to delete courses, but check ownership
        $teacher = $userRepository->find($user->getId());
        
        if (!$teacher) {
            throw $this->createNotFoundException('User not found');
        }
        
        // Check if the course belongs to the logged-in user
        if ($course->getUser()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('You can only delete your own courses');
        }

        $courseTitle = $course->getTitle();
        
        // Remove the course
        $entityManager->remove($course);
        $entityManager->flush();
        
        $this->addFlash('course_deleted', [
            'title' => 'Course Deleted Successfully!',
            'message' => "The course '{$courseTitle}' has been permanently removed from your catalog.",
            'type' => 'success',
            'icon' => 'fas fa-check-circle'
        ]);
        
        return $this->redirectToRoute('app_instructor_manage_courses');
    }

    #[Route('/instructor/students', name: 'app_instructor_students')]
    public function students(UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        // Get the currently logged-in instructor
        $instructor = $this->getUser();
        
        if (!$instructor) {
            return $this->redirectToRoute('app_login');
        }

        // Get all users from the database
        $users = $userRepository->findAll();
        
        // Calculate statistics
        $totalUsers = count($users);
        $activeUsers = count(array_filter($users, fn($user) => $user->getStatus() === 'active'));
        $inactiveUsers = count(array_filter($users, fn($user) => $user->getStatus() === 'inactive'));
        
        // Get role statistics
        $roleStats = [];
        foreach ($users as $user) {
            $roleName = $user->getRole() ? $user->getRole()->getName() : 'Unknown';
            if (!isset($roleStats[$roleName])) {
                $roleStats[$roleName] = 0;
            }
            $roleStats[$roleName]++;
        }

        return $this->render('instructor/students.html.twig', [
            'users' => $users,
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'inactiveUsers' => $inactiveUsers,
            'roleStats' => $roleStats,
            'teacher' => [
                'name' => $instructor->getFullName(),
                'email' => $instructor->getEmail(),
                'role' => $instructor->getRole()
            ]
        ]);
    }

    #[Route('/instructor/earnings', name: 'app_instructor_earnings')]
    public function earnings(): Response
    {
        return $this->render('instructor/earnings.html.twig');
    }

    #[Route('/instructor/reviews', name: 'app_instructor_reviews')]
    public function reviews(): Response
    {
        return $this->render('instructor/reviews.html.twig');
    }

    #[Route('/instructor/quiz', name: 'app_instructor_quiz')]
    public function quiz(EntityManagerInterface $entityManager): Response
    {
        // Get the currently logged-in user
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Get courses for this instructor (both active and inactive for quiz creation)
        $courses = $entityManager->getRepository('App\Entity\Course')->findBy(['user' => $user]);
        
        // Debug: Log course information
        error_log('Quiz Page - User ID: ' . $user->getId());
        error_log('Quiz Page - Courses found: ' . count($courses));
        
        foreach ($courses as $course) {
            error_log('Quiz Page - Course: ' . $course->getTitle() . ' (Status: ' . $course->getStatus() . ', ID: ' . $course->getId() . ')');
        }
        
        // Get quizzes for this instructor's courses (from all courses for debugging)
        $quizzes = [];
        foreach ($courses as $course) {
            // Get quizzes from all courses (remove status restriction for debugging)
            error_log('Processing course: ' . $course->getTitle() . ' (Status: ' . $course->getStatus() . ')');
            $courseQuizzes = $entityManager->getRepository('App\Entity\Quiz')->findBy(['course' => $course]);
            error_log('Found ' . count($courseQuizzes) . ' quizzes for course ' . $course->getTitle());
            $quizzes = array_merge($quizzes, $courseQuizzes);
        }
        
        error_log('Total quizzes found: ' . count($quizzes));
        
        // Calculate statistics
        $totalQuestions = 0;
        $totalAttempts = 0;
        $avgScore = 0;
        $scoreSum = 0;
        $scoreCount = 0;
        
        foreach ($quizzes as $quiz) {
            $totalQuestions += $quiz->getQuestions()->count();
            $totalAttempts += $quiz->getQuizResults()->count();
            
            // Calculate average score for this quiz
            $quizResults = $quiz->getQuizResults();
            if ($quizResults->count() > 0) {
                $quizScoreSum = 0;
                foreach ($quizResults as $result) {
                    // Assuming QuizResult has getScore() method
                    if (method_exists($result, 'getScore')) {
                        $quizScoreSum += $result->getScore();
                    }
                }
                $avgScore += $quizScoreSum / $quizResults->count();
                $scoreCount++;
            }
        }
        
        if ($scoreCount > 0) {
            $avgScore = round($avgScore / $scoreCount, 1);
        }
        
        // Get instructor data
        $instructor = [
            'name' => $user->getFullName() ?? $user->getEmail(),
            'totalCourses' => count($courses),
            'totalStudents' => $this->calculateTotalStudents($courses),
            'averageRating' => $this->calculateAverageRating($courses)
        ];
        
        return $this->render('instructor/quiz.html.twig', [
            'quizzes' => $quizzes,
            'courses' => $courses,
            'teacher' => $instructor,
            'totalQuestions' => $totalQuestions,
            'totalAttempts' => $totalAttempts,
            'averageScore' => $avgScore
        ]);
    }

    #[Route('/instructor/orders', name: 'app_instructor_orders')]
    public function orders(): Response
    {
        return $this->render('instructor/orders.html.twig');
    }

    #[Route('/instructor/edit-profile', name: 'app_instructor_edit_profile', methods: ['GET', 'POST'])]
    public function editProfile(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        // Get the currently logged-in user
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Create the form
        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle profile image upload
            $profileImageFile = $form->get('profileImage')->getData();
            if ($profileImageFile) {
                $newFilename = uniqid() . '.' . $profileImageFile->guessExtension();
                
                // Move the file to the uploads directory
                $profileImageFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/profiles',
                    $newFilename
                );
                
                // Update user profile image path
                $user->setProfileImage('/uploads/profiles/' . $newFilename);
            }

            // Handle password change
            $plainPassword = $form->get('plainPassword')->get('first');
            if ($plainPassword) {
                // Verify current password
                $currentPassword = $form->get('currentPassword')->getData();
                if ($passwordHasher->isPasswordValid($user, $currentPassword)) {
                    // Hash and set the new password
                    $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                    $user->setPassword($hashedPassword);
                    
                    $this->addFlash('success', 'Your password has been updated successfully.');
                } else {
                    $this->addFlash('error', 'Current password is incorrect. Please try again.');
                    
                    return $this->render('instructor/edit-profile.html.twig', [
                        'form' => $form->createView(),
                        'user' => $user
                    ]);
                }
            }

            // Save the changes
            $entityManager->persist($user);
            $entityManager->flush();

            // Add success message
            $this->addFlash('success', 'Your profile has been updated successfully!');

            return $this->redirectToRoute('app_instructor_edit_profile');
        }

        return $this->render('instructor/edit-profile.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    #[Route('/instructor/payout', name: 'app_instructor_payout')]
    public function payout(): Response
    {
        return $this->render('instructor/payout.html.twig');
    }

    #[Route('/instructor/delete-account', name: 'app_instructor_delete_account', methods: ['GET', 'POST'])]
    public function deleteAccount(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        // Get the currently logged-in user
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Create a simple form for confirmation
        $form = $this->createFormBuilder()
            ->add('password', \Symfony\Component\Form\Extension\Core\Type\PasswordType::class, [
                'label' => 'Enter your password to confirm account deletion',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter your password'
                ],
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\NotBlank([
                        'message' => 'Please enter your password to confirm account deletion'
                    ])
                ]
            ])
            ->add('confirm', \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class, [
                'label' => 'I understand that this action cannot be undone and will permanently delete all my data',
                'required' => true,
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\IsTrue([
                        'message' => 'You must confirm that you understand the consequences of deleting your account'
                    ])
                ]
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $password = $form->get('password')->getData();
            
            // Verify password
            if ($passwordHasher->isPasswordValid($user, $password)) {
                // Get user information for logging
                $userName = $user->getFullName();
                $userEmail = $user->getEmail();
                
                // Log the user out
                $this->container->get('security.token_storage')->setToken(null);
                $request->getSession()->invalidate();
                
                // Delete the user (cascade will handle related entities)
                $entityManager->remove($user);
                $entityManager->flush();
                
                // Add success message (though user won't see it as they're logged out)
                $this->addFlash('success', 'Your account has been permanently deleted.');
                
                // Redirect to home page
                return $this->redirectToRoute('app_home');
            } else {
                $this->addFlash('error', 'Incorrect password. Please try again.');
            }
        }

        return $this->render('instructor/delete-account.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    #[Route('/instructor/settings', name: 'app_instructor_settings')]
    public function settings(): Response
    {
        return $this->render('instructor/settings.html.twig');
    }

    /**
     * Calculate total number of students across all courses
     * This is a mock implementation - in a real app, you'd have an enrollment table
     */
    private function calculateTotalStudents(array $courses): int
    {
        // Mock calculation: assume average of 15 students per course
        return count($courses) * 15;
    }

    /**
     * Calculate total earnings from all courses
     */
    private function calculateTotalEarnings(array $courses): float
    {
        $total = 0;
        foreach ($courses as $course) {
            // Mock calculation: assume 70% of course price as actual earnings (after platform fees)
            $total += ($course->getPrice() * 0.7);
        }
        return $total;
    }

    /**
     * Calculate average rating across all courses
     * This is a mock implementation - in a real app, you'd have a review/rating table
     */
    private function calculateAverageRating(array $courses): float
    {
        if (empty($courses)) {
            return 0.0;
        }
        
        // Mock calculation: return average of random ratings between 3.5 and 5.0
        $totalRating = 0;
        foreach ($courses as $course) {
            $totalRating += 4.2; // Mock rating
        }
        
        return round($totalRating / count($courses), 1);
    }
}
