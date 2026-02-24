<?php

namespace App\Controller;

use App\Entity\Question;
use App\Entity\Quiz;
use App\Repository\QuestionRepository;
use App\Repository\QuizRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidator;

class PageController extends AbstractController
{
    private QuizRepository $quizRepository;
    private QuestionRepository $questionRepository;
    private EntityManagerInterface $entityManager;
    private SymfonyValidator $validator;

    public function __construct(
        QuizRepository $quizRepository,
        QuestionRepository $questionRepository,
        EntityManagerInterface $entityManager,
        SymfonyValidator $validator,
    ) {
        $this->quizRepository = $quizRepository;
        $this->questionRepository = $questionRepository;
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }

    #[Route('/about', name: 'app_about')]
    public function about(): Response
    {
        // Create a simple about page using base template
        return $this->render('base.html.twig', [
            'title' => 'About - Unilearn',
            'content' => 'About Unilearn - Learning Platform',
        ]);
    }

    #[Route('/contact', name: 'app_contact')]
    public function contact(): Response
    {
        // Create a simple contact page using base template
        return $this->render('base.html.twig', [
            'title' => 'Contact - Unilearn',
            'content' => 'Contact Unilearn - Get in Touch',
        ]);
    }

    #[Route('/register', name: 'app_register')]
    public function register(): Response
    {
        return $this->render('auth/register.html.twig');
    }

    #[Route('/home-variant', name: 'app_home_variant')]
    public function homeVariant(): Response
    {
        return $this->render('home/index-2.html.twig');
    }

    #[Route('/home-variant-3', name: 'app_home_variant_3')]
    public function homeVariant3(): Response
    {
        return $this->render('home/index-3.html.twig');
    }

    #[Route('/course-detail', name: 'app_course_detail')]
    public function courseDetail(): Response
    {
        return $this->render('course/detail.html.twig');
    }

    #[Route('/sign-in', name: 'app_sign_in')]
    public function signIn(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('auth/sign-in.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/sign-up', name: 'app_sign_up')]
    public function signUp(): Response
    {
        return $this->redirectToRoute('app_register');
    }

    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(): Response
    {
        return $this->render('auth/forgot-password.html.twig');
    }

    #[Route('/student-dashboard', name: 'app_student_dashboard')]
    public function studentDashboard(): Response
    {
        // Get the currently logged-in user
        $user = $this->getUser();

        if (!$user) {
            // If no user is logged in, redirect to login
            return $this->redirectToRoute('app_login');
        }

        // Allow all users to access student dashboard
        return $this->render('student/dashboard.html.twig');
    }

    #[Route('/admin-dashboard', name: 'app_admin_dashboard')]
    public function adminDashboard(EntityManagerInterface $entityManager): Response
    {
        // Get real data from database
        $courseRepository = $entityManager->getRepository(\App\Entity\Course::class);
        $quizRepository = $entityManager->getRepository(Quiz::class);
        $userRepository = $entityManager->getRepository(\App\Entity\User::class);

        $totalCourses = count($courseRepository->findAll());
        $totalQuizzes = count($quizRepository->findAll());
        $totalUsers = count($userRepository->findAll());

        // Get recent activities (mock data for now)
        $recentCourses = [];
        $recentQuizzes = [];
        $recentUsers = [];

        return $this->render('admin/dashboard.html.twig', [
            'totalCourses' => $totalCourses,
            'totalQuizzes' => $totalQuizzes,
            'totalUsers' => $totalUsers,
            'recentCourses' => $recentCourses,
            'recentQuizzes' => $recentQuizzes,
            'recentUsers' => $recentUsers,
        ]);
    }

    #[Route('/blog-grid', name: 'app_blog_grid')]
    public function blogGrid(): Response
    {
        return $this->render('blog/grid.html.twig');
    }

    #[Route('/shop', name: 'app_shop')]
    public function shop(): Response
    {
        return $this->render('shop/index.html.twig');
    }

    #[Route('/pricing', name: 'app_pricing')]
    public function pricing(): Response
    {
        return $this->render('utility/pricing.html.twig');
    }

    #[Route('/error-404', name: 'app_error_404')]
    public function error404(): Response
    {
        return $this->render('utility/error-404.html.twig');
    }

