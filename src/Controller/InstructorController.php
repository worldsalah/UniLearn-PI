<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\CourseReview;
use App\Entity\Enrollment;
use App\Entity\User;
use App\Form\ProfileType;
use App\Repository\CategoryRepository;
use App\Repository\CourseRepository;
use App\Repository\EnrollmentRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\JobRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;

final class InstructorController extends AbstractController
{
    #[Route('/instructor/dashboard', name: 'app_instructor_dashboard')]
    public function dashboard(CourseRepository $courseRepository, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        // Get the currently logged-in user
        $user = $this->getUser();

        if ($user === null) {
            // If no user is logged in, redirect to login
            return $this->redirectToRoute('app_login');
        }

        // Allow all users to access the dashboard, not just teachers
        $teacher = $userRepository->find($user->getId());

        if ($teacher === null) {
            throw $this->createNotFoundException('User not found');
        }

        // Get courses associated with this user (if any)
        $courses = $courseRepository->findBy(['user' => $teacher]);

        // Calculate statistics
        $totalCourses = count($courses);
        $totalStudents = $this->calculateTotalStudents($courses);
        $totalEarnings = $teacher->getIncome() ?? 0;
        $averageRating = $this->calculateAverageRating($courses);
        
        // Calculate course status counts
        $activeCourses = 0;
        $pendingCourses = 0;
        $draftCourses = 0;
        $totalCourseValue = 0;
        
        foreach ($courses as $course) {
            $status = $course->getStatus();
            if ($status === 'published' || $status === 'active' || $status === 'live') {
                $activeCourses++;
            } elseif ($status === 'pending' || $status === 'review') {
                $pendingCourses++;
            } else {
                $draftCourses++;
            }
            $totalCourseValue += $course->getPrice();
        }
        
        // Calculate student growth (new students this month vs last month)
        $enrollmentRepository = $entityManager->getRepository(Enrollment::class);
        
        $currentMonth = new \DateTime('first day of this month');
        $lastMonth = new \DateTime('first day of last month');
        $currentMonthEnd = new \DateTime('last day of this month');
        $lastMonthEnd = new \DateTime('last day of last month');
        
        $currentMonthStudents = 0;
        $lastMonthStudents = 0;
        $currentMonthEarnings = 0;
        $lastMonthEarnings = 0;
        $monthlyEarnings = [];
        $monthlyStudents = [];
        
        foreach ($courses as $course) {
            $enrollments = $enrollmentRepository->findBy(['course' => $course]);
            
            foreach ($enrollments as $enrollment) {
                $enrollDate = $enrollment->getEnrolledAt();
                if ($enrollDate !== null) {
                    // Current month stats
                    if ($enrollDate >= $currentMonth && $enrollDate <= $currentMonthEnd) {
                        $currentMonthEarnings += $course->getPrice();
                        $currentMonthStudents++;
                    }
                    // Last month stats
                    elseif ($enrollDate >= $lastMonth && $enrollDate <= $lastMonthEnd) {
                        $lastMonthEarnings += $course->getPrice();
                        $lastMonthStudents++;
                    }
                    
                    // Build monthly data for chart (last 7 months)
                    $monthKey = $enrollDate->format('Y-m');
                    if (!isset($monthlyEarnings[$monthKey])) {
                        $monthlyEarnings[$monthKey] = 0;
                        $monthlyStudents[$monthKey] = 0;
                    }
                    $monthlyEarnings[$monthKey] += $course->getPrice();
                    $monthlyStudents[$monthKey]++;
                }
            }
        }
        
        // Sort and get last 7 months for chart
        krsort($monthlyEarnings);
        $chartData = array_slice(array_values($monthlyEarnings), 0, 7);
        $chartLabels = array_slice(array_keys($monthlyEarnings), 0, 7);
        $studentChartData = array_slice(array_values($monthlyStudents), 0, 7);
        
        // Reverse for chronological order
        $chartData = array_reverse($chartData);
        $chartLabels = array_reverse($chartLabels);
        $studentChartData = array_reverse($studentChartData);
        
        // Format labels
        $months = ['01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr', '05' => 'May', '06' => 'Jun', '07' => 'Jul', '08' => 'Aug', '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dec'];
        $formattedLabels = [];
        foreach ($chartLabels as $label) {
            $parts = explode('-', $label);
            $formattedLabels[] = $months[$parts[1]] . ' ' . substr($parts[0], 2);
        }
        
        // Calculate averages and percentage changes
        $averageEarnings = $totalEarnings > 0 ? $totalEarnings / max(count($monthlyEarnings), 1) : 0;
        
        // Earnings percentage change
        $earningsPercentChange = 0;
        if ($lastMonthEarnings > 0) {
            $earningsPercentChange = (($currentMonthEarnings - $lastMonthEarnings) / $lastMonthEarnings) * 100;
        } elseif ($currentMonthEarnings > 0) {
            $earningsPercentChange = 100;
        }
        
        // Students percentage change
        $studentsPercentChange = 0;
        if ($lastMonthStudents > 0) {
            $studentsPercentChange = (($currentMonthStudents - $lastMonthStudents) / $lastMonthStudents) * 100;
        } elseif ($currentMonthStudents > 0) {
            $studentsPercentChange = 100;
        }
        
        // Courses percentage change (courses created this month vs last month)
        $coursesThisMonth = 0;
        $coursesLastMonth = 0;
        foreach ($courses as $course) {
            $createdAt = $course->getCreatedAt();
            if ($createdAt !== null) {
                if ($createdAt >= $currentMonth && $createdAt <= $currentMonthEnd) {
                    $coursesThisMonth++;
                } elseif ($createdAt >= $lastMonth && $createdAt <= $lastMonthEnd) {
                    $coursesLastMonth++;
                }
            }
        }
        
        $coursesPercentChange = 0;
        if ($coursesLastMonth > 0) {
            $coursesPercentChange = (($coursesThisMonth - $coursesLastMonth) / $coursesLastMonth) * 100;
        } elseif ($coursesThisMonth > 0) {
            $coursesPercentChange = 100;
        }

        $earningsData = [
            'currentMonth' => $currentMonthEarnings,
            'lastMonth' => $lastMonthEarnings,
            'average' => $averageEarnings,
            'percentChange' => $earningsPercentChange,
            'chartData' => $chartData ?: [0, 0, 0, 0, 0, 0, 0],
            'chartLabels' => $formattedLabels ?: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
            'studentChartData' => $studentChartData ?: [0, 0, 0, 0, 0, 0, 0],
        ];
        
        $courseStats = [
            'total' => $totalCourses,
            'active' => $activeCourses,
            'pending' => $pendingCourses,
            'draft' => $draftCourses,
            'totalValue' => $totalCourseValue,
            'percentChange' => $coursesPercentChange,
            'newThisMonth' => $coursesThisMonth,
        ];
        
        $studentStats = [
            'total' => $totalStudents,
            'newThisMonth' => $currentMonthStudents,
            'percentChange' => $studentsPercentChange,
        ];

        return $this->render('instructor/dashboard.html.twig', [
            'teacher' => $teacher,
            'courses' => $courses,
            'totalCourses' => $totalCourses,
            'totalStudents' => $totalStudents,
            'totalEarnings' => $totalEarnings,
            'averageRating' => $averageRating,
            'earningsData' => $earningsData,
            'courseStats' => $courseStats,
            'studentStats' => $studentStats,
        ]);
    }

