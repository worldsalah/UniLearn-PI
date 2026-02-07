<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\User;
use App\Repository\CourseRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class InstructorController extends AbstractController
{
    #[Route('/instructor/dashboard', name: 'app_instructor_dashboard')]
    public function dashboard(CourseRepository $courseRepository, UserRepository $userRepository): Response
    {
        // Simulating logged-in teacher with ID 1
        // TODO: Replace with real authentication after user module integration:
        // $user = $this->getUser(); // or $this->security->getUser();
        // if (!$user) { throw $this->createAccessDeniedException('Please log in'); }
        // $teacher = $user; // assuming User entity implements TeacherInterface
        
        $loggedInTeacherId = 1;
        $teacher = $userRepository->find($loggedInTeacherId);
        
        if (!$teacher || $teacher->getRole() !== 'teacher') {
            throw $this->createNotFoundException('Teacher not found');
        }

        // Get instructor's courses
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
        // Simulating logged-in teacher with ID 1
        // TODO: Replace with real authentication after user module integration:
        // $user = $this->getUser(); // or $this->security->getUser();
        // if (!$user) { throw $this->createAccessDeniedException('Please log in'); }
        // $teacher = $user; // assuming User entity implements TeacherInterface
        
        $loggedInTeacherId = 1;
        $teacher = $userRepository->find($loggedInTeacherId);
        
        if (!$teacher || $teacher->getRole() !== 'teacher') {
            throw $this->createNotFoundException('Teacher not found');
        }

        // Get only courses belonging to the logged-in teacher
        $courses = $courseRepository->findBy(['user' => $teacher]);

        return $this->render('instructor/manage-courses.html.twig', [
            'courses' => $courses,
            'teacher' => $teacher
        ]);
    }

    #[Route('/instructor/course/edit/{id}', name: 'app_instructor_edit_course', methods: ['GET', 'POST'])]
    public function editCourse(Course $course, CourseRepository $courseRepository, UserRepository $userRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Simulating logged-in teacher with ID 1
        $loggedInTeacherId = 1;
        $teacher = $userRepository->find($loggedInTeacherId);
        
        // Check if the course belongs to the logged-in teacher
        if ($course->getUser()?->getId() !== $loggedInTeacherId) {
            throw $this->createAccessDeniedException('You can only edit your own courses');
        }

        if ($request->isMethod('POST')) {
            // Update course with form data
            $course->setTitle($request->request->get('course_title'));
            $course->setShortDescription($request->request->get('short_description'));
            $course->setCategory($request->request->get('course_category'));
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
        // Simulating logged-in teacher with ID 1
        $loggedInTeacherId = 1;
        $teacher = $userRepository->find($loggedInTeacherId);
        
        // Check if the course belongs to the logged-in teacher
        if ($course->getUser()?->getId() !== $loggedInTeacherId) {
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
        // Simulating logged-in teacher with ID 1
        $loggedInTeacherId = 1;
        $teacher = $userRepository->find($loggedInTeacherId);
        
        // Check if the course belongs to the logged-in teacher
        if ($course->getUser()?->getId() !== $loggedInTeacherId) {
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
    public function students(): Response
    {
        return $this->render('instructor/students.html.twig');
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
    public function quiz(): Response
    {
        return $this->render('instructor/quiz.html.twig');
    }

    #[Route('/instructor/orders', name: 'app_instructor_orders')]
    public function orders(): Response
    {
        return $this->render('instructor/orders.html.twig');
    }

    #[Route('/instructor/edit-profile', name: 'app_instructor_edit_profile')]
    public function editProfile(): Response
    {
        return $this->render('instructor/edit-profile.html.twig');
    }

    #[Route('/instructor/payout', name: 'app_instructor_payout')]
    public function payout(): Response
    {
        return $this->render('instructor/payout.html.twig');
    }

    #[Route('/instructor/delete-account', name: 'app_instructor_delete_account')]
    public function deleteAccount(): Response
    {
        return $this->render('instructor/delete-account.html.twig');
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