    #[Route('/home-variant-4', name: 'app_home_variant_4')]
    public function homeVariant4(): Response
    {
        return $this->render('home/index-4.html.twig');
    }

    #[Route('/course-list', name: 'app_course_list')]
    public function courseList(EntityManagerInterface $entityManager): Response
    {
        // Get all courses from database
        $courses = $entityManager->getRepository(\App\Entity\Course::class)->findAll();

        // Prepare course data for template
        $courseData = [];
        foreach ($courses as $course) {
            $courseData[] = [
                'id' => $course->getId(),
                'title' => $course->getTitle(),
                'shortDescription' => $course->getShortDescription(),
                'category' => $course->getCategory(),
                'level' => $course->getLevel(),
                'price' => $course->getPrice(),
                'status' => $course->getStatus() ?? 'pending',
                'createdAt' => $course->getCreatedAt() ? $course->getCreatedAt()->format('d M Y') : 'Unknown',
                'thumbnailUrl' => $course->getThumbnailUrl(),
                'instructor' => [
                    'name' => $course->getUser() ? $course->getUser()->getFullName() : 'Unknown',
                    'image' => null,
                ],
                'levelClass' => $this->getLevelBadgeClass($course->getLevel()),
                'statusClass' => $this->getStatusBadgeClass($course->getStatus()),
            ];
        }

        return $this->render('admin/course-list.html.twig', [
            'courses' => $courseData,
        ]);
    }

    #[Route('/instructor-list', name: 'app_instructor_list')]
    public function instructorList(): Response
    {
        return $this->render('instructor/list.html.twig');
    }

    #[Route('/admin-instructor-list', name: 'admin_instructor_list')]
    public function adminInstructorList(EntityManagerInterface $entityManager): Response
    {
        $userRepository = $entityManager->getRepository(\App\Entity\User::class);
        $instructors = $userRepository->findBy(['roles' => ['ROLE_INSTRUCTOR']]);

        $instructorData = [];
        foreach ($instructors as $instructor) {
            // Calculate actual course count from course assignments
            $coursesCount = count($instructor->getCourses());

            $instructorData[] = [
                'id' => $instructor->getId(),
                'name' => $instructor->getFullName() ?? 'Unknown',
                'email' => $instructor->getEmail(),
                'coursesCount' => $coursesCount,
                'rating' => 4.5, // Mock rating for now
                'joinedAt' => $instructor->getCreatedAt() ? $instructor->getCreatedAt()->format('M Y') : 'Unknown',
            ];
        }

        return $this->render('admin/instructor-list.html.twig', [
            'instructors' => $instructorData,
        ]);
    }

    #[Route('/admin-instructor-detail/{id}', name: 'admin_instructor_detail')]
    public function adminInstructorDetail(int $id): Response
    {
        return $this->render('admin/instructor-detail.html.twig');
    }

    #[Route('/admin-instructor-requests', name: 'admin_instructor_requests')]
    public function adminInstructorRequests(): Response
    {
        return $this->render('admin/instructor-requests.html.twig');
    }

    #[Route('/admin-review-list', name: 'admin_review_list')]
    public function adminReviewList(): Response
    {
        return $this->render('admin/review-list.html.twig');
    }