    #[Route('/instructor/manage-courses', name: 'app_instructor_manage_courses')]
    public function manageCourses(CourseRepository $courseRepository, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        // Get the currently logged-in user
        $user = $this->getUser();

        if ($user === null) {
            // If no user is logged in, redirect to login
            return $this->redirectToRoute('app_login');
        }

        // Allow all users to access course management
        $teacher = $userRepository->find($user->getId());

        if ($teacher === null) {
            throw $this->createNotFoundException('User not found');
        }

        // Get courses belonging to the logged-in user (if any)
        $courses = $courseRepository->findBy(['user' => $teacher]);
        
        // Calculate enrollment counts for each course
        $enrollmentRepository = $entityManager->getRepository(Enrollment::class);
        $courseEnrollments = [];
        
        // Calculate course statistics
        $totalCourses = count($courses);
        $activeCourses = 0;
        $pendingCourses = 0;
        $totalValue = 0;
        $totalStudents = 0;
        
        $currentMonth = new \DateTime('first day of this month');
        $lastMonth = new \DateTime('first day of last month');
        $currentMonthCourses = 0;
        $lastMonthCourses = 0;
        $currentMonthActive = 0;
        $lastMonthActive = 0;
        
        foreach ($courses as $course) {
            // Count enrollments
            $enrollments = $enrollmentRepository->findBy(['course' => $course]);
            $courseEnrollments[$course->getId()] = count($enrollments);
            $totalStudents += count($enrollments);
            
            // Status counts
            $status = $course->getStatus();
            if ($status === 'active' || $status === 'live' || $status === 'published') {
                $activeCourses++;
            } elseif ($status === 'pending' || $status === 'review') {
                $pendingCourses++;
            }
            
            // Total value
            $totalValue += $course->getPrice();
            
            // Monthly course creation for percentage change
            $createdAt = $course->getCreatedAt();
            if ($createdAt !== null) {
                if ($createdAt >= $currentMonth) {
                    $currentMonthCourses++;
                    if ($status === 'active' || $status === 'live' || $status === 'published') {
                        $currentMonthActive++;
                    }
                } elseif ($createdAt >= $lastMonth && $createdAt < $currentMonth) {
                    $lastMonthCourses++;
                    if ($status === 'active' || $status === 'live' || $status === 'published') {
                        $lastMonthActive++;
                    }
                }
            }
        }
        
        // Calculate percentage changes
        $totalPercentChange = $lastMonthCourses > 0 ? (($currentMonthCourses - $lastMonthCourses) / $lastMonthCourses) * 100 : ($currentMonthCourses > 0 ? 100 : 0);
        $activePercentChange = $lastMonthActive > 0 ? (($currentMonthActive - $lastMonthActive) / $lastMonthActive) * 100 : ($currentMonthActive > 0 ? 100 : 0);
        
        $courseStats = [
            'total' => $totalCourses,
            'active' => $activeCourses,
            'pending' => $pendingCourses,
            'totalValue' => $totalValue,
            'totalStudents' => $totalStudents,
            'totalPercentChange' => $totalPercentChange,
            'activePercentChange' => $activePercentChange,
        ];

        return $this->render('instructor/manage-courses.html.twig', [
            'courses' => $courses,
            'teacher' => $teacher,
            'courseEnrollments' => $courseEnrollments,
            'courseStats' => $courseStats,
        ]);
    }

