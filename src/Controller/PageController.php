<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Quiz;
use App\Entity\Question;
use App\Entity\QuizResult;
use App\Entity\User;
use App\Repository\CourseRepository;
use App\Repository\QuizRepository;
use App\Repository\QuestionRepository;
use App\Repository\QuizResultRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidator;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class PageController extends AbstractController
{
    private CourseRepository $courseRepository;
    private QuizRepository $quizRepository;
    private QuestionRepository $questionRepository;
    private QuizResultRepository $quizResultRepository;
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;
    private SymfonyValidator $validator;

    public function __construct(
        CourseRepository $courseRepository,
        QuizRepository $quizRepository,
        QuestionRepository $questionRepository,
        QuizResultRepository $quizResultRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        SymfonyValidator $validator
    ) {
        $this->courseRepository = $courseRepository;
        $this->quizRepository = $quizRepository;
        $this->questionRepository = $questionRepository;
        $this->quizResultRepository = $quizResultRepository;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }

    #[Route('/quiz/{id}/data', name: 'app_quiz_data', methods: ['GET'])]
    public function getQuizData(int $id): JsonResponse
    {
        try {
            // Get the quiz
            $quiz = $this->quizRepository->find($id);
            if (!$quiz) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Quiz not found'
                ], Response::HTTP_NOT_FOUND);
            }
            
            // Get all courses for dropdown
            $courses = $this->courseRepository->findAll();
            $coursesData = [];
            foreach ($courses as $course) {
                $coursesData[] = [
                    'id' => $course->getId(),
                    'title' => $course->getTitle()
                ];
            }
            
            return new JsonResponse([
                'success' => true,
                'quiz' => [
                    'id' => $quiz->getId(),
                    'title' => $quiz->getTitle(),
                    'course' => $quiz->getCourse() ? [
                        'id' => $quiz->getCourse()->getId(),
                        'title' => $quiz->getCourse()->getTitle()
                    ] : null,
                    'timeLimit' => null, // Quiz entity doesn't have this field
                    'passingScore' => 70 // Default passing score
                ],
                'courses' => $coursesData
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error loading quiz data: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/quiz/{id}/edit', name: 'app_quiz_edit', methods: ['PUT'])]
    public function editQuiz(int $id, Request $request): JsonResponse
    {
        try {
            // Get the quiz
            $quiz = $this->quizRepository->find($id);
            if (!$quiz) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Quiz not found'
                ], Response::HTTP_NOT_FOUND);
            }
            
            $data = json_decode($request->getContent(), true);
            
            // Update quiz fields
            $quiz->setTitle($data['title']);
            $quiz->setUpdatedAt(new \DateTimeImmutable());
            
            // Handle course assignment
            if (!empty($data['courseId'])) {
                $course = $this->courseRepository->find($data['courseId']);
                if ($course) {
                    $quiz->setCourse($course);
                }
            } else {
                $quiz->setCourse(null);
            }
            
            // Note: timeLimit and passingScore are not in the Quiz entity
            // These fields are ignored for now
            
            // Validate the quiz
            $errors = $this->validator->validate($quiz);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return new JsonResponse([
                    'success' => false,
                    'message' => implode(', ', $errorMessages)
                ], Response::HTTP_BAD_REQUEST);
            }
            
            // Save the quiz
            $this->entityManager->flush();
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Quiz updated successfully!'
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error updating quiz: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/quiz/question/{id}/data', name: 'app_quiz_question_data', methods: ['GET'])]
    public function getQuestionData(int $id): JsonResponse
    {
        try {
            // Find the question
            $question = $this->entityManager->getRepository(Question::class)->find($id);
            if (!$question) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Question not found'
                ], Response::HTTP_NOT_FOUND);
            }
            
            return new JsonResponse([
                'success' => true,
                'question' => [
                    'id' => $question->getId(),
                    'question' => $question->getQuestion(),
                    'optionA' => $question->getOptionA(),
                    'optionB' => $question->getOptionB(),
                    'optionC' => $question->getOptionC(),
                    'optionD' => $question->getOptionD(),
                    'correctOption' => $question->getCorrectOption(),
                    'points' => 1, // Question entity doesn't have points field, default to 1
                    'quiz' => [
                        'id' => $question->getQuiz()->getId(),
                        'title' => $question->getQuiz()->getTitle()
                    ]
                ],
                'quizTitle' => $question->getQuiz()->getTitle()
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error loading question data: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/quiz/question/{id}/edit', name: 'app_quiz_question_edit', methods: ['PUT'])]
    public function editQuestion(int $id, Request $request): JsonResponse
    {
        try {
            // Find the question
            $question = $this->entityManager->getRepository(Question::class)->find($id);
            if (!$question) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Question not found'
                ], Response::HTTP_NOT_FOUND);
            }
            
            $data = json_decode($request->getContent(), true);
            error_log('Question update data: ' . print_r($data, true));
            
            // Update question fields
            $question->setQuestion($data['question']);
            $question->setUpdatedAt(new \DateTimeImmutable());
            
            // Update multiple choice options
            $question->setOptionA($data['optionA'] ?? '');
            $question->setOptionB($data['optionB'] ?? '');
            
            // Handle optional options C and D
            $optionC = $data['optionC'] ?? '';
            $optionD = $data['optionD'] ?? '';
            
            if (empty($optionC)) {
                $question->setOptionC('Option C');
            } else {
                $question->setOptionC($optionC);
            }
            
            if (empty($optionD)) {
                $question->setOptionD('Option D');
            } else {
                $question->setOptionD($optionD);
            }
            
            $question->setCorrectOption($data['correctOption'] ?? 'A');
            
            // Note: Question entity doesn't have points field, so we skip this
            
            // Validate the question
            $errors = $this->validator->validate($question);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                    error_log('Validation error: ' . $error->getMessage());
                }
                return new JsonResponse([
                    'success' => false,
                    'message' => implode(', ', $errorMessages)
                ], Response::HTTP_BAD_REQUEST);
            }
            
            // Save the question
            $this->entityManager->flush();
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Question updated successfully!'
            ]);
            
        } catch (\Exception $e) {
            error_log('Error updating question: ' . $e->getMessage());
            return new JsonResponse([
                'success' => false,
                'message' => 'Error updating question: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/quiz/{id}/delete', name: 'app_quiz_delete', methods: ['DELETE'])]
    public function deleteQuiz(int $id): JsonResponse
    {
        try {
            // Get the quiz
            $quiz = $this->quizRepository->find($id);
            if (!$quiz) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Quiz not found'
                ], Response::HTTP_NOT_FOUND);
            }
            
            // First, delete all quiz results for this quiz
            $quizResults = $this->quizResultRepository->findBy(['quiz' => $quiz]);
            foreach ($quizResults as $result) {
                $this->entityManager->remove($result);
            }
            
            // Then delete all questions for this quiz
            $questions = $this->entityManager->getRepository(Question::class)->findBy(['quiz' => $quiz]);
            foreach ($questions as $question) {
                $this->entityManager->remove($question);
            }
            
            // Finally delete the quiz
            $this->entityManager->remove($quiz);
            $this->entityManager->flush();
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Quiz deleted successfully!'
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error deleting quiz: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/quiz/question/{id}/delete', name: 'app_quiz_question_delete', methods: ['DELETE'])]
    public function deleteQuestion(int $id): JsonResponse
    {
        try {
            // Find the question
            $question = $this->entityManager->getRepository(Question::class)->find($id);
            if (!$question) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Question not found'
                ], Response::HTTP_NOT_FOUND);
            }
            
            // Delete the question
            $this->entityManager->remove($question);
            $this->entityManager->flush();
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Question deleted successfully!'
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error deleting question: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/question/add', name: 'app_question_add', methods: ['POST'])]
    public function addQuestion(Request $request): JsonResponse
    {
        error_log("=== PageController addQuestion called ===");
        
        try {
            $data = json_decode($request->getContent(), true);
            error_log("Received question data: " . print_r($data, true));

            // Check if this is a quiz-specific question addition
            if (isset($data['quizId'])) {
                return $this->handleQuizQuestion($data);
            }
            
            // Original simple quiz creation logic can go here if needed
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid request format'
            ]);

        } catch (\Exception $e) {
            error_log('Error in addQuestion: ' . $e->getMessage());
            return new JsonResponse([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function handleQuizQuestion(array $data): JsonResponse
    {
        try {
            // Validate required fields
            if (!isset($data['quizId']) || !isset($data['question'])) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Quiz ID and question text are required'
                ], Response::HTTP_BAD_REQUEST);
            }
            
            // Get the quiz
            $quiz = $this->quizRepository->find($data['quizId']);
            if (!$quiz) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Quiz not found'
                ], Response::HTTP_NOT_FOUND);
            }
            
            // Create new question
            $question = new Question();
            $question->setQuiz($quiz);
            $question->setQuestion($data['question']);
            $question->setCreatedAt(new \DateTimeImmutable());
            
            // Handle different question types by adapting to the entity structure
            if (isset($data['type']) && $data['type'] === 'multiple') {
                // Multiple choice question
                $question->setOptionA($data['optionA'] ?? '');
                $question->setOptionB($data['optionB'] ?? '');
                
                // Handle optional options C and D
                $optionC = $data['optionC'] ?? '';
                $optionD = $data['optionD'] ?? '';
                
                // If options C or D are empty, set default values to satisfy validation
                if (empty($optionC)) {
                    $question->setOptionC('Option C');
                } else {
                    $question->setOptionC($optionC);
                }
                
                if (empty($optionD)) {
                    $question->setOptionD('Option D');
                } else {
                    $question->setOptionD($optionD);
                }
                
                $question->setCorrectOption($data['correctOption'] ?? 'A');
            } elseif (isset($data['type']) && $data['type'] === 'true_false') {
                // True/False question - adapt to multiple choice format
                $question->setOptionA('True');
                $question->setOptionB('False');
                $question->setOptionC('False Option');
                $question->setOptionD('False Option');
                $question->setCorrectOption($data['correctAnswer'] === 'true' ? 'A' : 'B');
            } else {
                // Short answer or default - adapt to multiple choice format
                $correctAnswer = $data['correctAnswer'] ?? 'Answer';
                $question->setOptionA($correctAnswer);
                $question->setOptionB('Wrong Answer 1');
                $question->setOptionC('Wrong Answer 2');
                $question->setOptionD('Wrong Answer 3');
                $question->setCorrectOption('A');
            }
            
            // Validate the question
            $errors = $this->validator->validate($question);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return new JsonResponse([
                    'success' => false,
                    'message' => implode(', ', $errorMessages)
                ], Response::HTTP_BAD_REQUEST);
            }
            
            // Save the question
            $this->entityManager->persist($question);
            $this->entityManager->flush();
            
            error_log("Question saved successfully with ID: " . $question->getId());
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Question added successfully!',
                'questionId' => $question->getId(),
                'quizId' => $quiz->getId()
            ]);
            
        } catch (\Exception $e) {
            error_log('Error adding question: ' . $e->getMessage());
            return new JsonResponse([
                'success' => false,
                'message' => 'Error adding question: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/about', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('page/about.html.twig');
    }

    #[Route('/contact', name: 'app_contact')]
    public function contact(): Response
    {
        return $this->render('page/contact.html.twig');
    }

    #[Route('/courses', name: 'app_courses')]
    public function courses(): Response
    {
        return $this->render('course/index.html.twig');
    }

    #[Route('/login', name: 'app_login')]
    public function login(): Response
    {
        return $this->render('auth/login.html.twig');
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

    #[Route('/course-grid', name: 'app_course_grid')]
    public function courseGrid(): Response
    {
        return $this->render('course/grid.html.twig');
    }

    #[Route('/course-detail', name: 'app_course_detail')]
    public function courseDetail(): Response
    {
        return $this->render('course/detail.html.twig');
    }

    #[Route('/sign-in', name: 'app_sign_in')]
    public function signIn(): Response
    {
        return $this->render('auth/sign-in.html.twig');
    }

    #[Route('/sign-up', name: 'app_sign_up')]
    public function signUp(): Response
    {
        return $this->render('auth/sign-up.html.twig');
    }

    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(): Response
    {
        return $this->render('auth/forgot-password.html.twig');
    }

    #[Route('/instructor-dashboard', name: 'app_instructor_dashboard')]
    public function instructorDashboard(): Response
    {
        return $this->render('instructor/dashboard.html.twig');
    }

    #[Route('/student-dashboard', name: 'app_student_dashboard')]
    public function studentDashboard(): Response
    {
        return $this->render('student/dashboard.html.twig');
    }

    #[Route('/admin-dashboard', name: 'app_admin_dashboard')]
    public function adminDashboard(): Response
    {
        return $this->render('admin/dashboard.html.twig');
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
    public function courseList(): Response
    {
        return $this->render('course/list.html.twig');
    }

    #[Route('/instructor-list', name: 'app_instructor_list')]
    public function instructorList(): Response
    {
        return $this->render('instructor/list.html.twig');
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

    #[Route('/instructor-quiz-ultra-minimal', name: 'app_instructor_quiz_ultra_minimal')]
    public function instructorQuizUltraMinimal(): Response
    {
        try {
            $instructor = [
                'name' => 'Lori Stevens',
                'email' => 'lori.stevens@unilearn.com'
            ];
            
            return $this->render('instructor/quiz-ultra-minimal.html.twig', [
                'instructor' => $instructor
            ]);
        } catch (\Exception $e) {
            return new Response('Error: ' . $e->getMessage(), 500);
        }
    }

    #[Route('/test-instructor', name: 'app_test_instructor')]
    public function testInstructor(): Response
    {
        return new Response('Instructor test route is working!');
    }

    #[Route('/instructor-quiz-minimal', name: 'app_instructor_quiz_minimal')]
    public function instructorQuizMinimal(): Response
    {
        try {
            // Version minimale pour tester
            $instructor = [
                'name' => 'Lori Stevens',
                'email' => 'lori.stevens@unilearn.com'
            ];
            
            return $this->render('instructor/quiz-test.html.twig', [
                'instructor' => $instructor
            ]);
        } catch (\Exception $e) {
            return new Response('Error: ' . $e->getMessage(), 500);
        }
    }

    #[Route('/instructor-quiz', name: 'app_instructor_quiz')]
    public function instructorQuiz(): Response
    {
        try {
            // Get all quizzes from database
            $quizzes = $this->quizRepository->findAll();
            
            // Create sample users if they don't exist
            $users = $this->userRepository->findAll();
            if (empty($users)) {
                $sampleUsers = [
                    ['name' => 'John Doe', 'email' => 'john@example.com'],
                    ['name' => 'Jane Smith', 'email' => 'jane@example.com'],
                    ['name' => 'Mike Johnson', 'email' => 'mike@example.com'],
                    ['name' => 'Sarah Williams', 'email' => 'sarah@example.com'],
                    ['name' => 'Tom Brown', 'email' => 'tom@example.com']
                ];
                
                foreach ($sampleUsers as $userData) {
                    $user = new User();
                    $user->setName($userData['name']);
                    $user->setEmail($userData['email']);
                    $user->setCreatedAt(new \DateTimeImmutable());
                    $this->entityManager->persist($user);
                    $users[] = $user;
                }
                $this->entityManager->flush();
            }
            
            // Create sample quiz results if they don't exist
            $quizResults = $this->quizResultRepository->findAll();
            if (empty($quizResults) && !empty($quizzes) && !empty($users)) {
                $sampleResults = [
                    ['score' => 85, 'maxScore' => 100, 'daysAgo' => 2],
                    ['score' => 92, 'maxScore' => 100, 'daysAgo' => 3],
                    ['score' => 78, 'maxScore' => 100, 'daysAgo' => 5],
                    ['score' => 88, 'maxScore' => 100, 'daysAgo' => 7],
                    ['score' => 95, 'maxScore' => 100, 'daysAgo' => 10],
                    ['score' => 73, 'maxScore' => 100, 'daysAgo' => 12],
                    ['score' => 90, 'maxScore' => 100, 'daysAgo' => 15],
                    ['score' => 82, 'maxScore' => 100, 'daysAgo' => 18]
                ];
                
                foreach ($sampleResults as $index => $resultData) {
                    $quizResult = new QuizResult();
                    $quizResult->setUser($users[$index % count($users)]);
                    $quizResult->setQuiz($quizzes[$index % count($quizzes)]);
                    $quizResult->setScore($resultData['score']);
                    $quizResult->setMaxScore($resultData['maxScore']);
                    $quizResult->setTakenAt((new \DateTimeImmutable())->modify("-{$resultData['daysAgo']} days"));
                    $quizResult->setCreatedAt(new \DateTimeImmutable());
                    $this->entityManager->persist($quizResult);
                    $quizResults[] = $quizResult;
                }
                $this->entityManager->flush();
            }
            
            // Get all quiz results
            $quizResults = $this->quizResultRepository->findAll();
            
            // Mock instructor data for the template
            $instructor = [
                'name' => 'Lori Stevens',
                'email' => 'lori.stevens@unilearn.com',
                'avatar' => null,
                'verified' => true,
                'rating' => '4.8',
                'totalStudents' => 1250,
                'totalCourses' => 12
            ];
            
            return $this->render('instructor/quiz.html.twig', [
                'quizzes' => $quizzes,
                'instructor' => $instructor,
                'studentResults' => $quizResults,
                'course' => null,
                'currentPage' => 1,
                'totalPages' => 1,
                'totalResults' => count($quizResults),
                'resultsPerPage' => 10
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error loading instructor quizzes: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
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
            'questions' => $questions
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
            'questions' => $questions
        ]);
    }

    #[Route('/admin/quiz/{id}/delete', name: 'app_admin_quiz_delete', methods: ['DELETE'])]
    public function adminQuizDelete(int $id): JsonResponse
    {
        error_log("=== DELETE METHOD CALLED with ID: " . $id . " ===");
        return $this->performQuizDelete($id);
    }

    #[Route('/test-delete/{id}', name: 'test_delete', methods: ['GET'])]
    public function testDelete(int $id): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'message' => 'Test delete endpoint works for ID: ' . $id
        ]);
    }

    #[Route('/admin/quiz/{id}/delete', name: 'app_admin_quiz_delete_post', methods: ['POST'])]
    public function adminQuizDeletePost(Request $request, int $id): JsonResponse
    {
        // Check if this is a DELETE request disguised as POST (for compatibility)
        if ($request->request->get('_method') === 'DELETE') {
            return $this->performQuizDelete($id);
        }
        
        return new JsonResponse([
            'success' => false,
            'message' => 'Method not allowed'
        ], Response::HTTP_METHOD_NOT_ALLOWED);
    }

    private function performQuizDelete(int $id): JsonResponse
    {
        error_log("=== performQuizDelete called with ID: " . $id . " ===");
        
        try {
            $quiz = $this->quizRepository->find($id);
            if (!$quiz) {
                error_log("Quiz not found with ID: " . $id);
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Quiz not found'
                ], Response::HTTP_NOT_FOUND);
            }

            error_log("Found quiz: " . $quiz->getTitle());

            // Remove all questions associated with this quiz first
            $questions = $this->questionRepository->findBy(['quiz' => $quiz]);
            error_log("Found " . count($questions) . " questions to delete");
            
            foreach ($questions as $question) {
                $this->entityManager->remove($question);
                error_log("Removed question ID: " . $question->getId());
            }
            
            // Remove the quiz
            $this->entityManager->remove($quiz);
            error_log("Removed quiz ID: " . $quiz->getId());
            
            // Flush all changes
            $this->entityManager->flush();
            error_log("Flushed changes to database");
            
            error_log("Quiz and questions deleted successfully");
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Quiz deleted successfully'
            ]);

        } catch (\Exception $e) {
            error_log("Error deleting quiz: " . $e->getMessage());
            error_log("Error trace: " . $e->getTraceAsString());
            return new JsonResponse([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/admin/quiz/{id}/update', name: 'app_admin_quiz_update', methods: ['PUT'])]
    public function adminQuizUpdate(int $id, Request $request): JsonResponse
    {
        error_log("=== adminQuizUpdate called with ID: " . $id . " ===");
        
        try {
            $quiz = $this->quizRepository->find($id);
            if (!$quiz) {
                error_log("Quiz not found with ID: " . $id);
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Quiz not found'
                ], Response::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true);
            error_log("Received data: " . print_r($data, true));
            
            if (!isset($data['title']) || empty($data['title'])) {
                error_log("Quiz title is missing or empty");
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Quiz title is required'
                ], Response::HTTP_BAD_REQUEST);
            }

            error_log("Updating quiz title from: " . $quiz->getTitle() . " to: " . $data['title']);
            
            // Update quiz
            $quiz->setTitle($data['title']);
            $quiz->setUpdatedAt(new \DateTimeImmutable());
            
            // If course is provided, update it
            if (isset($data['courseId'])) {
                if ($data['courseId']) {
                    $course = $this->courseRepository->find($data['courseId']);
                    if ($course) {
                        $quiz->setCourse($course);
                    }
                } else {
                    $quiz->setCourse(null);
                }
            }
            
            $this->entityManager->flush();
            error_log("Quiz updated successfully in database");

            return new JsonResponse([
                'success' => true,
                'message' => 'Quiz updated successfully',
                'title' => $quiz->getTitle()
            ]);

        } catch (\Exception $e) {
            error_log("Error in adminQuizUpdate: " . $e->getMessage());
            error_log("Error trace: " . $e->getTraceAsString());
            return new JsonResponse([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/admin/question/{id}/get', name: 'app_admin_question_get', methods: ['GET'])]
    public function adminGetQuestion(int $id): JsonResponse
    {
        try {
            $question = $this->questionRepository->find($id);
            if (!$question) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Question not found'
                ], Response::HTTP_NOT_FOUND);
            }

            return new JsonResponse([
                'success' => true,
                'question' => [
                    'id' => $question->getId(),
                    'question' => $question->getQuestion(),
                    'optionA' => $question->getOptionA(),
                    'optionB' => $question->getOptionB(),
                    'optionC' => $question->getOptionC(),
                    'optionD' => $question->getOptionD(),
                    'correctOption' => $question->getCorrectOption()
                ]
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/admin/question/{id}/update', name: 'app_admin_question_update', methods: ['PUT'])]
    public function adminUpdateQuestion(int $id, Request $request): JsonResponse
    {
        try {
            // Debug logging
            error_log('Admin update question called with ID: ' . $id);
            error_log('Request data: ' . $request->getContent());
            
            $question = $this->questionRepository->find($id);
            if (!$question) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Question not found'
                ], Response::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true);
            
            // Validate required fields
            $requiredFields = ['question', 'optionA', 'optionB', 'optionC', 'optionD', 'correctOption'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => ucfirst($field) . ' is required'
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            // Update question
            $question->setQuestion($data['question']);
            $question->setOptionA($data['optionA']);
            $question->setOptionB($data['optionB']);
            $question->setOptionC($data['optionC']);
            $question->setOptionD($data['optionD']);
            $question->setCorrectOption($data['correctOption']);
            
            error_log("Updating question ID: " . $question->getId());
            error_log("New question data: " . print_r($data, true));
            
            $this->entityManager->flush();
            
            error_log("Question updated successfully in database");

            return new JsonResponse([
                'success' => true,
                'message' => 'Question updated successfully'
            ]);

        } catch (\Exception $e) {
            error_log('Error in adminUpdateQuestion: ' . $e->getMessage());
            return new JsonResponse([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/admin/question/{id}/delete', name: 'app_admin_question_delete', methods: ['DELETE'])]
    public function adminDeleteQuestion(int $id): JsonResponse
    {
        try {
            $question = $this->questionRepository->find($id);
            if (!$question) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Question not found'
                ], Response::HTTP_NOT_FOUND);
            }

            $this->entityManager->remove($question);
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Question deleted successfully'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/admin/quizzes/search', name: 'app_admin_quizzes_search', methods: ['GET'])]
    public function adminQuizzesSearch(Request $request): JsonResponse
    {
        try {
            $searchTerm = $request->query->get('q', '');
            
            // Debug logging
            error_log('Search term received: "' . $searchTerm . '"');
            
            if (empty($searchTerm)) {
                // Return all quizzes if search term is empty
                error_log('Empty search term, returning all quizzes');
                $quizzes = $this->quizRepository->findAll();
            } else {
                // Search quizzes by title
                error_log('Searching for quizzes with term: "' . $searchTerm . '"');
                $quizzes = $this->quizRepository->createQueryBuilder('q')
                    ->where('q.title LIKE :searchTerm')
                    ->setParameter('searchTerm', '%' . $searchTerm . '%')
                    ->getQuery()
                    ->getResult();
                
                error_log('Found ' . count($quizzes) . ' quizzes matching');
            }
            
            // Get all questions for each quiz
            $allQuestions = [];
            foreach ($quizzes as $quiz) {
                $questions = $this->questionRepository->findBy(['quiz' => $quiz]);
                $allQuestions[] = [
                    'quiz_id' => $quiz->getId(),
                    'quiz_title' => $quiz->getTitle(),
                    'questions' => $questions
                ];
            }

            $formattedQuizzes = $this->formatQuizzesForResponse($quizzes);
            error_log('Returning ' . count($formattedQuizzes) . ' formatted quizzes');

            return new JsonResponse([
                'success' => true,
                'quizzes' => $formattedQuizzes,
                'allQuestions' => $allQuestions,
                'searchTerm' => $searchTerm
            ]);

        } catch (\Exception $e) {
            error_log('Search error: ' . $e->getMessage());
            return new JsonResponse([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function formatQuizzesForResponse($quizzes): array
    {
        $formattedQuizzes = [];
        foreach ($quizzes as $quiz) {
            $questions = $this->questionRepository->findBy(['quiz' => $quiz]);
            $formattedQuizzes[] = [
                'id' => $quiz->getId(),
                'title' => $quiz->getTitle(),
                'createdAt' => $quiz->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $quiz->getUpdatedAt()->format('Y-m-d H:i:s'),
                'questionCount' => count($questions)
            ];
        }
        return $formattedQuizzes;
    }

    #[Route('/admin/quiz/add', name: 'app_admin_quiz_add')]
    public function adminQuizAdd(): Response
    {
        return $this->render('admin/quiz-add.html.twig');
    }

    #[Route('/test-simple-json', name: 'app_test_simple_json')]
    public function testSimpleJson(): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'message' => 'Simple JSON test works',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    #[Route('/test-quiz-creation', name: 'app_test_quiz_creation', methods: ['POST'])]
    public function testQuizCreation(Request $request): JsonResponse
    {
        try {
            error_log('TEST: Quiz creation request received');
            
            $data = json_decode($request->getContent(), true);
            error_log('TEST: Quiz data: ' . print_r($data, true));
            
            return new JsonResponse([
                'success' => true,
                'message' => 'TEST: Quiz creation endpoint is working',
                'data_received' => $data
            ]);

        } catch (\Exception $e) {
            error_log('TEST: Quiz creation error: ' . $e->getMessage());
            
            return new JsonResponse([
                'success' => false,
                'message' => 'TEST: Error - ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/test-quiz-simple', name: 'app_test_quiz_simple', methods: ['POST'])]
    public function testQuizSimple(Request $request): JsonResponse
    {
        try {
            return new JsonResponse([
                'success' => true,
                'message' => 'TEST: Endpoint simple fonctionne!',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'TEST: Erreur - ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/admin/quiz/create', name: 'app_admin_quiz_create', methods: ['POST'])]
    public function adminQuizCreate(Request $request): JsonResponse
    {
        try {
            error_log('QUIZ CREATE: Method called');
            
            $data = json_decode($request->getContent(), true);
            error_log('QUIZ CREATE: Data received: ' . print_r($data, true));
            
            // Utiliser les données du formulaire ou valeurs par défaut
            $title = isset($data['title']) && !empty($data['title']) ? trim($data['title']) : 'Default Quiz ' . date('H:i:s');
            
            error_log('QUIZ CREATE: Title to use: ' . $title);
            
            // Créer le quiz avec les données du formulaire
            $quiz = new Quiz();
            $quiz->setTitle($title);
            $quiz->setCreatedAt(new \DateTimeImmutable());
            $quiz->setUpdatedAt(new \DateTimeImmutable());
            
            error_log('QUIZ CREATE: Quiz object created');
            
            // Gérer le course si fourni
            if (isset($data['courseId']) && $data['courseId'] && $data['courseId'] !== null) {
                $course = $this->courseRepository->find($data['courseId']);
                if ($course) {
                    $quiz->setCourse($course);
                    error_log('QUIZ CREATE: Course set: ' . $course->getId());
                }
            }
            
            $this->entityManager->persist($quiz);
            error_log('QUIZ CREATE: Quiz persisted');
            
            $this->entityManager->flush();
            error_log('QUIZ CREATE: Quiz flushed successfully - ID: ' . $quiz->getId());
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Quiz created successfully!',
                'quizId' => $quiz->getId(),
                'title' => $quiz->getTitle()
            ]);

        } catch (\Exception $e) {
            error_log('QUIZ CREATE: EXCEPTION - ' . $e->getMessage());
            error_log('QUIZ CREATE: TRACE - ' . $e->getTraceAsString());
            
            return new JsonResponse([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/admin-quizzes', name: 'app_admin_quizzes')]
    public function adminQuizzes(): Response
    {
        // Get all quizzes from database
        $quizzes = $this->quizRepository->findAll();
        
        // Get all questions for each quiz
        $allQuestions = [];
        foreach ($quizzes as $quiz) {
            $questions = $this->questionRepository->findBy(['quiz' => $quiz]);
            $allQuestions[] = [
                'quiz_id' => $quiz->getId(),
                'quiz_title' => $quiz->getTitle(),
                'questions' => $questions
            ];
        }
        
        return $this->render('admin/quizzes.html.twig', [
            'quizzes' => $quizzes,
            'allQuestions' => $allQuestions
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
    <p>Generated on: ' . date('Y-m-d H:i:s') . '</p>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Questions</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody>';
            
            foreach ($quizzes as $quiz) {
                $questions = $this->questionRepository->findBy(['quiz' => $quiz]);
                $questionCount = count($questions);
                
                $html .= '<tr>
                    <td>' . $quiz->getId() . '</td>
                    <td>' . htmlspecialchars($quiz->getTitle()) . '</td>
                    <td>' . $questionCount . '</td>
                    <td>' . $quiz->getCreatedAt()->format('Y-m-d') . '</td>
                </tr>';
            }
            
            $html .= '</tbody>
    </table>
</body>
</html>';
            
            $response = new Response($html);
            $response->headers->set('Content-Type', 'text/html; charset=utf-8');
            $response->headers->set('Content-Disposition', 'attachment; filename="quiz_export_' . date('Y-m-d') . '.html"');
            
            return $response;
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function generatePdfHtml($quizzes): string
    {
        $totalQuestions = 0;
        foreach ($quizzes as $quiz) {
            $questions = $this->questionRepository->findBy(['quiz' => $quiz]);
            $totalQuestions += count($questions);
        }

        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Unilearn Quiz Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .header {
            text-align: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        .section {
            background: white;
            padding: 25px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .section-title {
            color: #333;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-size: 20px;
            font-weight: bold;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .summary-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .summary-card h3 {
            margin: 0;
            font-size: 32px;
            font-weight: bold;
        }
        .summary-card p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        tr:hover {
            background-color: #f8f9fa;
        }
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-beginner { background-color: #28a745; color: white; }
        .badge-intermediate { background-color: #ffc107; color: #212529; }
        .badge-advanced { background-color: #dc3545; color: white; }
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 20px;
        }
        .chart-placeholder {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            color: #6c757d;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            padding: 20px;
            color: #6c757d;
            border-top: 1px solid #dee2e6;
        }
        .question-item {
            background: #f8f9fa;
            padding: 15px;
            margin: 10px 0;
            border-radius: 6px;
            border-left: 4px solid #667eea;
        }
        .question-text {
            font-weight: bold;
            margin-bottom: 10px;
        }
        .options {
            font-size: 14px;
            color: #555;
        }
        .correct-answer {
            color: #28a745;
            font-weight: bold;
            margin-top: 5px;
        }
        @media print {
            body { background: white; }
            .section { break-inside: avoid; }
        }
    </style>
</head>
<body>';

        // Header
        $html .= '<div class="header">
            <h1>UNILEARN QUIZ MANAGEMENT SYSTEM</h1>
            <p>Quiz Export Report - Generated on ' . date('Y-m-d H:i:s') . '</p>
            <p>Exported by: Admin User</p>
        </div>';

        // Summary Section
        $html .= '<div class="section">
            <h2 class="section-title">📊 QUIZ SUMMARY</h2>
            <div class="summary-grid">
                <div class="summary-card">
                    <h3>' . count($quizzes) . '</h3>
                    <p>Total Quizzes</p>
                </div>
                <div class="summary-card">
                    <h3>' . $totalQuestions . '</h3>
                    <p>Total Questions</p>
                </div>
                <div class="summary-card">
                    <h3>' . round($totalQuestions / max(count($quizzes), 1), 1) . '</h3>
                    <p>Avg Questions/Quiz</p>
                </div>
                <div class="summary-card">
                    <h3>100%</h3>
                    <p>Active Quizzes</p>
                </div>
            </div>
        </div>';

        // Detailed Quiz Information
        $html .= '<div class="section">
            <h2 class="section-title">📋 DETAILED QUIZ INFORMATION</h2>
            <table>
                <thead>
                    <tr>
                        <th>Quiz ID</th>
                        <th>Title</th>
                        <th>Course</th>
                        <th>Questions</th>
                        <th>Difficulty</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($quizzes as $quiz) {
            $questions = $this->questionRepository->findBy(['quiz' => $quiz]);
            $questionCount = count($questions);
            
            // Determine difficulty level
            $difficulty = 'Beginner';
            $difficultyClass = 'badge-beginner';
            if ($questionCount > 10) {
                $difficulty = 'Intermediate';
                $difficultyClass = 'badge-intermediate';
            }
            if ($questionCount > 20) {
                $difficulty = 'Advanced';
                $difficultyClass = 'badge-advanced';
            }
            
            $courseName = 'General';
            if ($quiz->getCourse()) {
                $courseName = $quiz->getCourse()->getTitle();
            }
            
            $html .= '<tr>
                <td>#' . $quiz->getId() . '</td>
                <td><strong>' . htmlspecialchars($quiz->getTitle()) . '</strong></td>
                <td>' . htmlspecialchars($courseName) . '</td>
                <td>' . $questionCount . '</td>
                <td><span class="badge ' . $difficultyClass . '">' . $difficulty . '</span></td>
                <td>' . $quiz->getCreatedAt()->format('Y-m-d') . '</td>
            </tr>';
        }

        $html .= '</tbody>
            </table>
        </div>';

        // Questions Detail Section
        $html .= '<div class="section">
            <h2 class="section-title">❓ QUESTIONS DETAIL</h2>';

        foreach ($quizzes as $quiz) {
            $questions = $this->questionRepository->findBy(['quiz' => $quiz]);
            if (count($questions) > 0) {
                $html .= '<h3 style="color: #667eea; margin-top: 30px;">' . htmlspecialchars($quiz->getTitle()) . '</h3>';
                
                foreach ($questions as $question) {
                    $html .= '<div class="question-item">
                        <div class="question-text">Q' . $question->getId() . ': ' . htmlspecialchars($question->getQuestion()) . '</div>
                        <div class="options">
                            <div>A. ' . htmlspecialchars($question->getOptionA()) . '</div>
                            <div>B. ' . htmlspecialchars($question->getOptionB()) . '</div>
                            <div>C. ' . htmlspecialchars($question->getOptionC()) . '</div>
                            <div>D. ' . htmlspecialchars($question->getOptionD()) . '</div>
                        </div>
                        <div class="correct-answer">✓ Correct Answer: ' . $question->getCorrectOption() . '</div>
                    </div>';
                }
            }
        }

        $html .= '</div>';

        // Statistics Section
        $html .= '<div class="section">
            <h2 class="section-title">📈 STATISTICS & ANALYTICS</h2>
            <div class="stats-grid">
                <div>
                    <h3 style="color: #667eea; margin-bottom: 15px;">Correct Answer Distribution</h3>
                    <table>';

        // Calculate answer distribution
        $correctAnswers = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0];
        foreach ($quizzes as $quiz) {
            $questions = $this->questionRepository->findBy(['quiz' => $quiz]);
            foreach ($questions as $question) {
                $correctAnswers[$question->getCorrectOption()]++;
            }
        }

        foreach ($correctAnswers as $option => $count) {
            $percentage = round(($count / max($totalQuestions, 1)) * 100, 1);
            $html .= '<tr>
                <td><strong>Option ' . $option . '</strong></td>
                <td>' . $count . ' questions</td>
                <td>' . $percentage . '%</td>
            </tr>';
        }

        $html .= '</table>
                </div>
                <div>
                    <h3 style="color: #667eea; margin-bottom: 15px;">Quiz Creation Timeline</h3>
                    <div class="chart-placeholder">
                        <p>📅 Quiz Creation Activity</p>
                        <p>Total: ' . count($quizzes) . ' quizzes created</p>
                    </div>
                </div>
            </div>
        </div>';

        // Footer
        $html .= '<div class="footer">
            <p><strong>Unilearn Quiz Management System</strong></p>
            <p>This report was generated automatically on ' . date('Y-m-d H:i:s') . '</p>
            <p>For questions or support, please contact the system administrator</p>
        </div>';

        $html .= '</body>
</html>';

        return $html;
    }
}