    #[Route('/admin-student-list', name: 'admin_student_list')]
    public function adminStudentList(Request $request, EntityManagerInterface $entityManager): Response
    {
        $userRepository = $entityManager->getRepository(\App\Entity\User::class);

        // Get search and filter parameters
        $search = $request->query->get('search', '');
        $sortBy = $request->query->get('sort', 'createdAt');
        $sortOrder = $request->query->get('order', 'desc');
        $status = $request->query->get('status', '');
        $role = $request->query->get('role', '');
        $isAjax = $request->query->get('ajax', false);

        // Build query
        $qb = $userRepository->createQueryBuilder('u');

        // Join role to get role name
        $qb->leftJoin('u.role', 'r')
           ->addSelect('r');

        // Apply search filter
        if (!empty($search)) {
            $qb->andWhere('u.fullName LIKE :search OR u.email LIKE :search')
               ->setParameter('search', '%'.$search.'%');
        }

        // Apply status filter
        if (!empty($status)) {
            $qb->andWhere('u.status = :status')
               ->setParameter('status', $status);
        }

        // Apply role filter
        if (!empty($role)) {
            $qb->andWhere('r.name = :role')
               ->setParameter('role', $role);
        }

        // Apply sorting
        $validSortFields = ['fullName', 'email', 'createdAt', 'status', 'r.name'];
        if (in_array($sortBy, $validSortFields, true)) {
            $qb->orderBy('u.'.$sortBy, 'asc' === $sortOrder ? 'ASC' : 'DESC');
        } else {
            $qb->orderBy('u.createdAt', 'DESC');
        }

        $users = $qb->getQuery()->getResult();

        $userData = [];
        foreach ($users as $user) {
            $userData[] = [
                'id' => $user->getId(),
                'name' => $user->getFullName() ?? 'Unknown',
                'email' => $user->getEmail(),
                'status' => $user->getStatus() ?? 'active',
                'role' => $this->getUserRole($user),
                'joinedAt' => $user->getCreatedAt() ? $user->getCreatedAt()->format('M d, Y') : 'Unknown',
                'lastLogin' => 'Never', // User entity doesn't have lastLogin field
            ];
        }

        // Handle AJAX request
        if ($isAjax) {
            return new JsonResponse([
                'success' => true,
                'users' => $userData,
            ]);
        }

        // Get filter options for dropdowns
        $allUsers = $userRepository->findAll();
        $availableRoles = [];
        $availableStatuses = [];

        foreach ($allUsers as $user) {
            $userRole = $this->getUserRole($user);
            if (!in_array($userRole, $availableRoles, true)) {
                $availableRoles[] = $userRole;
            }

            $userStatus = $user->getStatus() ?? 'active';
            if (!in_array($userStatus, $availableStatuses, true)) {
                $availableStatuses[] = $userStatus;
            }
        }

        sort($availableRoles);
        sort($availableStatuses);

        return $this->render('admin/student-list.html.twig', [
            'users' => $userData,
            'search' => $search,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
            'status' => $status,
            'role' => $role,
            'availableRoles' => $availableRoles,
            'availableStatuses' => $availableStatuses,
        ]);
    }

    private function getUserRole($user): string
    {
        // Get user role from the role relationship
        $role = $user->getRole();

        return $role ? $role->getName() : 'User';
    }

    #[Route('/admin/user/{id}/view', name: 'admin_user_view', methods: ['GET'])]
    public function viewUser(int $id, EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(\App\Entity\User::class)->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        return $this->render('admin/user-view.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/admin/user/{id}/edit', name: 'admin_user_edit', methods: ['GET', 'POST'])]
    public function editUser(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(\App\Entity\User::class)->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        if ($request->isMethod('POST')) {
            // Handle form submission
            $user->setFullName($request->request->get('fullName'));
            $user->setEmail($request->request->get('email'));
            $user->setStatus($request->request->get('status'));

            $entityManager->flush();

            return $this->redirectToRoute('admin_student_list');
        }

        return $this->render('admin/user-edit.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/admin/user/{id}/suspend', name: 'admin_user_suspend', methods: ['POST'])]
    public function suspendUser(int $id, EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(\App\Entity\User::class)->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $user->setStatus('suspended');
        $entityManager->flush();

        return $this->redirectToRoute('admin_student_list');
    }

    #[Route('/admin/user/{id}/activate', name: 'admin_user_activate', methods: ['POST'])]
    public function activateUser(int $id, EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(\App\Entity\User::class)->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $user->setStatus('active');
        $entityManager->flush();

        return $this->redirectToRoute('admin_student_list');
    }

    #[Route('/admin/user/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function deleteUser(int $id, EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(\App\Entity\User::class)->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        try {
            // Check for related data before deletion
            $relatedData = [];

            // Check for courses
            $courseCount = count($user->getCourses());
            if ($courseCount > 0) {
                $relatedData['courses'] = $courseCount;
            }

            // Check for quiz results
            $quizResultCount = count($user->getQuizResults());
            if ($quizResultCount > 0) {
                $relatedData['quiz_results'] = $quizResultCount;
            }

            // Check for products
            $productCount = count($user->getProducts());
            if ($productCount > 0) {
                $relatedData['products'] = $productCount;
            }