    #[Route('/instructor/course/edit/{id}', name: 'app_instructor_edit_course', methods: ['GET', 'POST'])]
    public function editCourse(Course $course, CourseRepository $courseRepository, CategoryRepository $categoryRepository, UserRepository $userRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Get the currently logged-in user
        $user = $this->getUser();

        if ($user === null) {
            // If no user is logged in, redirect to login
            return $this->redirectToRoute('app_login');
        }

        // Allow all users to edit courses, but check ownership
        $teacher = $userRepository->find($user->getId());

        if ($teacher === null) {
            throw $this->createNotFoundException('User not found');
        }

        // Check if the course belongs to the logged-in user
        $courseUser = $course->getUser();
        if ($courseUser === null || $courseUser->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('You can only edit your own courses');
        }

        if ($request->isMethod('POST')) {
            // Update course with form data
            $title = $request->request->get('course_title');
            $shortDescription = $request->request->get('short_description');
            
            if (is_string($title)) {
                $course->setTitle($title);
            }
            if (is_string($shortDescription)) {
                $course->setShortDescription($shortDescription);
            }

            // Handle category - fetch entity from repository
            $categoryId = $request->request->get('course_category');
            if ($categoryId !== null && $categoryId !== '') {
                $category = $categoryRepository->find($categoryId);
                if ($category !== null) {
                    $course->setCategory($category);
                }
            }

            $level = $request->request->get('course_level');
            if (is_string($level)) {
                $course->setLevel($level);
            }
            $course->setPrice((float) $request->request->get('course_price'));
            $language = $request->request->get('language');
            if (is_string($language)) {
                $course->setLanguage($language);
            }
            $course->setDuration((float) $request->request->get('duration'));
            $requirements = $request->request->get('requirements');
            if (is_string($requirements)) {
                $course->setRequirements($requirements);
            }
            $learningOutcomes = $request->request->get('learning_outcomes');
            if (is_string($learningOutcomes)) {
                $course->setLearningOutcomes($learningOutcomes);
            }
            $targetAudience = $request->request->get('target_audience');
            if (is_string($targetAudience)) {
                $course->setTargetAudience($targetAudience);
            }

            $entityManager->flush();

            // Show success notification
            $this->addFlash('course_updated', [
                'title' => 'Course Updated Successfully!',
                'message' => "The course '{$course->getTitle()}' has been updated.",
                'type' => 'success',
                'icon' => 'fas fa-edit',
            ]);

            return $this->redirectToRoute('app_instructor_manage_courses');
        }

        // Show edit form for GET request
        return $this->render('instructor/edit-course.html.twig', [
            'course' => $course,
            'teacher' => $teacher,
        ]);
    }

