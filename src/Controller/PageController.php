<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Course;
use App\Entity\Enrollment;
use App\Entity\Question;
use App\Entity\Quiz;
use App\Repository\QuestionRepository;
use App\Repository\QuizRepository;
use App\Service\GeminiAiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidator;
use Psr\Log\LoggerInterface;

class PageController extends AbstractController
{
    private QuizRepository $quizRepository;
    private QuestionRepository $questionRepository;
    private EntityManagerInterface $entityManager;
    private SymfonyValidator $validator;
    private LoggerInterface $logger;

    public function __construct(
        QuizRepository $quizRepository,
        QuestionRepository $questionRepository,
        EntityManagerInterface $entityManager,
        SymfonyValidator $validator,
        LoggerInterface $logger
    ) {
        $this->quizRepository = $quizRepository;
        $this->questionRepository = $questionRepository;
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->logger = $logger;
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
    public function studentDashboard(EntityManagerInterface $entityManager): Response
    {
        // Get the currently logged-in user
        $user = $this->getUser();

        if (!$user) {
            // If no user is logged in, redirect to login
            return $this->redirectToRoute('app_login');
        }

        // Get real data from database
        $courseRepository = $entityManager->getRepository(\App\Entity\Course::class);
        $enrollmentRepository = $entityManager->getRepository(\App\Entity\Enrollment::class);
        $quizRepository = $entityManager->getRepository(Quiz::class);
        
        // Get student's enrollments
        $enrollments = $enrollmentRepository->findBy(['user' => $user]);
        
        // Get enrolled courses
        $enrolledCourses = [];
        foreach ($enrollments as $enrollment) {
            $enrolledCourses[] = $enrollment->getCourse();
        }
        
        // Get available courses (not enrolled)
        $allCourses = $courseRepository->findAll();
        $availableCourses = array_filter($allCourses, function($course) use ($enrolledCourses) {
            return !in_array($course, $enrolledCourses);
        });
        
        // Get recent quiz results
        $recentQuizzes = [];
        // You can implement quiz results logic here
        
        // Calculate statistics
        $totalEnrollments = count($enrollments);
        $completedCourses = count(array_filter($enrollments, function($enrollment) {
            return $enrollment->getProgress() >= 100;
        }));
        $inProgressCourses = $totalEnrollments - $completedCourses;
        
        return $this->render('student/dashboard.html.twig', [
            'user' => $user,
            'enrollments' => $enrollments,
            'enrolledCourses' => $enrolledCourses,
            'availableCourses' => $availableCourses,
            'totalEnrollments' => $totalEnrollments,
            'completedCourses' => $completedCourses,
            'inProgressCourses' => $inProgressCourses,
            'recentQuizzes' => $recentQuizzes
        ]);
    }

    #[Route('/student-courses', name: 'app_student_courses')]
    public function studentCourses(EntityManagerInterface $entityManager): Response
    {
        // Get the currently logged-in user
        $user = $this->getUser();

        if (!$user) {
            // If no user is logged in, redirect to login
            return $this->redirectToRoute('app_login');
        }

        // Get real data from database
        $courseRepository = $entityManager->getRepository(\App\Entity\Course::class);
        $enrollmentRepository = $entityManager->getRepository(\App\Entity\Enrollment::class);
        
        // Get student's enrollments
        $enrollments = $enrollmentRepository->findBy(['user' => $user]);
        
        // Separate courses by status
        $completedCourses = [];
        $inProgressCourses = [];
        
        foreach ($enrollments as $enrollment) {
            if ($enrollment->getProgress() >= 100) {
                $completedCourses[] = $enrollment;
            } else {
                $inProgressCourses[] = $enrollment;
            }
        }
        
        return $this->render('student/courses.html.twig', [
            'user' => $user,
            'completedCourses' => $completedCourses,
            'inProgressCourses' => $inProgressCourses,
            'totalEnrollments' => count($enrollments)
        ]);
    }

    #[Route('/student-bookmarks', name: 'app_student_bookmarks')]
    public function studentBookmarks(EntityManagerInterface $entityManager): Response
    {
        // Get the currently logged-in user
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Get user's enrollments to create bookmarks from enrolled courses
        $enrollmentRepository = $entityManager->getRepository(\App\Entity\Enrollment::class);
        $enrollments = $enrollmentRepository->findBy(['user' => $user]);

        // Create bookmarks from user's enrolled courses
        $bookmarks = [];
        foreach ($enrollments as $enrollment) {
            $course = $enrollment->getCourse();
            $bookmarks[] = (object)[
                'id' => 'course_' . $enrollment->getId(),
                'title' => $course->getTitle(),
                'description' => $course->getShortDescription() ?? 'No description available',
                'type' => 'course',
                'course' => $course,
                'bookmarkedAt' => $enrollment->getEnrolledAt(),
                'progress' => $enrollment->getProgress(),
                'status' => $enrollment->getStatus()
            ];
        }

        // Add lesson bookmarks from course chapters/lessons if available
        $courseRepository = $entityManager->getRepository(\App\Entity\Course::class);
        foreach ($enrollments as $enrollment) {
            $course = $enrollment->getCourse();
            // Get chapters for this course
            $chapters = $course->getChapters();
            foreach ($chapters as $chapter) {
                // Add sample lesson bookmarks
                $bookmarks[] = (object)[
                    'id' => 'lesson_' . $chapter->getId(),
                    'title' => 'Chapter: ' . ($chapter->getTitle() ?? 'Untitled Chapter'),
                    'description' => 'Lesson content from ' . $course->getTitle(),
                    'type' => 'lesson',
                    'course' => $course,
                    'bookmarkedAt' => $enrollment->getEnrolledAt(),
                    'chapter' => $chapter
                ];
            }
        }

        // Sort bookmarks by creation date (newest first)
        usort($bookmarks, function($a, $b) {
            return $b->bookmarkedAt <=> $a->bookmarkedAt;
        });

        return $this->render('student/bookmarks.html.twig', [
            'user' => $user,
            'bookmarks' => $bookmarks,
            'totalEnrollments' => count($enrollments)
        ]);
    }

    #[Route('/student-certificates', name: 'app_student_certificates')]
    public function studentCertificates(EntityManagerInterface $entityManager): Response
    {
        // Get the currently logged-in user
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Get completed courses for certificates
        $enrollmentRepository = $entityManager->getRepository(\App\Entity\Enrollment::class);
        $enrollments = $enrollmentRepository->findBy(['user' => $user]);
        
        $completedCourses = array_filter($enrollments, function($enrollment) {
            return $enrollment->getProgress() >= 100;
        });

        // Calculate statistics
        $totalEnrollments = count($enrollments);
        $completedCount = count($completedCourses);
        $totalHours = 0;
        $averageScore = 0;

        foreach ($completedCourses as $enrollment) {
            $course = $enrollment->getCourse();
            $totalHours += $course->getDuration() ?? 0;
        }

        // Calculate average score based on progress
        if ($totalEnrollments > 0) {
            $totalProgress = array_sum(array_map(function($e) { return $e->getProgress(); }, $enrollments));
            $averageScore = round($totalProgress / $totalEnrollments, 1);
        }

        return $this->render('student/certificates.html.twig', [
            'user' => $user,
            'completedCourses' => $completedCourses,
            'totalEnrollments' => $totalEnrollments,
            'completedCount' => $completedCount,
            'totalHours' => $totalHours,
            'averageScore' => $averageScore
        ]);
    }

    #[Route('/student-settings', name: 'app_student_settings')]
    public function studentSettings(EntityManagerInterface $entityManager): Response
    {
        // Get currently logged-in user
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Get user statistics for settings page
        $enrollmentRepository = $entityManager->getRepository(\App\Entity\Enrollment::class);
        $enrollments = $enrollmentRepository->findBy(['user' => $user]);
        
        $completedCourses = array_filter($enrollments, function($enrollment) {
            return $enrollment->getProgress() >= 100;
        });

        $totalEnrollments = count($enrollments);
        $completedCount = count($completedCourses);
        $inProgressCount = $totalEnrollments - $completedCount;

        return $this->render('student/settings.html.twig', [
            'user' => $user,
            'totalEnrollments' => $totalEnrollments,
            'completedCourses' => $completedCount,
            'inProgressCourses' => $inProgressCount
        ]);
    }

    #[Route('/student-quiz-analysis', name: 'app_student_quiz_analysis')]
    public function studentQuizAnalysis(EntityManagerInterface $entityManager, \App\Service\GeminiAiService $geminiAiService): Response
    {
        // Get the currently logged-in user
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Fetch actual quiz results from database
        $quizResultRepository = $entityManager->getRepository(\App\Entity\QuizResult::class);
        $quizResults = $quizResultRepository->findBy(['user' => $user], ['takenAt' => 'DESC']);

        // Build structured data for AI analysis
        $quizData = [];
        foreach ($quizResults as $result) {
            $quiz = $result->getQuiz();
            $course = $quiz ? $quiz->getCourse() : null;
            
            $quizData[] = [
                'quiz' => $quiz ? $quiz->getTitle() : 'Unknown Quiz',
                'course' => $course ? $course->getTitle() : 'Unknown Course',
                'score' => $result->getScore(),
                'max_score' => $result->getMaxScore(),
                'percentage' => $result->getMaxScore() > 0 ? round(($result->getScore() / $result->getMaxScore()) * 100, 1) : 0,
                'taken_at' => $result->getTakenAt() ? $result->getTakenAt()->format('Y-m-d H:i:s') : null,
                'created_at' => $result->getCreatedAt() ? $result->getCreatedAt()->format('Y-m-d H:i:s') : null
            ];
        }

        // Generate AI insights
        $aiInsights = null;
        if (!empty($quizData)) {
            try {
                $aiInsights = $geminiAiService->generateQuizInsights($quizData);
            } catch (\Exception $e) {
                // Log error but continue with fallback
                $aiInsights = 'AI analysis temporarily unavailable. Please try again later.';
            }
        }

        // Calculate basic analytics for UI display
        $analytics = $this->calculateBasicAnalytics($quizData);

        return $this->render('student/quiz-analysis.html.twig', [
            'user' => $user,
            'quizResults' => $quizResults,
            'quizData' => $quizData,
            'analytics' => $analytics,
            'aiInsights' => $aiInsights
        ]);
    }

    private function calculateBasicAnalytics(array $quizData): array
    {
        $totalQuizzes = count($quizData);
        
        if ($totalQuizzes === 0) {
            return [
                'totalQuizzes' => 0,
                'totalAttempts' => 0,
                'averageScore' => 0,
                'bestScore' => 0,
                'improvementRate' => 0,
                'strengthAreas' => [],
                'weaknessAreas' => [],
                'recommendations' => ['Take more quizzes to get personalized insights'],
                'performanceTrend' => 'stable',
                'studyTime' => 0,
                'accuracyRate' => 0
            ];
        }

        $scores = array_column($quizData, 'percentage');
        $averageScore = array_sum($scores) / count($scores);
        $bestScore = max($scores);

        // Calculate improvement rate (comparing first half vs second half)
        $improvementRate = 0;
        if (count($scores) > 1) {
            $midPoint = floor(count($scores) / 2);
            $firstHalf = array_slice($scores, 0, $midPoint);
            $secondHalf = array_slice($scores, $midPoint);
            
            if (count($firstHalf) > 0 && count($secondHalf) > 0) {
                $firstHalfAvg = array_sum($firstHalf) / count($firstHalf);
                $secondHalfAvg = array_sum($secondHalf) / count($secondHalf);
                $improvementRate = round((($secondHalfAvg - $firstHalfAvg) / $firstHalfAvg) * 100, 1);
            }
        }

        // Identify strength and weakness areas
        $strengthAreas = [];
        $weaknessAreas = [];
        
        foreach ($quizData as $quiz) {
            if ($quiz['percentage'] >= 80) {
                $strengthAreas[] = $quiz['quiz'];
            } elseif ($quiz['percentage'] < 60) {
                $weaknessAreas[] = $quiz['quiz'];
            }
        }

        // Generate recommendations based on performance
        $recommendations = [];
        if ($averageScore < 60) {
            $recommendations[] = 'Focus on fundamental concepts before advanced topics';
            $recommendations[] = 'Practice with timed quizzes to improve speed';
        } elseif ($averageScore < 80) {
            $recommendations[] = 'Review incorrect answers to understand patterns';
            $recommendations[] = 'Try advanced difficulty quizzes';
        } else {
            $recommendations[] = 'Excellent performance! Try expert-level challenges';
            $recommendations[] = 'Consider mentoring other students';
        }

        // Performance trend
        $performanceTrend = 'stable';
        if (count($scores) >= 3) {
            $recent = array_slice($scores, -3);
            if ($recent[2] > $recent[1] && $recent[1] > $recent[0]) {
                $performanceTrend = 'improving';
            } elseif ($recent[2] < $recent[1] && $recent[1] < $recent[0]) {
                $performanceTrend = 'declining';
            }
        }

        return [
            'totalQuizzes' => $totalQuizzes,
            'totalAttempts' => $totalQuizzes, // Each result represents one attempt
            'averageScore' => round($averageScore, 1),
            'bestScore' => $bestScore,
            'improvementRate' => $improvementRate,
            'strengthAreas' => array_unique($strengthAreas),
            'weaknessAreas' => array_unique($weaknessAreas),
            'recommendations' => $recommendations,
            'performanceTrend' => $performanceTrend,
            'studyTime' => $totalQuizzes * 15, // Estimated 15 minutes per attempt
            'accuracyRate' => round($averageScore, 0),
            'recentPerformance' => $this->getRecentPerformance($quizData),
            'scoreDistribution' => $this->getScoreDistribution($quizData),
            'weeklyProgress' => $this->getWeeklyProgress($quizData)
        ];
    }

    private function getRecentPerformance(array $quizData): array
    {
        $recent = array_slice($quizData, -7);
        return array_map(function($quiz) {
            return [
                'date' => $quiz['taken_at'],
                'score' => $quiz['percentage'],
                'quiz' => $quiz['quiz']
            ];
        }, $recent);
    }

    private function getScoreDistribution(array $quizData): array
    {
        $distribution = [
            '0-40' => 0,
            '41-60' => 0,
            '61-80' => 0,
            '81-100' => 0
        ];
        
        foreach ($quizData as $quiz) {
            $score = $quiz['percentage'];
            if ($score <= 40) $distribution['0-40']++;
            elseif ($score <= 60) $distribution['41-60']++;
            elseif ($score <= 80) $distribution['61-80']++;
            else $distribution['81-100']++;
        }
        
        return $distribution;
    }

    private function getWeeklyProgress(array $quizData): array
    {
        $weekly = [];
        $now = new \DateTime();
        
        for ($i = 6; $i >= 0; $i--) {
            $date = (clone $now)->modify("-$i days");
            $dateStr = $date->format('Y-m-d');
            $weekly[$dateStr] = [
                'date' => $dateStr,
                'quizzes' => 0,
                'avgScore' => 0,
                'totalScore' => 0
            ];
        }
        
        foreach ($quizData as $quiz) {
            $quizDate = (new \DateTime($quiz['taken_at']))->format('Y-m-d');
            if (isset($weekly[$quizDate])) {
                $weekly[$quizDate]['quizzes']++;
                $weekly[$quizDate]['totalScore'] += $quiz['percentage'];
            }
        }
        
        // Calculate averages
        foreach ($weekly as &$day) {
            if ($day['quizzes'] > 0) {
                $day['avgScore'] = round($day['totalScore'] / $day['quizzes'], 1);
            }
        }
        
        return array_values($weekly);
    }

    #[Route('/api/quiz-analytics', name: 'api_quiz_analytics')]
    public function getQuizAnalytics(EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], 401);
        }

        $quizResultRepository = $entityManager->getRepository(\App\Entity\QuizResult::class);
        $quizResults = $quizResultRepository->findBy(['user' => $user], ['takenAt' => 'DESC'], 10);

        $quizData = [];
        foreach ($quizResults as $result) {
            $quiz = $result->getQuiz();
            $course = $quiz ? $quiz->getCourse() : null;
            
            $quizData[] = [
                'id' => $result->getId(),
                'quiz' => $quiz ? $quiz->getTitle() : 'Unknown Quiz',
                'course' => $course ? $course->getTitle() : 'Unknown Course',
                'score' => $result->getScore(),
                'maxScore' => $result->getMaxScore(),
                'percentage' => $result->getMaxScore() > 0 ? round(($result->getScore() / $result->getMaxScore()) * 100, 1) : 0,
                'takenAt' => $result->getTakenAt() ? $result->getTakenAt()->format('Y-m-d H:i:s') : null,
                'createdAt' => $result->getCreatedAt() ? $result->getCreatedAt()->format('Y-m-d H:i:s') : null
            ];
        }

        $analytics = $this->calculateBasicAnalytics($quizData);

        return new JsonResponse([
            'quizResults' => $quizData,
            'analytics' => $analytics,
            'lastUpdated' => (new \DateTime())->format('Y-m-d H:i:s')
        ]);
    }

    #[Route('/student-ai-roadmap', name: 'app_student_ai_roadmap')]
    public function studentAiRoadmap(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Fetch all courses from database for AI recommendations
        $courseRepository = $entityManager->getRepository(Course::class);
        $allCourses = $courseRepository->findAll();
        
        // Get user's enrolled courses for personalized recommendations
        $enrollmentRepository = $entityManager->getRepository(Enrollment::class);
        $userEnrollments = $enrollmentRepository->findBy(['user' => $user]);
        
        // Get course categories for better recommendations
        $categoryRepository = $entityManager->getRepository(Category::class);
        $categories = $categoryRepository->findAll();

        return $this->render('student/ai-learning-roadmap.html.twig', [
            'user' => $user,
            'allCourses' => $allCourses,
            'userEnrollments' => $userEnrollments,
            'categories' => $categories
        ]);
    }
    
    #[Route('/api/test', name: 'api_test', methods: ['GET'])]
    public function testRoute(EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $courseRepository = $entityManager->getRepository(Course::class);
            $allCourses = $courseRepository->findAll();
            
            return new JsonResponse([
                'success' => true,
                'total_courses' => count($allCourses),
                'message' => 'Test route working'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api/courses/recommendations', name: 'api_course_recommendations', methods: ['GET'])]
    public function getCourseRecommendations(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $learningGoal = $request->query->get('goal', '');
        $skillLevel = $request->query->get('level', 'beginner');
        
        try {
            // Get all courses from database
            $courseRepository = $entityManager->getRepository(Course::class);
            $allCourses = $courseRepository->findAll();
            
            // Filter courses based on learning goal and skill level using semantic analysis
            $recommendedCourses = [];
            $goalLower = strtolower($learningGoal);
            
            foreach ($allCourses as $course) {
                $courseTitle = strtolower($course->getTitle());
                $courseDescription = strtolower($course->getShortDescription());
                $courseLevel = strtolower($course->getLevel());
                $courseCategory = strtolower($course->getCategory()?->getName() ?? '');
                
                // Check if course matches the learning goal using semantic analysis
                $matchesGoal = $this->courseMatchesGoal($courseTitle, $courseDescription, $courseCategory, $goalLower);
                $matchesLevel = $skillLevel === 'all' || $courseLevel === $skillLevel;
                
                if ($matchesGoal && $matchesLevel) {
                    $recommendedCourses[] = [
                        'id' => $course->getId(),
                        'title' => $course->getTitle(),
                        'shortDescription' => $course->getShortDescription(),
                        'level' => $course->getLevel(),
                        'category' => $course->getCategory()?->getName(),
                        'price' => $course->getPrice(),
                        'thumbnailUrl' => $course->getThumbnailUrl()
                    ];
                }
            }
            
            return new JsonResponse([
                'success' => true,
                'courses' => $recommendedCourses,
                'total' => count($recommendedCourses),
                'goal' => $learningGoal,
                'level' => $skillLevel
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Error fetching course recommendations: ' . $e->getMessage());
            
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to fetch course recommendations',
                'courses' => [],
                'total' => 0
            ], 500);
        }
    }

    #[Route('/api/ai-roadmap/generate', name: 'api_ai_roadmap_generate', methods: ['POST'])]
    public function generateAiRoadmap(Request $request, EntityManagerInterface $entityManager, GeminiAiService $geminiAiService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $learningGoal = $data['learning_goal'] ?? '';
        $skillLevel = $data['skill_level'] ?? 'beginner';
        $timeCommitment = $data['time_commitment'] ?? '3-5';
        $learningStyles = $data['learning_styles'] ?? [];
        
        // Get user's learning history
        $enrollmentRepository = $entityManager->getRepository(Enrollment::class);
        $quizResultRepository = $entityManager->getRepository(\App\Entity\QuizResult::class);
        
        $userEnrollments = $enrollmentRepository->findBy(['user' => $user]);
        $quizResults = $quizResultRepository->findBy(['user' => $user], ['takenAt' => 'DESC'], 20);
        
        // Build user history data
        $userHistory = [
            'enrollments' => array_map(function($enrollment) {
                $course = $enrollment->getCourse();
                return [
                    'course_title' => $course->getTitle(),
                    'category' => $course->getCategory()?->getName(),
                    'level' => $course->getLevel(),
                    'progress' => $enrollment->getProgress(),
                    'status' => $enrollment->getStatus(),
                    'enrolled_at' => $enrollment->getEnrolledAt()?->format('Y-m-d')
                ];
            }, $userEnrollments),
            'quiz_results' => array_map(function($result) {
                $quiz = $result->getQuiz();
                return [
                    'quiz_title' => $quiz->getTitle(),
                    'course' => $quiz->getCourse()?->getTitle(),
                    'score' => $result->getScore(),
                    'max_score' => $result->getMaxScore(),
                    'percentage' => $result->getMaxScore() > 0 ? round(($result->getScore() / $result->getMaxScore()) * 100, 1) : 0,
                    'taken_at' => $result->getTakenAt()?->format('Y-m-d')
                ];
            }, $quizResults)
        ];
        
        // Get available courses
        $courseRepository = $entityManager->getRepository(Course::class);
        $allCourses = $courseRepository->findAll();
        
        $availableCourses = array_map(function($course) {
            return [
                'id' => $course->getId(),
                'title' => $course->getTitle(),
                'shortDescription' => $course->getShortDescription(),
                'level' => $course->getLevel(),
                'category' => $course->getCategory()?->getName(),
                'price' => $course->getPrice(),
                'duration' => $course->getDuration()
            ];
        }, $allCourses);
        
        // Prepare user data for AI
        $userData = [
            'learning_goal' => $learningGoal,
            'skill_level' => $skillLevel,
            'time_commitment' => $timeCommitment,
            'learning_styles' => $learningStyles,
            'user_profile' => [
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'total_enrollments' => count($userEnrollments),
                'completed_courses' => count(array_filter($userEnrollments, fn($e) => $e->getProgress() >= 100)),
                'average_quiz_score' => count($quizResults) > 0 ? array_sum(array_map(fn($r) => $r->getMaxScore() > 0 ? ($r->getScore() / $r->getMaxScore()) * 100 : 0, $quizResults)) / count($quizResults) : 0
            ]
        ];
        
        try {
            // Generate AI roadmap
            $roadmap = $geminiAiService->generateLearningRoadmap($userData, $availableCourses, $userHistory);
            
            return new JsonResponse([
                'success' => true,
                'roadmap' => $roadmap,
                'user_history' => $userHistory,
                'generated_at' => (new \DateTime())->format('Y-m-d H:i:s')
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('AI Roadmap generation error: ' . $e->getMessage());
            
            // Fallback to enhanced rule-based roadmap
            $fallbackRoadmap = $this->generateEnhancedRoadmap($userData, $availableCourses, $userHistory);
            
            return new JsonResponse([
                'success' => true,
                'roadmap' => $fallbackRoadmap,
                'user_history' => $userHistory,
                'generated_at' => (new \DateTime())->format('Y-m-d H:i:s'),
                'fallback_used' => true
            ]);
        }
    }
    
    private function generateEnhancedRoadmap(array $userData, array $availableCourses, array $userHistory): array
    {
        $goal = $userData['learning_goal'];
        $level = $userData['skill_level'];
        $timeCommitment = $userData['time_commitment'];
        
        // Enhanced course filtering with semantic analysis
        $relevantCourses = [];
        foreach ($availableCourses as $course) {
            if ($this->courseMatchesGoal($course['title'], $course['shortDescription'], $course['category'], $goal)) {
                $relevantCourses[] = $course;
            }
        }
        
        // Sort by relevance and logical progression
        usort($relevantCourses, function($a, $b) use ($level) {
            // Level appropriateness scoring
            $levelOrder = ['beginner' => 0, 'intermediate' => 1, 'advanced' => 2];
            $aLevelScore = abs(($levelOrder[$a['level']] ?? 1) - ($levelOrder[$level] ?? 0));
            $bLevelScore = abs(($levelOrder[$b['level']] ?? 1) - ($levelOrder[$level] ?? 0));
            
            if ($aLevelScore !== $bLevelScore) {
                return $aLevelScore - $bLevelScore;
            }
            
            // Progression scoring
            $aProgression = $this->analyzeCourseProgression($a['title'], $a['shortDescription'], $goal);
            $bProgression = $this->analyzeCourseProgression($b['title'], $b['shortDescription'], $goal);
            
            return $aProgression - $bProgression;
        });
        
        // Generate intelligent learning path
        $weeks = max(4, min(16, (int)explode('-', $timeCommitment)[0] * 2));
        $roadmap = $this->createIntelligentRoadmapStructure($goal, $level, $weeks, $relevantCourses, $userHistory);
        
        return $roadmap;
    }
    
    private function createIntelligentRoadmapStructure(string $goal, string $level, int $weeks, array $courses, array $history): array
    {
        $roadmap = [
            'roadmap' => [
                'title' => "Intelligent Learning Roadmap for {$goal}",
                'description' => "Personalized learning path based on your goals, skill level, and learning history",
                'estimated_duration' => "{$weeks} weeks",
                'difficulty_progression' => "{$level} → intermediate → advanced",
                'personalization_factors' => [
                    'learning_goal_analysis' => true,
                    'skill_level_assessment' => true,
                    'learning_history_integration' => true,
                    'semantic_course_matching' => true,
                    'prerequisite_analysis' => true
                ]
            ],
            'weeks' => [],
            'milestones' => [],
            'adaptation_notes' => 'This roadmap adapts based on your quiz performance, course completion rates, and learning pace. The AI will recommend adjustments as you progress.',
            'success_criteria' => 'Complete each week\'s objectives with >80% quiz scores and finish all project assignments',
            'next_steps' => 'After completion, you\'ll be ready for advanced specialization or real-world projects in your chosen field'
        ];
        
        // Generate weekly content with intelligence
        for ($week = 1; $week <= $weeks; $week++) {
            $weekData = $this->generateIntelligentWeek($week, $goal, $level, $courses, $history);
            $roadmap['weeks'][] = $weekData;
        }
        
        // Add milestones
        $roadmap['milestones'] = $this->generateMilestones($weeks, $goal, $level);
        
        return $roadmap;
    }
    
    private function generateIntelligentWeek(int $week, string $goal, string $level, array $courses, array $history): array
    {
        // Analyze user's learning patterns
        $learningPatterns = $this->analyzeLearningPatterns($history);
        
        // Select appropriate courses for this week
        $weekCourses = $this->selectWeekCourses($week, $courses, $level, count($history['enrollments'] ?? []));
        
        // Generate week-specific objectives
        $objectives = $this->generateWeekObjectives($week, $goal, $level, $weekCourses, $learningPatterns);
        
        // Determine focus area based on progression
        $focus = $this->determineWeekFocus($week, $goal, $level, $weekCourses);
        
        // Recommend activities based on learning styles
        $activities = $this->recommendActivities($week, $focus, $learningPatterns);
        
        return [
            'week' => $week,
            'title' => "Week {$week}: " . $focus,
            'focus' => $focus,
            'objectives' => $objectives,
            'course_recommendations' => $weekCourses,
            'activities' => $activities,
            'estimated_hours' => $this->calculateEstimatedHours($week, $level, $activities),
            'difficulty' => $this->adjustDifficulty($week, $level, $learningPatterns),
            'assessment_type' => $this->determineAssessmentType($week, $focus),
            'personalization_notes' => $this->generatePersonalizationNotes($week, $learningPatterns, $history)
        ];
    }
    
    private function analyzeLearningPatterns(array $history): array
    {
        $patterns = [
            'preferred_difficulty' => 'intermediate',
            'learning_pace' => 'moderate',
            'strength_areas' => [],
            'improvement_areas' => [],
            'preferred_content_types' => ['practical', 'visual'],
            'quiz_performance_trend' => 'stable'
        ];
        
        // Analyze quiz performance
        if (!empty($history['quiz_results'])) {
            $scores = array_column($history['quiz_results'], 'percentage');
            $averageScore = array_sum($scores) / count($scores);
            
            if ($averageScore >= 85) {
                $patterns['preferred_difficulty'] = 'advanced';
                $patterns['learning_pace'] = 'fast';
            } elseif ($averageScore >= 70) {
                $patterns['preferred_difficulty'] = 'intermediate';
                $patterns['learning_pace'] = 'moderate';
            } else {
                $patterns['preferred_difficulty'] = 'beginner';
                $patterns['learning_pace'] = 'steady';
            }
            
            // Identify strengths and weaknesses
            foreach ($history['quiz_results'] as $result) {
                if ($result['percentage'] >= 80) {
                    $patterns['strength_areas'][] = $result['quiz_title'];
                } elseif ($result['percentage'] < 60) {
                    $patterns['improvement_areas'][] = $result['quiz_title'];
                }
            }
        }
        
        return $patterns;
    }
    
    private function selectWeekCourses(int $week, array $courses, string $level, int $previousEnrollments): array
    {
        $recommendations = [];
        $coursesPerWeek = min(2, max(1, ceil(count($courses) / 4)));
        
        // Select courses based on logical progression
        $startIndex = ($week - 1) * $coursesPerWeek;
        $endIndex = min($startIndex + $coursesPerWeek, count($courses));
        
        for ($i = $startIndex; $i < $endIndex; $i++) {
            if (isset($courses[$i])) {
                $course = $courses[$i];
                $recommendations[] = [
                    'course_id' => $course['id'],
                    'title' => $course['title'],
                    'relevance_score' => $this->calculateCourseRelevance($course, $week, $level),
                    'reason' => $this->generateCourseReason($course, $week, $level),
                    'level' => $course['level'],
                    'category' => $course['category'],
                    'estimated_weeks' => $this->estimateCourseWeeks($course, $level)
                ];
            }
        }
        
        return $recommendations;
    }
    
    private function calculateCourseRelevance(array $course, int $week, string $level): float
    {
        $baseScore = 0.8;
        
        // Level matching
        if ($course['level'] === $level) {
            $baseScore += 0.2;
        }
        
        // Week progression bonus
        if ($week <= 2 && in_array($course['level'], ['beginner', 'intro'])) {
            $baseScore += 0.1;
        } elseif ($week > 2 && in_array($course['level'], ['intermediate', 'advanced'])) {
            $baseScore += 0.1;
        }
        
        return min(1.0, $baseScore);
    }
    
    private function generateCourseReason(array $course, int $week, string $level): string
    {
        $reasons = [
            'Builds foundational knowledge for your learning journey',
            'Introduces key concepts and terminology',
            'Provides practical hands-on experience',
            'Expands on previous week\'s learning',
            'Prepares you for advanced topics',
            'Aligns with your learning objectives'
        ];
        
        return $reasons[($week - 1) % count($reasons)];
    }
    
    private function estimateCourseWeeks(array $course, string $level): int
    {
        $baseWeeks = 2;
        
        if ($course['level'] === 'beginner') {
            return $baseWeeks;
        } elseif ($course['level'] === 'intermediate') {
            return $baseWeeks + 1;
        } else {
            return $baseWeeks + 2;
        }
    }
    
    private function generateWeekObjectives(int $week, string $goal, string $level, array $courses, array $patterns): array
    {
        $baseObjectives = [
            'Complete core learning modules',
            'Practice with hands-on exercises',
            'Pass weekly assessment with >80% score'
        ];
        
        // Add personalized objectives based on patterns
        if (!empty($patterns['improvement_areas'])) {
            $baseObjectives[] = 'Focus extra time on weak areas: ' . implode(', ', array_slice($patterns['improvement_areas'], 0, 2));
        }
        
        if (!empty($courses)) {
            $baseObjectives[] = 'Progress through recommended course materials';
        }
        
        return $baseObjectives;
    }
    
    private function determineWeekFocus(int $week, string $goal, string $level, array $courses): string
    {
        $focusAreas = $this->getProgressionFocusAreas($goal, $level);
        $index = ($week - 1) % count($focusAreas);
        
        return $focusAreas[$index];
    }
    
    private function getProgressionFocusAreas(string $goal, string $level): array
    {
        $goalLower = strtolower($goal);
        
        if (strpos($goalLower, 'web development') !== false) {
            return ['HTML & CSS Foundations', 'JavaScript Essentials', 'DOM Manipulation', 'Responsive Design', 'Modern Frameworks', 'Backend Integration'];
        } elseif (strpos($goalLower, 'data science') !== false) {
            return ['Python Fundamentals', 'Data Analysis Basics', 'Statistical Concepts', 'Machine Learning Intro', 'Data Visualization', 'Advanced ML Techniques'];
        } elseif (strpos($goalLower, 'mobile') !== false) {
            return ['Mobile Basics', 'UI/UX Principles', 'Platform Fundamentals', 'App Development', 'Testing & Debugging', 'Deployment Strategies'];
        } else {
            return ['Foundation Concepts', 'Core Skills Development', 'Practical Application', 'Advanced Topics', 'Specialization', 'Mastery & Innovation'];
        }
    }
    
    private function recommendActivities(int $week, string $focus, array $patterns): array
    {
        $activities = ['Reading & Study', 'Practice Exercises', 'Video Tutorials'];
        
        // Add activities based on learning patterns
        if (in_array('practical', $patterns['preferred_content_types'])) {
            $activities[] = 'Hands-on Projects';
        }
        
        if (in_array('visual', $patterns['preferred_content_types'])) {
            $activities[] = 'Video Demonstrations';
        }
        
        if ($week % 2 === 0) {
            $activities[] = 'Peer Discussion';
        }
        
        return $activities;
    }
    
    private function calculateEstimatedHours(int $week, string $level, array $activities): int
    {
        $baseHours = 8;
        
        if ($level === 'advanced') {
            $baseHours += 2;
        } elseif ($level === 'beginner') {
            $baseHours -= 2;
        }
        
        // Add time for activities
        $activityHours = count($activities) * 1.5;
        
        return max(5, min(15, $baseHours + $activityHours));
    }
    
    private function adjustDifficulty(int $week, string $level, array $patterns): string
    {
        // Adjust based on learning patterns
        if ($patterns['preferred_difficulty'] === 'advanced' && $week > 2) {
            return 'advanced';
        } elseif ($patterns['preferred_difficulty'] === 'beginner' && $week <= 4) {
            return 'beginner';
        }
        
        return $level;
    }
    
    private function determineAssessmentType(int $week, string $focus): string
    {
        if ($week % 2 === 0) {
            return 'project';
        } elseif ($week % 3 === 0) {
            return 'assignment';
        } else {
            return 'quiz';
        }
    }
    
    private function generatePersonalizationNotes(int $week, array $patterns, array $history): string
    {
        $notes = [];
        
        if (!empty($patterns['strength_areas'])) {
            $notes[] = 'Leverage your strength in ' . $patterns['strength_areas'][0];
        }
        
        if (!empty($patterns['improvement_areas'])) {
            $notes[] = 'Extra practice recommended for ' . $patterns['improvement_areas'][0];
        }
        
        if ($patterns['learning_pace'] === 'fast') {
            $notes[] = 'You can progress quickly through familiar topics';
        } elseif ($patterns['learning_pace'] === 'steady') {
            $notes[] = 'Take your time to build strong foundations';
        }
        
        return implode('. ', $notes);
    }
    
    private function generateMilestones(int $weeks, string $goal, string $level): array
    {
        $milestones = [];
        
        // Mid-point milestone
        $midWeek = floor($weeks / 2);
        $milestones[] = [
            'week' => $midWeek,
            'title' => 'Mid-point Competency Check',
            'description' => 'You should have a solid understanding of core concepts and be ready for intermediate topics',
            'skills_gained' => ['Fundamental knowledge', 'Practical skills', 'Problem-solving abilities']
        ];
        
        // Final milestone
        $milestones[] = [
            'week' => $weeks,
            'title' => 'Learning Goal Achievement',
            'description' => 'You\'ve completed your learning journey and are ready for advanced challenges or real-world application',
            'skills_gained' => ['Complete understanding', 'Practical application', 'Confidence in subject matter']
        ];
        
        return $milestones;
    }
    
    #[Route('/api/roadmap/save', name: 'api_roadmap_save', methods: ['POST'])]
    public function saveRoadmap(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], 401);
        }

        try {
            $data = json_decode($request->getContent(), true);
            
            // Validate required fields
            if (empty($data['learning_goal']) || empty($data['skill_level'])) {
                return new JsonResponse(['error' => 'Missing required fields'], 400);
            }
            
            // Create or update roadmap entity (you'll need to create this entity)
            $roadmap = new \App\Entity\LearningRoadmap();
            $roadmap->setUser($user);
            $roadmap->setLearningGoal($data['learning_goal']);
            $roadmap->setSkillLevel($data['skill_level']);
            $roadmap->setTimeCommitment($data['time_commitment'] ?? '3-5');
            $roadmap->setLearningStyles($data['learning_styles'] ?? []);
            $roadmap->setRoadmapContent($data['roadmap_content'] ?? []);
            $roadmap->setGeneratedAt(new \DateTime($data['generated_at'] ?? 'now'));
            $roadmap->setIsActive(true);
            
            $entityManager->persist($roadmap);
            $entityManager->flush();
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Roadmap saved successfully',
                'roadmap_id' => $roadmap->getId()
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Error saving roadmap: ' . $e->getMessage());
            
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to save roadmap. Please try again.'
            ], 500);
        }
    }
    
    private function courseMatchesGoal(string $title, string $description, string $category, string $goal): bool
    {
        // Simple keyword matching for now - can be enhanced later
        $goalLower = strtolower(trim($goal));
        $titleLower = strtolower(trim($title));
        $descriptionLower = strtolower(trim($description));
        $categoryLower = strtolower(trim($category));
        
        // Check for direct keyword matches
        $goalKeywords = explode(' ', $goalLower);
        $titleWords = explode(' ', $titleLower);
        $descriptionWords = explode(' ', $descriptionLower);
        
        // Count matching keywords
        $matches = 0;
        $totalKeywords = count($goalKeywords);
        
        foreach ($goalKeywords as $keyword) {
            if (in_array($keyword, $titleWords) || in_array($keyword, $descriptionWords)) {
                $matches++;
            }
        }
        
        // If at least 30% of keywords match, consider it a match
        $matchPercentage = $totalKeywords > 0 ? ($matches / $totalKeywords) : 0;
        
        return $matchPercentage >= 0.3;
    }
    
    private function calculateSemanticSimilarity(string $goal, string $title, string $description, string $category): float
    {
        // Simple similarity calculation
        $goalWords = array_unique(explode(' ', strtolower($goal)));
        $titleWords = array_unique(explode(' ', strtolower($title)));
        $descriptionWords = array_unique(explode(' ', strtolower($description)));
        
        $allWords = array_unique(array_merge($titleWords, $descriptionWords));
        
        if (empty($allWords)) return 0.0;
        
        $matches = 0;
        foreach ($goalWords as $word) {
            if (in_array($word, $allWords)) {
                $matches++;
            }
        }
        
        return count($goalWords) > 0 ? ($matches / count($goalWords)) : 0.0;
    }
    
    private function analyzeLearningObjectiveAlignment(string $goal, string $title, string $description): float
    {
        // Simple objective alignment
        return 0.7; // Placeholder
    }
    
    private function analyzeSkillProgression(string $goal, string $title, string $description): float
    {
        // Simple progression analysis
        return 0.6; // Placeholder
    }
    
    private function analyzePrerequisiteDependencies(string $goal, string $title, string $description): float
    {
        // Simple prerequisite analysis
        return 0.5; // Placeholder
    }
    
    private function analyzeCareerRelevance(string $goal, string $title, string $description, string $category): float
    {
        // Simple career relevance
        return 0.6; // Placeholder
    }
    
    private function createConceptVector(string $text): array
    {
        // Simple concept vector creation
        $words = array_unique(explode(' ', strtolower($text)));
        return array_fill_keys($words, 1);
    }
    
    private function calculateCosineSimilarity(array $vector1, array $vector2): float
    {
        // Simple cosine similarity
        $commonWords = array_intersect_key($vector1, $vector2);
        
        if (empty($commonWords)) return 0.0;
        
        $dotProduct = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;
        
        foreach ($vector1 as $word => $value) {
            $magnitude1 += $value * $value;
        }
        
        foreach ($vector2 as $word => $value) {
            $magnitude2 += $value * $value;
        }
        
        foreach ($commonWords as $word => $value) {
            $dotProduct += $value * $vector2[$word];
        }
        
        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);
        
        return ($magnitude1 * $magnitude2) > 0 ? ($dotProduct / ($magnitude1 * $magnitude2)) : 0.0;
    }
    
    private function analyzeCourseProgression(string $title, string $description, string $goal): int
    {
        // Simple progression analysis
        return 1; // Placeholder
    }
}