            // Check for jobs
            $jobCount = count($user->getJobs());
            if ($jobCount > 0) {
                $relatedData['jobs'] = $jobCount;
            }

            // Check for orders
            $orderCount = count($user->getOrders());
            if ($orderCount > 0) {
                $relatedData['orders'] = $orderCount;
            }

            // Check for applications
            $applicationCount = count($user->getApplications());
            if ($applicationCount > 0) {
                $relatedData['applications'] = $applicationCount;
            }

            // Check for favorites
            $favoriteCount = count($user->getFavorites());
            if ($favoriteCount > 0) {
                $relatedData['favorites'] = $favoriteCount;
            }

            // If there's related data, handle it properly
            if (!empty($relatedData)) {
                // Option 1: Remove related data first (uncomment if you want this behavior)
                /*
                foreach ($user->getCourses() as $course) {
                    $entityManager->remove($course);
                }
                foreach ($user->getQuizResults() as $quizResult) {
                    $entityManager->remove($quizResult);
                }
                foreach ($user->getProducts() as $product) {
                    $entityManager->remove($product);
                }
                foreach ($user->getJobs() as $job) {
                    $entityManager->remove($job);
                }
                foreach ($user->getOrders() as $order) {
                    $entityManager->remove($order);
                }
                foreach ($user->getApplications() as $application) {
                    $entityManager->remove($application);
                }
                foreach ($user->getFavorites() as $favorite) {
                    $entityManager->remove($favorite);
                }
                */

                // Option 2: Show error message (current behavior)
                $errorMessage = 'Cannot delete user "'.$user->getFullName().'" because they have related data: ';
                $errorDetails = [];

                foreach ($relatedData as $entityType => $count) {
                    $errorDetails[] = $count.' '.str_replace('_', ' ', $entityType);
                }

                $errorMessage .= implode(', ', $errorDetails).'. Please remove or reassign this data first.';

                // Add flash message and redirect back
                $this->addFlash('error', $errorMessage);

                return $this->redirectToRoute('admin_user_view', ['id' => $id]);
            }

            // If no related data, proceed with deletion
            $entityManager->remove($user);
            $entityManager->flush();