    #[Route('/instructor/course/detail/{id}', name: 'app_instructor_course_detail')]
    public function courseDetail(Course $course, UserRepository $userRepository, EnrollmentRepository $enrollmentRepository): Response
    {
        // Get the currently logged-in user
        $user = $this->getUser();

        if ($user === null) {
            // If no user is logged in, redirect to login
            return $this->redirectToRoute('app_login');
        }

        // Allow all users to view courses, but check ownership for editing
        $teacher = $userRepository->find($user->getId());

        if ($teacher === null) {
            throw $this->createNotFoundException('User not found');
        }

        // Allow all users to view courses (remove ownership check for viewing)
        // Only check ownership for editing/deleting operations

        // Check if user is enrolled
        $isEnrolled = false;
        $enrollment = $enrollmentRepository->findOneBy(['user' => $user, 'course' => $course]);
        $isEnrolled = $enrollment !== null;

        return $this->render('instructor/course-detail.html.twig', [
            'course' => $course,
            'teacher' => $teacher,
            'isEnrolled' => $isEnrolled,
        ]);
    }

    #[Route('/instructor/course/delete/{id}', name: 'app_instructor_delete_course')]
    public function deleteCourse(Course $course, EntityManagerInterface $entityManager, UserRepository $userRepository): RedirectResponse
    {
        // Get the currently logged-in user
        $user = $this->getUser();

        if ($user === null) {
            // If no user is logged in, redirect to login
            return $this->redirectToRoute('app_login');
        }

        // Allow all users to delete courses, but check ownership
        $teacher = $userRepository->find($user->getId());

        if ($teacher === null) {
            throw $this->createNotFoundException('User not found');
        }

        // Check if the course belongs to the logged-in user
        $courseUser = $course->getUser();
        if ($courseUser === null || $courseUser->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('You can only delete your own courses');
        }

        $courseTitle = $course->getTitle();

        // Delete related records manually to avoid foreign key constraints
        $lessonCompletionRepository = $entityManager->getRepository(\App\Entity\LessonCompletion::class);
        $enrollmentRepository = $entityManager->getRepository(\App\Entity\Enrollment::class);
        $certificateRepository = $entityManager->getRepository(\App\Entity\Certificate::class);
        $quizRepository = $entityManager->getRepository(\App\Entity\Quiz::class);
        
        // Delete lesson completions for this course
        $lessonCompletions = $lessonCompletionRepository->findBy(['course' => $course]);
        foreach ($lessonCompletions as $completion) {
            $entityManager->remove($completion);
        }
        
        // Delete enrollments for this course
        $enrollments = $enrollmentRepository->findBy(['course' => $course]);
        foreach ($enrollments as $enrollment) {
            $entityManager->remove($enrollment);
        }
        
        // Delete certificates for this course
        $certificates = $certificateRepository->findBy(['course' => $course]);
        foreach ($certificates as $certificate) {
            $entityManager->remove($certificate);
        }
        
        // Delete quizzes for this course
        $quizzes = $quizRepository->findBy(['course' => $course]);
        foreach ($quizzes as $quiz) {
            $entityManager->remove($quiz);
        }
        
        // Flush all deletions before removing course
        $entityManager->flush();

        // Remove the course (chapters and lessons will cascade)
        $entityManager->remove($course);
        $entityManager->flush();

        $this->addFlash('course_deleted', [
            'title' => 'Course Deleted Successfully!',
            'message' => "The course '{$courseTitle}' has been permanently removed from your catalog.",
            'type' => 'success',
            'icon' => 'fas fa-check-circle',
        ]);

        return $this->redirectToRoute('app_instructor_manage_courses');
    }

