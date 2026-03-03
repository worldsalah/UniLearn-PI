<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\CourseTest;
use App\Entity\CourseTestAnswer;
use App\Entity\CourseTestResult;
use App\Entity\Enrollment;
use App\Entity\User;
use App\Repository\CourseRepository;
use App\Repository\CourseTestRepository;
use App\Repository\EnrollmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CourseTestController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private CourseTestRepository $testRepository;

    public function __construct(EntityManagerInterface $entityManager, CourseTestRepository $testRepository)
    {
        $this->entityManager = $entityManager;
        $this->testRepository = $testRepository;
    }

    #[Route('/course/{id}/test', name: 'app_course_test', methods: ['GET'])]
    public function startTest(Course $course): Response
    {
        $user = $this->getUser();
        if ($user === null) {
            return $this->redirectToRoute('app_login');
        }

        // Check if user is the instructor/creator of this course
        $courseUser = $course->getUser();
        $isInstructor = $courseUser !== null && $courseUser->getId() === $user->getId();
        $hasInstructorRole = in_array('ROLE_INSTRUCTOR', $user->getRoles(), true);
        
        // Instructors can take tests for their own courses without enrollment
        if ($isInstructor && $hasInstructorRole) {
            // Get final quiz first (new system), fallback to CourseTest
            $finalQuiz = $course->getFinalQuiz();
            if ($finalQuiz !== null) {
                return $this->render('course/test/start.html.twig', [
                    'course' => $course,
                    'test' => $finalQuiz,
                    'enrollment' => null,
                    'isInstructor' => true
                ]);
            }
            
            // Get or create test for this course (old system)
            $test = $this->testRepository->findOneBy(['course' => $course]);
            
            if ($test === null) {
                // Create a default test if none exists
                $test = new CourseTest();
                $test->setCourse($course);
                $test->setTitle('Course Completion Test');
                $test->setDescription('Test your knowledge of this course material');
                $test->setTimeLimit(60);
                $test->setPassingScore(70);
                
                $this->entityManager->persist($test);
                $this->entityManager->flush();
            }

            return $this->render('course/test/start.html.twig', [
                'course' => $course,
                'test' => $test,
                'enrollment' => null,
                'isInstructor' => true
            ]);
        }

        // Check if user is enrolled (for students)
        $enrollment = $this->entityManager->getRepository('App\Entity\Enrollment')
            ->findOneBy(['user' => $user, 'course' => $course]);
        
        if ($enrollment === null) {
            $this->addFlash('error', 'You must be enrolled in this course to take the test.');
            return $this->redirectToRoute('app_course_show', ['id' => $course->getId()]);
        }

        // Get or create test for this course
        $test = $this->testRepository->findOneBy(['course' => $course]);
        
        if ($test === null) {
            // Create a default test if none exists
            $test = new CourseTest();
            $test->setCourse($course);
            $test->setTitle('Course Completion Test');
            $test->setDescription('Test your knowledge of this course material');
            $test->setTimeLimit(60); // 60 minutes
            $test->setPassingScore(70); // 70% to pass
            
            $this->entityManager->persist($test);
            $this->entityManager->flush();
        }

        // Check if user has already taken this test
        $existingResult = $this->entityManager->getRepository('App\Entity\CourseTestResult')
            ->findOneBy(['user' => $user, 'courseTest' => $test]);

        if ($existingResult !== null) {
            return $this->redirectToRoute('app_course_test_result', ['id' => $course->getId()]);
        }

        return $this->render('course/test/start.html.twig', [
            'course' => $course,
            'test' => $test,
            'enrollment' => $enrollment
        ]);
    }

    #[Route('/course/{id}/test/take', name: 'app_course_test_take', methods: ['GET', 'POST'])]
    public function takeTest(Course $course, Request $request): Response
    {
        $user = $this->getUser();
        if ($user === null) {
            return $this->redirectToRoute('app_login');
        }

        // Check if user is the instructor/creator of this course
        $courseUser = $course->getUser();
        $isInstructor = $courseUser !== null && $courseUser->getId() === $user->getId();
        $hasInstructorRole = in_array('ROLE_INSTRUCTOR', $user->getRoles(), true);

        $test = $this->testRepository->findOneBy(['course' => $course]);
        if ($test === null) {
            throw $this->createNotFoundException('Test not found');
        }

        if ($request->isMethod('POST')) {
            $answers = $request->request->all('answers');
            $score = 0;
            $totalQuestions = count($test->getQuestions());

            // Calculate score
            foreach ($test->getQuestions() as $question) {
                $questionId = $question->getId();
                if (isset($answers[$questionId])) {
                    $userAnswer = $answers[$questionId];
                    if ($userAnswer === $question->getCorrectAnswer()) {
                        $score++;
                    }
                }
            }

            $percentage = $totalQuestions > 0 ? ($score / $totalQuestions) * 100 : 0;
            $passed = $percentage >= $test->getPassingScore();

            // Save test result
            $result = new CourseTestResult();
            if ($user instanceof \App\Entity\User) {
                $result->setUser($user);
            }
            $result->setCourseTest($test);
            $result->setScore($score);
            $result->setTotalQuestions($totalQuestions);
            $result->setPercentage($percentage);
            $result->setPassed($passed);
            $result->setTimeTaken(new \DateTime());
            $result->setAnswers($answers);

            $this->entityManager->persist($result);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_course_test_result', ['id' => $course->getId()]);
        }

        return $this->render('course/test/take.html.twig', [
            'course' => $course,
            'test' => $test,
            'isInstructor' => $isInstructor && $hasInstructorRole
        ]);
    }

    #[Route('/course/{id}/test/result', name: 'app_course_test_result', methods: ['GET'])]
    public function testResult(Course $course): Response
    {
        $user = $this->getUser();
        if ($user === null) {
            return $this->redirectToRoute('app_login');
        }

        // Check if user is the instructor/creator of this course
        $courseUser = $course->getUser();
        $isInstructor = $courseUser !== null && $courseUser->getId() === $user->getId();
        $hasInstructorRole = in_array('ROLE_INSTRUCTOR', $user->getRoles(), true);

        $test = $this->testRepository->findOneBy(['course' => $course]);
        if ($test === null) {
            throw $this->createNotFoundException('Test not found');
        }

        $result = $this->entityManager->getRepository('App\Entity\CourseTestResult')
            ->findOneBy(['user' => $user, 'courseTest' => $test]);

        if ($result === null) {
            return $this->redirectToRoute('app_course_test', ['id' => $course->getId()]);
        }

        // Get AI recommendations based on test performance
        $recommendations = $user instanceof \App\Entity\User ? $this->generateAIRecommendations($user, $course, $result) : [];

        return $this->render('course/test/result.html.twig', [
            'course' => $course,
            'test' => $test,
            'result' => $result,
            'recommendations' => $recommendations,
            'isInstructor' => $isInstructor && $hasInstructorRole
        ]);
    }

    private function generateAIRecommendations(User $user, Course $course, CourseTestResult $result): array
    {
        $recommendations = [];
        $score = $result->getPercentage();
        $passed = $result->getPassed();

        // Analyze user's test performance
        if ($passed) {
            if ($score >= 90) {
                $recommendations[] = [
                    'type' => 'advanced',
                    'title' => 'Excellent Performance!',
                    'description' => 'You have mastered this course material. Consider advanced topics or related specializations.',
                    'courses' => $this->getAdvancedRecommendations($course),
                    'icon' => 'trophy',
                    'color' => 'success'
                ];
            } elseif ($score >= 80) {
                $recommendations[] = [
                    'type' => 'good',
                    'title' => 'Great Job!',
                    'description' => 'You have a strong understanding. Review weak areas and try related courses.',
                    'courses' => $this->getRelatedRecommendations($course),
                    'icon' => 'star',
                    'color' => 'primary'
                ];
            } else {
                $recommendations[] = [
                    'type' => 'passed',
                    'title' => 'Good Effort!',
                    'description' => 'You passed! Focus on areas where you scored lower to improve.',
                    'courses' => $this->getImprovementRecommendations($course, $result),
                    'icon' => 'check-circle',
                    'color' => 'info'
                ];
            }
        } else {
            if ($score >= 60) {
                $recommendations[] = [
                    'type' => 'close',
                    'title' => 'Almost There!',
                    'description' => 'You\'re close to passing. Review the material and try again.',
                    'courses' => $this->getFoundationRecommendations($course),
                    'icon' => 'refresh',
                    'color' => 'warning'
                ];
            } else {
                $recommendations[] = [
                    'type' => 'failed',
                    'title' => 'Keep Learning!',
                    'description' => 'Take more time with the course material. Consider foundational courses.',
                    'courses' => $this->getBeginnerRecommendations($course),
                    'icon' => 'book',
                    'color' => 'danger'
                ];
            }
        }

        // Add personalized learning path recommendations
        $recommendations[] = [
            'type' => 'learning_path',
            'title' => 'Your Learning Path',
            'description' => 'Based on your performance, here\'s a suggested learning path.',
            'path' => $this->generateLearningPath($user, $course, $result),
            'icon' => 'route',
            'color' => 'secondary'
        ];

        return $recommendations;
    }

    private function getAdvancedRecommendations(Course $course): array
    {
        // Get courses in the same category but higher level
        $category = $course->getCategory();
        if ($category === null) {
            return [];
        }
        return $this->entityManager->getRepository('App\Entity\Course')
            ->createQueryBuilder('c')
            ->where('c.category = :category')
            ->andWhere('c.level IN (:levels)')
            ->setParameter('category', $category)
            ->setParameter('levels', ['Advanced', 'Expert'])
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();
    }

    private function getRelatedRecommendations(Course $course): array
    {
        // Get related courses in the same category
        $category = $course->getCategory();
        if ($category === null) {
            return [];
        }
        $courseId = $course->getId();
        return $this->entityManager->getRepository('App\Entity\Course')
            ->createQueryBuilder('c')
            ->where('c.category = :category')
            ->andWhere('c.id != :currentId')
            ->setParameter('category', $category)
            ->setParameter('currentId', $courseId)
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();
    }

    private function getImprovementRecommendations(Course $course, CourseTestResult $result): array
    {
        // Analyze weak areas and suggest improvement courses
        $category = $course->getCategory();
        if ($category === null) {
            return [];
        }
        $level = $course->getLevel();
        return $this->entityManager->getRepository('App\Entity\Course')
            ->createQueryBuilder('c')
            ->where('c.category = :category')
            ->andWhere('c.level = :level')
            ->setParameter('category', $category)
            ->setParameter('level', $level ?? 'Beginner')
            ->setMaxResults(2)
            ->getQuery()
            ->getResult();
    }

    private function getFoundationRecommendations(Course $course): array
    {
        // Suggest foundational courses
        $category = $course->getCategory();
        if ($category === null) {
            return [];
        }
        return $this->entityManager->getRepository('App\Entity\Course')
            ->createQueryBuilder('c')
            ->where('c.category = :category')
            ->andWhere('c.level IN (:levels)')
            ->setParameter('category', $category)
            ->setParameter('levels', ['Beginner', 'Elementary'])
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();
    }

    private function getBeginnerRecommendations(Course $course): array
    {
        // Suggest beginner courses in the same category
        $category = $course->getCategory();
        if ($category === null) {
            return [];
        }
        return $this->entityManager->getRepository('App\Entity\Course')
            ->createQueryBuilder('c')
            ->where('c.category = :category')
            ->andWhere('c.level = :level')
            ->setParameter('category', $category)
            ->setParameter('level', 'Beginner')
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();
    }

    private function generateLearningPath(User $user, Course $course, CourseTestResult $result): array
    {
        $path = [];
        $score = $result->getPercentage();

        if ($score < 70) {
            $path[] = [
                'step' => 1,
                'title' => 'Review Course Material',
                'description' => 'Spend more time with the course content',
                'status' => 'recommended'
            ];
            $path[] = [
                'step' => 2,
                'title' => 'Take Practice Quiz',
                'description' => 'Test your knowledge with practice questions',
                'status' => 'locked'
            ];
        } else {
            $path[] = [
                'step' => 1,
                'title' => 'Explore Advanced Topics',
                'description' => 'Challenge yourself with more complex material',
                'status' => 'recommended'
            ];
            $path[] = [
                'step' => 2,
                'title' => 'Apply Your Skills',
                'description' => 'Work on real-world projects',
                'status' => 'locked'
            ];
        }

        return $path;
    }
}