            $this->addFlash('success', 'User "'.$user->getFullName().'" has been successfully deleted.');
        } catch (\Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException $e) {
            $this->addFlash('error', 'Cannot delete this user because they have related data in the system. Please remove or reassign the related data first.');

            return $this->redirectToRoute('admin_user_view', ['id' => $id]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'An error occurred while trying to delete the user: '.$e->getMessage());

            return $this->redirectToRoute('admin_user_view', ['id' => $id]);
        }

        return $this->redirectToRoute('admin_student_list');
    }

    #[Route('/student-course-list', name: 'app_student_course_list')]
    public function studentCourseList(): Response
    {
        return $this->render('student/course-list.html.twig');
    }

    #[Route('/coming-soon', name: 'app_coming_soon')]
    public function comingSoon(): Response
    {
        return $this->render('utility/coming-soon.html.twig');
    }

    #[Route('/home-variant-5', name: 'app_home_variant_5')]
    public function homeVariant5(): Response
    {
        return $this->render('home/index-5.html.twig');
    }

    #[Route('/faq', name: 'app_faq')]
    public function faq(): Response
    {
        return $this->render('utility/faq.html.twig');
    }

    #[Route('/blog-detail', name: 'app_blog_detail')]
    public function blogDetail(): Response
    {
        return $this->render('blog/detail.html.twig');
    }

    #[Route('/cart', name: 'app_cart')]
    public function cart(): Response
    {
        return $this->render('shop/cart.html.twig');
    }

    #[Route('/checkout', name: 'app_checkout')]
    public function checkout(): Response
    {
        return $this->render('shop/checkout.html.twig');
    }

    #[Route('/course-detail-adv', name: 'app_course_detail_adv')]
    public function courseDetailAdv(): Response
    {
        return $this->render('course/detail-adv.html.twig');
    }

    #[Route('/course-detail-min', name: 'app_course_detail_min')]
    public function courseDetailMin(): Response
    {
        return $this->render('course/detail-min.html.twig');
    }

    #[Route('/course-detail-module', name: 'app_course_detail_module')]
    public function courseDetailModule(): Response
    {
        return $this->render('course/detail-module.html.twig');
    }

    #[Route('/blog-masonry', name: 'app_blog_masonry')]
    public function blogMasonry(): Response
    {
        return $this->render('blog/masonry.html.twig');
    }

    #[Route('/notification-example', name: 'app_notification_example')]
    public function notificationExample(): Response
    {
        return $this->render('examples/notification-example.html.twig');
    }

    #[Route('/admin-quizzes', name: 'app_admin_quizzes')]
    public function adminQuizzes(Request $request): Response
    {
        // Get search and filter parameters
        $search = $request->query->get('search', '');
        $sortBy = $request->query->get('sort', 'createdAt');
        $sortOrder = $request->query->get('order', 'desc');
        $status = $request->query->get('status', '');
        $isAjax = $request->query->get('ajax', false);

        // Build query
        $qb = $this->quizRepository->createQueryBuilder('q');

        // Join course to get course title
        $qb->leftJoin('q.course', 'c')
           ->addSelect('c');

        // Apply search filter
        if (!empty($search)) {
            $qb->andWhere('q.title LIKE :search')
               ->setParameter('search', '%'.$search.'%');
        }

        // Apply status filter (all quizzes are currently "active")
        if (!empty($status) && 'active' === $status) {
            // All quizzes are active by default, so no filtering needed
        } elseif (!empty($status) && 'inactive' === $status) {
            // No inactive quizzes currently
            $qb->andWhere('1 = 0'); // Return no results
        }

        // Apply sorting
        $validSortFields = ['title', 'createdAt'];
        if (in_array($sortBy, $validSortFields, true)) {
            $qb->orderBy('q.'.$sortBy, 'asc' === $sortOrder ? 'ASC' : 'DESC');
        } else {
            $qb->orderBy('q.createdAt', 'DESC');
        }

        $quizzes = $qb->getQuery()->getResult();

        $quizData = [];
        foreach ($quizzes as $quiz) {
            $questions = $this->questionRepository->findBy(['quiz' => $quiz]);
            $quizData[] = [
                'id' => $quiz->getId(),
                'title' => $quiz->getTitle(),
                'course' => $quiz->getCourse(),
                'questions' => $questions,
                'createdAt' => $quiz->getCreatedAt(),
            ];
        }

        // Handle AJAX request
        if ($isAjax) {
            return new JsonResponse([
                'success' => true,
                'quizzes' => $quizData,
            ]);
        }

        // Get all questions for statistics
        $allQuestions = [];
        foreach ($quizzes as $quiz) {
            $questions = $this->questionRepository->findBy(['quiz' => $quiz]);
            $allQuestions[] = [
                'quiz_id' => $quiz->getId(),
                'quiz_title' => $quiz->getTitle(),
                'questions' => $questions,
            ];
        }

        return $this->render('admin/simple.html.twig', [
            'quizzes' => $quizzes,
            'allQuestions' => $allQuestions,
            'search' => $search,
            'status' => $status,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
        ]);
    }

    #[Route('/admin/quizzes/export', name: 'app_admin_quizzes_export')]
    public function adminQuizzesExport(): Response
    {
        try {
            $quizzes = $this->quizRepository->findAll();

            // Simple HTML content
            $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Quiz Export Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background-color: #f2f2f2; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Quiz Export Report</h1>
    <p>Generated on: '.date('Y-m-d H:i:s').'</p>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Questions Count</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>';

            foreach ($quizzes as $quiz) {
                $questionCount = count($this->questionRepository->findBy(['quiz' => $quiz]));
                $html .= '
            <tr>
                <td>'.$quiz->getId().'</td>
                <td>'.htmlspecialchars($quiz->getTitle()).'</td>
                <td>'.$questionCount.'</td>
                <td>'.$quiz->getCreatedAt()->format('Y-m-d H:i:s').'</td>
            </tr>';
            }

            $html .= '
        </tbody>
    </table>
</body>
</html>';

            return new Response($html, 200, [
                'Content-Type' => 'text/html',
                'Content-Disposition' => 'attachment; filename="quizzes_export_'.date('Y-m-d_H-i-s').'.html"',
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error exporting quizzes: '.$e->getMessage(),
            ], 500);
        }
    }

    #[Route('/admin/quiz/add', name: 'app_admin_quiz_add')]
    public function adminQuizAdd(): Response
    {
        return $this->render('admin/quiz-add.html.twig');
    }

    #[Route('/admin/quiz/create', name: 'app_admin_quiz_create', methods: ['POST'])]
    public function adminQuizCreate(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            // Create new quiz
            $quiz = new Quiz();
            $quiz->setTitle($data['title']);
            $quiz->setCreatedAt(new \DateTimeImmutable());

            // Validate the quiz
            $errors = $this->validator->validate($quiz);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }

                return new JsonResponse([
                    'success' => false,
                    'message' => implode(', ', $errorMessages),
                ], 400);
            }

            // Save the quiz to database
            $this->entityManager->persist($quiz);
            $this->entityManager->flush();

            // Add questions if provided
            if (isset($data['questions']) && is_array($data['questions'])) {
                foreach ($data['questions'] as $questionData) {
                    $question = new Question();
                    $question->setQuiz($quiz);
                    $question->setQuestion($questionData['question']);
                    $question->setOptionA($questionData['optionA'] ?? '');
                    $question->setOptionB($questionData['optionB'] ?? '');
                    $question->setOptionC($questionData['optionC'] ?? '');
                    $question->setOptionD($questionData['optionD'] ?? '');
                    $question->setCorrectOption($questionData['correctOption'] ?? 'A');
                    $question->setCreatedAt(new \DateTimeImmutable());

                    $this->entityManager->persist($question);
                }
                $this->entityManager->flush();
            }

            return new JsonResponse([
                'success' => true,
                'message' => 'Quiz created successfully!',
                'quizId' => $quiz->getId(),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error creating quiz: '.$e->getMessage(),
            ], 500);
        }
    }

    #[Route('/admin/quizzes/search', name: 'app_admin_quizzes_search', methods: ['GET'])]
    public function adminQuizzesSearch(Request $request): JsonResponse
    {
        try {
            $searchTerm = $request->query->get('q', '');

            if (empty($searchTerm)) {
                // Return all quizzes if search term is empty
                $quizzes = $this->quizRepository->findAll();
            } else {
                // Search quizzes by title
                $quizzes = $this->quizRepository->findByTitleContaining($searchTerm);
            }

            // Get all questions for each quiz
            $allQuestions = [];
            foreach ($quizzes as $quiz) {
                $questions = $this->questionRepository->findBy(['quiz' => $quiz]);
                $allQuestions[] = [
                    'quiz_id' => $quiz->getId(),
                    'quiz_title' => $quiz->getTitle(),
                    'questions' => $questions,
                ];
            }

            // Format quizzes for JSON response
            $formattedQuizzes = [];
            foreach ($quizzes as $quiz) {
                $formattedQuizzes[] = [
                    'id' => $quiz->getId(),
                    'title' => $quiz->getTitle(),
                    'createdAt' => $quiz->getCreatedAt()->format('Y-m-d H:i:s'),
                    'updatedAt' => $quiz->getUpdatedAt() ? $quiz->getUpdatedAt()->format('Y-m-d H:i:s') : null,
                ];
            }

            return new JsonResponse([
                'success' => true,
                'quizzes' => $formattedQuizzes,
                'allQuestions' => $allQuestions,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error searching quizzes: '.$e->getMessage(),
            ], 500);
        }
    }

    #[Route('/admin/quiz/{id}/delete', name: 'app_admin_quiz_delete', methods: ['DELETE'])]
    public function adminQuizDelete(int $id): JsonResponse
    {
        try {
            $quiz = $this->quizRepository->find($id);
            if (!$quiz) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Quiz not found',
                ], 404);
            }

            // Delete all questions for this quiz
            $questions = $this->questionRepository->findBy(['quiz' => $quiz]);
            foreach ($questions as $question) {
                $this->entityManager->remove($question);
            }

            // Delete quiz
            $this->entityManager->remove($quiz);
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Quiz deleted successfully!',
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error deleting quiz: '.$e->getMessage(),
            ], 500);
        }
    }

    #[Route('/admin/quiz/{id}/view', name: 'app_admin_quiz_view')]
    public function adminQuizView(int $id): Response
    {
        $quiz = $this->quizRepository->find($id);
        if (!$quiz) {
            throw $this->createNotFoundException('Quiz not found');
        }

        $questions = $this->questionRepository->findBy(['quiz' => $quiz]);

        return $this->render('admin/quiz-detail.html.twig', [
            'quiz' => $quiz,
            'questions' => $questions,
        ]);
    }

    #[Route('/admin/quiz/{id}/edit', name: 'app_admin_quiz_edit')]
    public function adminQuizEdit(int $id): Response
    {
        $quiz = $this->quizRepository->find($id);
        if (!$quiz) {
            throw $this->createNotFoundException('Quiz not found');
        }

        $questions = $this->questionRepository->findBy(['quiz' => $quiz]);

        return $this->render('admin/quiz-edit.html.twig', [
            'quiz' => $quiz,
            'questions' => $questions,
        ]);
    }

    #[Route('/admin/quiz/{id}/update', name: 'app_admin_quiz_update', methods: ['PUT'])]
    public function adminQuizUpdate(int $id, Request $request): JsonResponse
    {
        try {
            $quiz = $this->quizRepository->find($id);
            if (!$quiz) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Quiz not found',
                ], 404);
            }

            $data = json_decode($request->getContent(), true);

            // Update quiz fields
            $quiz->setTitle($data['title']);
            $quiz->setUpdatedAt(new \DateTimeImmutable());

            // Validate the quiz
            $errors = $this->validator->validate($quiz);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }

                return new JsonResponse([
                    'success' => false,
                    'message' => implode(', ', $errorMessages),
                ], 400);
            }

            // Save the quiz
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Quiz updated successfully!',
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error updating quiz: '.$e->getMessage(),
            ], 500);
        }
    }

    #[Route('/admin/quiz/{id}/duplicate', name: 'app_admin_quiz_duplicate', methods: ['POST'])]
    public function adminQuizDuplicate(int $id): JsonResponse
    {
        try {
            $quiz = $this->quizRepository->find($id);
            if (!$quiz) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Quiz not found',
                ], 404);
            }

            // Create new quiz as a duplicate
            $newQuiz = new Quiz();
            $newQuiz->setTitle($quiz->getTitle().' (Copy)');
            $newQuiz->setCourse($quiz->getCourse());
            $newQuiz->setCreatedAt(new \DateTimeImmutable());
            $newQuiz->setUpdatedAt(new \DateTimeImmutable());

            // Duplicate questions
            foreach ($quiz->getQuestions() as $originalQuestion) {
                $newQuestion = new Question();
                $newQuestion->setQuiz($newQuiz);
                $newQuestion->setQuestion($originalQuestion->getQuestion());
                $newQuestion->setOptionA($originalQuestion->getOptionA());
                $newQuestion->setOptionB($originalQuestion->getOptionB());
                $newQuestion->setOptionC($originalQuestion->getOptionC());
                $newQuestion->setOptionD($originalQuestion->getOptionD());
                $newQuestion->setCorrectOption($originalQuestion->getCorrectOption());
                $newQuestion->setCreatedAt(new \DateTimeImmutable());

                $this->entityManager->persist($newQuestion);
            }

            // Save the new quiz and questions
            $this->entityManager->persist($newQuiz);
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Quiz duplicated successfully!',
                'quiz_id' => $newQuiz->getId(),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error duplicating quiz: '.$e->getMessage(),
            ], 500);
        }
    }

    private function getLevelBadgeClass(?string $level): string
    {
        return match (strtolower($level)) {
            'beginner' => 'text-bg-primary',
            'intermediate' => 'text-bg-purple',
            'advanced' => 'text-bg-danger',
            'all levels', 'all level' => 'text-bg-orange',
            default => 'text-bg-secondary',
        };
    }

    private function getStatusBadgeClass(?string $status): string
    {
        return match (strtolower($status)) {
            'live', 'active', 'published' => 'bg-success bg-opacity-15 text-success',
            'pending', 'review' => 'bg-warning bg-opacity-15 text-warning',
            'unaccept', 'rejected', 'inactive' => 'bg-danger bg-opacity-15 text-danger',
            'draft' => 'bg-secondary bg-opacity-15 text-secondary',
            default => 'bg-secondary bg-opacity-15 text-secondary',
        };
    }
}