    #[Route('/instructor/students', name: 'app_instructor_students')]
    public function students(UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        // Get the currently logged-in instructor
        $instructor = $this->getUser();

        if ($instructor === null) {
            return $this->redirectToRoute('app_login');
        }

        // Get students enrolled in the instructor's courses
        $enrollmentRepository = $entityManager->getRepository(Enrollment::class);
        $courseRepository = $entityManager->getRepository(Course::class);
        
        // Get all courses by this instructor
        $instructorCourses = $courseRepository->findBy(['user' => $instructor]);
        
        // Get all enrollments for instructor's courses
        $enrollments = $enrollmentRepository->createQueryBuilder('e')
            ->innerJoin('e.course', 'c')
            ->where('c.user = :instructor')
            ->setParameter('instructor', $instructor)
            ->getQuery()
            ->getResult();
        
        // Extract unique students from enrollments
        $students = [];
        $studentIds = [];
        foreach ($enrollments as $enrollment) {
            $student = $enrollment->getUser();
            if (!in_array($student->getId(), $studentIds, true)) {
                $students[] = $student;
                $studentIds[] = $student->getId();
            }
        }

        // Calculate statistics
        $totalUsers = count($students);
        $activeUsers = count(array_filter($students, fn ($user) => 'active' === $user->getStatus()));
        $inactiveUsers = count(array_filter($students, fn ($user) => 'inactive' === $user->getStatus()));

        // Get role statistics
        $roleStats = [];
        foreach ($students as $user) {
            $roleName = $user->getRole() ? $user->getRole()->getName() : 'Unknown';
            if (!isset($roleStats[$roleName])) {
                $roleStats[$roleName] = 0;
            }
            ++$roleStats[$roleName];
        }

        return $this->render('instructor/students.html.twig', [
            'users' => $students,
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'inactiveUsers' => $inactiveUsers,
            'roleStats' => $roleStats,
            'instructorCourses' => $instructorCourses,
            'totalEnrollments' => count($enrollments),
            'teacher' => [
                'name' => $instructor->getFullName(),
                'email' => $instructor->getEmail(),
                'role' => $instructor->getRole(),
            ],
        ]);
    }

    #[Route('/instructor/earnings', name: 'app_instructor_earnings')]
    public function earnings(): Response
    {
        return $this->render('instructor/earnings.html.twig');
    }

    #[Route('/instructor/reviews', name: 'app_instructor_reviews')]
    public function reviews(EntityManagerInterface $entityManager): Response
    {
        // Get the currently logged-in instructor
        $instructor = $this->getUser();

        if ($instructor === null) {
            return $this->redirectToRoute('app_login');
        }

        // Get reviews for instructor's courses
        $reviewRepository = $entityManager->getRepository(CourseReview::class);
        $courseRepository = $entityManager->getRepository(Course::class);
        
        // Get all courses by this instructor
        $instructorCourses = $courseRepository->findBy(['user' => $instructor]);
        
        // Get all reviews for instructor's courses
        $reviews = $reviewRepository->createQueryBuilder('r')
            ->innerJoin('r.course', 'c')
            ->where('c.user = :instructor')
            ->setParameter('instructor', $instructor)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
        
        // Calculate statistics
        $totalReviews = count($reviews);
        $averageRating = 0;
        $ratingDistribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        
        if ($totalReviews > 0) {
            $totalRating = 0;
            foreach ($reviews as $review) {
                $rating = $review->getRating();
                $totalRating += $rating;
                if (isset($ratingDistribution[$rating])) {
                    $ratingDistribution[$rating]++;
                }
            }
            $averageRating = round($totalRating / $totalReviews, 1);
        }
        
        // Get total enrolled students across all courses
        $enrollmentRepository = $entityManager->getRepository(Enrollment::class);
        $totalEnrollments = $enrollmentRepository->createQueryBuilder('e')
            ->select('COUNT(DISTINCT e.user)')
            ->innerJoin('e.course', 'c')
            ->where('c.user = :instructor')
            ->setParameter('instructor', $instructor)
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('instructor/reviews.html.twig', [
            'reviews' => $reviews,
            'instructorCourses' => $instructorCourses,
            'totalReviews' => $totalReviews,
            'averageRating' => $averageRating,
            'ratingDistribution' => $ratingDistribution,
            'totalEnrollments' => $totalEnrollments,
            'totalCourses' => count($instructorCourses),
            'teacher' => [
                'name' => $instructor->getFullName(),
                'email' => $instructor->getEmail(),
                'avatar' => $instructor->getProfileImage() ?: 'assets/images/avatar/01.jpg',
            ],
        ]);
    }

    #[Route('/instructor/quiz', name: 'app_instructor_quiz')]
    public function quiz(EntityManagerInterface $entityManager): Response
    {
        // Get the currently logged-in user
        $user = $this->getUser();

        if ($user === null) {
            return $this->redirectToRoute('app_login');
        }

        // Get courses for this instructor (both active and inactive for quiz creation)
        $courses = $entityManager->getRepository('App\Entity\Course')->findBy(['user' => $user]);

        // Debug: Log course information
        error_log('Quiz Page - User ID: '.$user->getId());
        error_log('Quiz Page - Courses found: '.count($courses));

        foreach ($courses as $course) {
            error_log('Quiz Page - Course: '.$course->getTitle().' (Status: '.$course->getStatus().', ID: '.$course->getId().')');
        }

        // Get quizzes for this instructor's courses (from all courses for debugging)
        $quizzes = [];
        foreach ($courses as $course) {
            // Get quizzes from all courses (remove status restriction for debugging)
            error_log('Processing course: '.$course->getTitle().' (Status: '.$course->getStatus().')');
            $courseQuizzes = $entityManager->getRepository('App\Entity\Quiz')->findBy(['course' => $course]);
            error_log('Found '.count($courseQuizzes).' quizzes for course '.$course->getTitle());
            $quizzes = array_merge($quizzes, $courseQuizzes);
        }

        error_log('Total quizzes found: '.count($quizzes));

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
                    $quizScoreSum += $result->getScore();
                }
                $avgScore += $quizScoreSum / $quizResults->count();
                ++$scoreCount;
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
            'averageRating' => $this->calculateAverageRating($courses),
        ];

        return $this->render('instructor/quiz.html.twig', [
            'quizzes' => $quizzes,
            'courses' => $courses,
            'teacher' => $instructor,
            'totalQuestions' => $totalQuestions,
            'totalAttempts' => $totalAttempts,
            'averageScore' => $avgScore,
        ]);
    }

    #[Route('/instructor/orders', name: 'app_instructor_orders')]
    public function orders(
        OrderRepository $orderRepository,
        JobRepository $jobRepository,
        ProductRepository $productRepository,
    ): Response {
        $user = $this->getUser();

        if ($user === null) {
            return $this->redirectToRoute('app_login');
        }

        // Get orders where user is the buyer
        $buyerOrders = $orderRepository->findBy(['buyer' => $user], ['createdAt' => 'DESC']);

        // Get orders for products the user is selling (as freelancer)
        $userProducts = $productRepository->findBy(['freelancer' => $user]);
        $sellerOrders = [];
        foreach ($userProducts as $product) {
            $productOrders = $orderRepository->findBy(['product' => $product], ['createdAt' => 'DESC']);
            $sellerOrders = array_merge($sellerOrders, $productOrders);
        }

        // Get jobs posted by the user
        $postedJobs = $jobRepository->findBy(['client' => $user], ['createdAt' => 'DESC']);

        // Calculate stats
        $stats = [
            'totalBuyerOrders' => count($buyerOrders),
            'totalSellerOrders' => count($sellerOrders),
            'totalJobs' => count($postedJobs),
            'totalSpent' => array_sum(array_map(fn($o) => $o->getTotalPrice(), $buyerOrders)),
            'totalEarned' => array_sum(array_map(fn($o) => $o->getTotalPrice(), $sellerOrders)),
            'pendingOrders' => count(array_filter(array_merge($buyerOrders, $sellerOrders), fn($o) => $o->getStatus() === 'pending')),
            'completedOrders' => count(array_filter(array_merge($buyerOrders, $sellerOrders), fn($o) => $o->getStatus() === 'completed')),
        ];

        return $this->render('instructor/orders.html.twig', [
            'buyerOrders' => $buyerOrders,
            'sellerOrders' => $sellerOrders,
            'postedJobs' => $postedJobs,
            'stats' => $stats,
            'user' => $user,
        ]);
    }

    #[Route('/instructor/edit-profile', name: 'app_instructor_edit_profile', methods: ['GET', 'POST'])]
    public function editProfile(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        // Get the currently logged-in user
        $user = $this->getUser();

        if ($user === null) {
            return $this->redirectToRoute('app_login');
        }

        // Create the form
        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle profile image upload
            $profileImageFile = $form->get('profileImage')->getData();
            if (null !== $profileImageFile) {
                $newFilename = uniqid().'.'.$profileImageFile->guessExtension();

                // Move the file to the uploads directory
                $projectDir = $this->getParameter('kernel.project_dir');
                $profileImageFile->move(
                    (is_string($projectDir) ? $projectDir : '') .'/public/uploads/profiles',
                    $newFilename
                );

                // Update user profile image path
                $user->setProfileImage('/uploads/profiles/'.$newFilename);
            }

            // Handle password change
            $plainPassword = $form->get('plainPassword')->get('first')->getData();
            if ($plainPassword !== null && is_string($plainPassword) && $plainPassword !== '') {
                // Verify current password
                $currentPassword = $form->get('currentPassword')->getData();
                if ($currentPassword !== null && $user instanceof \Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface) {
                    if ($passwordHasher->isPasswordValid($user, $currentPassword)) {
                        // Hash and set the new password
                        $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                        $user->setPassword($hashedPassword);

                        $this->addFlash('success', 'Your password has been updated successfully.');
                    } else {
                        $this->addFlash('error', 'Current password is incorrect. Please try again.');

                        return $this->render('instructor/edit-profile.html.twig', [
                            'form' => $form->createView(),
                            'user' => $user,
                        ]);
                    }
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
            'user' => $user,
        ]);
    }

    #[Route('/instructor/payout', name: 'app_instructor_payout')]
    public function payout(EntityManagerInterface $entityManager): Response
    {
        // Get the currently logged-in instructor
        $instructor = $this->getUser();

        if ($instructor === null) {
            return $this->redirectToRoute('app_login');
        }

        // Get instructor's courses
        $courseRepository = $entityManager->getRepository(Course::class);
        $enrollmentRepository = $entityManager->getRepository(Enrollment::class);
        
        $instructorCourses = $courseRepository->findBy(['user' => $instructor]);
        
        // Calculate earnings from course enrollments
        $totalEarnings = 0;
        $monthlyEarnings = [];
        $courseEarnings = [];
        
        $currentMonth = new \DateTime('first day of this month');
        $currentMonthString = $currentMonth->format('Y-m');
        
        foreach ($instructorCourses as $course) {
            $enrollments = $enrollmentRepository->findBy(['course' => $course]);
            $price = $course->getPrice();
            $courseRevenue = count($enrollments) * ($price ?? 0.0);
            $totalEarnings += $courseRevenue;
            
            $courseEarnings[] = [
                'course' => $course,
                'enrollments' => count($enrollments),
                'revenue' => $courseRevenue,
            ];
            
            // Calculate monthly earnings
            foreach ($enrollments as $enrollment) {
                $enrollDate = $enrollment->getEnrolledAt();
                if ($enrollDate !== null) {
                    $monthKey = $enrollDate->format('Y-m');
                    if (!isset($monthlyEarnings[$monthKey])) {
                        $monthlyEarnings[$monthKey] = 0;
                    }
                    $monthlyEarnings[$monthKey] += $course->getPrice() ?? 0.0;
                }
            }
        }
        
        // Sort monthly earnings by date (most recent first)
        krsort($monthlyEarnings);
        
        // Calculate pending payout (current month)
        $pendingPayout = $monthlyEarnings[$currentMonthString] ?? 0;
        
        // Calculate available for withdrawal (previous months)
        $availableForWithdrawal = $totalEarnings - $pendingPayout;
        
        // Get total students
        $totalStudents = $enrollmentRepository->createQueryBuilder('e')
            ->select('COUNT(DISTINCT e.user)')
            ->innerJoin('e.course', 'c')
            ->where('c.user = :instructor')
            ->setParameter('instructor', $instructor)
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('instructor/payout.html.twig', [
            'instructorCourses' => $instructorCourses,
            'totalEarnings' => $totalEarnings,
            'availableForWithdrawal' => $availableForWithdrawal,
            'pendingPayout' => $pendingPayout,
            'monthlyEarnings' => $monthlyEarnings,
            'courseEarnings' => $courseEarnings,
            'totalStudents' => $totalStudents,
            'totalCourses' => count($instructorCourses),
            'teacher' => [
                'name' => $instructor->getFullName(),
                'email' => $instructor->getEmail(),
                'avatar' => $instructor->getProfileImage() ?: 'assets/images/avatar/01.jpg',
            ],
        ]);
    }

    #[Route('/instructor/delete-account', name: 'app_instructor_delete_account', methods: ['GET', 'POST'])]
    public function deleteAccount(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        // Get the currently logged-in user
        $user = $this->getUser();

        if ($user === null) {
            return $this->redirectToRoute('app_login');
        }

        // Create a simple form for confirmation
        $form = $this->createFormBuilder()
            ->add('password', \Symfony\Component\Form\Extension\Core\Type\PasswordType::class, [
                'label' => 'Enter your password to confirm account deletion',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter your password',
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Please enter your password to confirm account deletion',
                    ]),
                ],
            ])
            ->add('confirm', \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class, [
                'label' => 'I understand that this action cannot be undone and will permanently delete all my data',
                'required' => true,
                'constraints' => [
                    new Assert\IsTrue([
                        'message' => 'You must confirm that you understand the consequences of deleting your account',
                    ]),
                ],
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $password = $form->get('password')->getData();

            // Verify password
            if ($user instanceof \Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface && $passwordHasher->isPasswordValid($user, $password)) {
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
            }
            $this->addFlash('error', 'Incorrect password. Please try again.');
        }

        return $this->render('instructor/delete-account.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/instructor/settings', name: 'app_instructor_settings')]
    public function settings(): Response
    {
        return $this->render('instructor/settings.html.twig');
    }

    /**
     * Calculate total number of students across all courses
     * This is a mock implementation - in a real app, you'd have an enrollment table.
     */
    private function calculateTotalStudents(array $courses): int
    {
        // Mock calculation: assume average of 15 students per course
        return count($courses) * 15;
    }

    /**
     * Calculate total earnings from all courses.
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
     * This is a mock implementation - in a real app, you'd have a review/rating table.
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
