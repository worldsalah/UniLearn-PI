<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\CourseTest;
use App\Entity\CourseTestQuestion;
use App\Entity\CourseTestResult;
use App\Entity\Enrollment;
use App\Entity\Lesson;
use App\Repository\CourseTestRepository;
use App\Repository\EnrollmentRepository;
use App\Repository\LessonCompletionRepository;
use App\Repository\LessonRepository;
use App\Service\GamificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/placement-test')]
class PlacementTestController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private CourseTestRepository $testRepository;
    private EnrollmentRepository $enrollmentRepository;
    private LessonRepository $lessonRepository;
    private LessonCompletionRepository $lessonCompletionRepository;
    private GamificationService $gamificationService;

    public function __construct(
        EntityManagerInterface $entityManager,
        CourseTestRepository $testRepository,
        EnrollmentRepository $enrollmentRepository,
        LessonRepository $lessonRepository,
        LessonCompletionRepository $lessonCompletionRepository,
        GamificationService $gamificationService
    ) {
        $this->entityManager = $entityManager;
        $this->testRepository = $testRepository;
        $this->enrollmentRepository = $enrollmentRepository;
        $this->lessonRepository = $lessonRepository;
        $this->lessonCompletionRepository = $lessonCompletionRepository;
        $this->gamificationService = $gamificationService;
    }

    #[Route('/course/{id}', name: 'app_placement_test_start', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function startTest(Course $course): Response
    {
        $user = $this->getUser();
        
        // Check if user is the instructor/creator of this course
        $courseUser = $course->getUser();
        $isInstructor = $courseUser !== null && $user !== null && $courseUser->getId() === $user->getId();
        $hasInstructorRole = $user !== null && in_array('ROLE_INSTRUCTOR', $user->getRoles(), true);
        
        // Instructors can take placement tests for their own courses without enrollment
        if ($isInstructor && $hasInstructorRole) {
            $quiz = $course->getPlacementQuiz();
            if ($quiz === null) {
                $test = $course->getPlacementTest();
                if ($test === null) {
                    $test = $this->createDefaultPlacementTest($course);
                }
            } else {
                $test = $quiz;
            }

            return $this->render('placement_test/start.html.twig', [
                'course' => $course,
                'test' => $test,
                'enrollment' => null,
                'isInstructor' => true
            ]);
        }
        
        // Check if user is enrolled (for students)
        $enrollment = $this->enrollmentRepository->findOneBy(['user' => $user, 'course' => $course]);
        
        if ($enrollment === null) {
            $this->addFlash('error', 'You must be enrolled in this course to take the placement test.');
            return $this->redirectToRoute('app_course_show', ['id' => $course->getId()]);
        }

        // Check if placement test was already taken
        if ($enrollment->isPlacementTestTaken()) {
            $this->addFlash('info', 'You have already taken the placement test for this course.');
            
            // Redirect to the appropriate lesson
            $startingLesson = $enrollment->getStartingLesson();
            if ($startingLesson !== null) {
                return $this->redirectToRoute('app_lesson_show', ['id' => $startingLesson->getId()]);
            }
            return $this->redirectToRoute('app_course_dashboard', ['id' => $course->getId()]);
        }

        // Get placement quiz first (new system), fallback to CourseTest (old system)
        $quiz = $course->getPlacementQuiz();
        $test = null;
        
        if ($quiz !== null) {
            $test = $quiz;
        } else {
            // Fallback to old CourseTest system
            $test = $course->getPlacementTest();
            if ($test === null) {
                $test = $this->createDefaultPlacementTest($course);
            }
        }

        return $this->render('placement_test/start.html.twig', [
            'course' => $course,
            'test' => $test,
            'enrollment' => $enrollment
        ]);
    }

    #[Route('/course/{id}/take', name: 'app_placement_test_take', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function takeTest(Course $course, Request $request): Response
    {
        $user = $this->getUser();
        
        // Check if user is the instructor/creator of this course
        $courseUser = $course->getUser();
        $isInstructor = $courseUser !== null && $user !== null && $courseUser->getId() === $user->getId();
        $hasInstructorRole = $user !== null && in_array('ROLE_INSTRUCTOR', $user->getRoles(), true);
        
        // Instructors can take placement tests for their own courses without enrollment
        if ($isInstructor && $hasInstructorRole) {
            $test = $course->getPlacementTest();
            if ($test === null) {
                $test = $this->createDefaultPlacementTest($course);
            }

            if ($request->isMethod('POST')) {
                $answers = $request->request->all('answers');
                $score = 0;
                $totalQuestions = count($test->getQuestions());

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

                $result = new CourseTestResult();
                if ($user instanceof \App\Entity\User) {
                    $result->setUser($user);
                }
                $result->setCourseTest($test);
                $result->setScore($score);
                $result->setTotalQuestions($totalQuestions);
                $result->setPercentage($percentage);
                $result->setPassed($percentage >= 70);
                $result->setTimeTaken(new \DateTimeImmutable());
                $result->setAnswers($answers);

                $this->entityManager->persist($result);
                $this->entityManager->flush();

                return $this->redirectToRoute('app_placement_test_result', ['id' => $course->getId()]);
            }

            return $this->render('placement_test/take.html.twig', [
                'course' => $course,
                'test' => $test,
                'isInstructor' => true
            ]);
        }
        
        $enrollment = $this->enrollmentRepository->findOneBy(['user' => $user, 'course' => $course]);
        
        if ($enrollment === null) {
            return $this->redirectToRoute('app_course_show', ['id' => $course->getId()]);
        }

        if ($enrollment->isPlacementTestTaken()) {
            return $this->redirectToRoute('app_placement_test_start', ['id' => $course->getId()]);
        }

        $test = $course->getPlacementTest();
        if ($test === null) {
            $test = $this->createDefaultPlacementTest($course);
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

            // Save test result
            $result = new CourseTestResult();
            if ($user instanceof \App\Entity\User) {
                $result->setUser($user);
            }
            $result->setCourseTest($test);
            $result->setScore($score);
            $result->setTotalQuestions($totalQuestions);
            $result->setPercentage($percentage);
            $result->setPassed($percentage >= 70);
            $result->setTimeTaken(new \DateTimeImmutable());
            $result->setAnswers($answers);

            $this->entityManager->persist($result);

            // Determine starting point based on score
            $this->setStartingPointBasedOnScore($enrollment, $course, $percentage, $result);

            $this->entityManager->flush();

            return $this->redirectToRoute('app_placement_test_result', ['id' => $course->getId()]);
        }

        return $this->render('placement_test/take.html.twig', [
            'course' => $course,
            'test' => $test
        ]);
    }

    #[Route('/course/{id}/result', name: 'app_placement_test_result', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function testResult(Course $course): Response
    {
        $user = $this->getUser();
        
        $enrollment = $this->enrollmentRepository->findOneBy(['user' => $user, 'course' => $course]);
        
        if ($enrollment === null || !$enrollment->isPlacementTestTaken()) {
            return $this->redirectToRoute('app_placement_test_start', ['id' => $course->getId()]);
        }

        $test = $course->getPlacementTest();
        $result = $enrollment->getPlacementTestResult();

        return $this->render('placement_test/result.html.twig', [
            'course' => $course,
            'test' => $test,
            'result' => $result,
            'enrollment' => $enrollment
        ]);
    }

    #[Route('/course/{id}/skip', name: 'app_placement_test_skip', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function skipTest(Course $course): Response
    {
        $user = $this->getUser();
        
        $enrollment = $this->enrollmentRepository->findOneBy(['user' => $user, 'course' => $course]);
        
        if ($enrollment === null) {
            return $this->redirectToRoute('app_course_show', ['id' => $course->getId()]);
        }

        if ($enrollment->isPlacementTestTaken()) {
            return $this->redirectToRoute('app_placement_test_start', ['id' => $course->getId()]);
        }

        // Set starting point to first lesson
        $firstLesson = $this->lessonRepository->findFirstLessonByCourse($course);
        $enrollment->setPlacementTestTaken(true);
        $enrollment->setStartingLesson($firstLesson);

        $this->entityManager->flush();

        $this->addFlash('info', 'Placement test skipped. Starting from the beginning.');

        if ($firstLesson !== null) {
            return $this->redirectToRoute('app_lesson_show', ['id' => $firstLesson->getId()]);
        }

        return $this->redirectToRoute('app_course_dashboard', ['id' => $course->getId()]);
    }

    private function createDefaultPlacementTest(Course $course): CourseTest
    {
        $test = new CourseTest();
        $test->setCourse($course);
        $test->setTitle('Placement Test - ' . $course->getTitle());
        $test->setDescription('This placement test helps determine your starting point in this course.');
        $test->setTimeLimit(30); // 30 minutes
        $test->setPassingScore(70);
        $test->setTestType('placement');

        // Create sample questions based on course content
        $lessons = $this->lessonRepository->findByCourse($course);
        $questionCount = 0;
        
        foreach ($lessons as $lesson) {
            if ($questionCount >= 10) break; // Max 10 questions
            
            // Create a question based on lesson content
            $question = new CourseTestQuestion();
            $question->setCourseTest($test);
            $question->setQuestion('What is the main topic of the lesson: "' . $lesson->getTitle() . '"?');
            $question->setOptions([
                'A' => 'Option A - ' . substr($lesson->getDescription() ?? $lesson->getTitle(), 0, 50),
                'B' => 'Option B - Related concept',
                'C' => 'Option C - Another concept',
                'D' => 'Option D - Different concept'
            ]);
            $question->setCorrectAnswer('A');
            $question->setDifficulty($lesson->getDifficulty() ?? 'medium');
            $question->setPoints(1);
            
            $test->addQuestion($question);
            $questionCount++;
        }

        // If no lessons, create generic questions
        if ($questionCount === 0) {
            $this->addGenericQuestions($test);
        }

        $this->entityManager->persist($test);
        $this->entityManager->flush();

        return $test;
    }

    private function addGenericQuestions(CourseTest $test): void
    {
        $questions = [
            [
                'question' => 'What is the primary focus of this course?',
                'options' => ['A' => 'Basic concepts', 'B' => 'Advanced topics', 'C' => 'Practical applications', 'D' => 'All of the above'],
                'correct' => 'D',
                'difficulty' => 'easy'
            ],
            [
                'question' => 'Are you familiar with the prerequisites for this course?',
                'options' => ['A' => 'Yes, completely', 'B' => 'Somewhat', 'C' => 'A little', 'D' => 'No, not at all'],
                'correct' => 'A',
                'difficulty' => 'easy'
            ],
            [
                'question' => 'How comfortable are you with the course subject matter?',
                'options' => ['A' => 'Very comfortable', 'B' => 'Moderately comfortable', 'C' => 'Somewhat comfortable', 'D' => 'Not comfortable'],
                'correct' => 'A',
                'difficulty' => 'easy'
            ]
        ];

        foreach ($questions as $q) {
            $question = new CourseTestQuestion();
            $question->setCourseTest($test);
            $question->setQuestion($q['question']);
            $question->setOptions($q['options']);
            $question->setCorrectAnswer($q['correct']);
            $question->setDifficulty($q['difficulty']);
            $question->setPoints(1);
            $test->addQuestion($question);
        }
    }

    private function setStartingPointBasedOnScore(Enrollment $enrollment, Course $course, float $percentage, CourseTestResult $result): void
    {
        $enrollment->setPlacementTestTaken(true);
        $enrollment->setPlacementTestResult($result);

        $lessons = $this->lessonRepository->findByCourse($course);
        $totalLessons = count($lessons);

        if ($percentage >= 100) {
            // Student knows everything - mark course as complete
            $enrollment->setProgress(100);
            $enrollment->setStatus('completed');
            $enrollment->setCompletedAt(new \DateTimeImmutable());
            
            // Mark all lessons as completed
            $user = $enrollment->getUser();
            foreach ($lessons as $lesson) {
                $existingCompletion = $this->lessonCompletionRepository->findOneBy(['lesson' => $lesson, 'user' => $user]);
                if ($existingCompletion === null) {
                    $completion = new \App\Entity\LessonCompletion();
                    if ($user instanceof \App\Entity\User) {
                        $completion->setUser($user);
                    }
                    $completion->setLesson($lesson);
                    $completion->setCourse($course);
                    $completion->setCompletedAt(new \DateTimeImmutable());
                    $this->entityManager->persist($completion);
                }
            }

            // Award XP for course completion
            if ($user instanceof \App\Entity\User) {
                $this->gamificationService->awardCourseCompletionXP($user, $course->getId() ?? 0, $course->getTitle() ?? '');
            }
            
        } elseif ($percentage >= 70) {
            // Student knows a lot - start from middle
            $middleIndex = (int) floor($totalLessons / 2);
            $startingLesson = $lessons[$middleIndex] ?? $lessons[0] ?? null;
            $enrollment->setStartingLesson($startingLesson);
            
            // Mark first half of lessons as completed
            $user = $enrollment->getUser();
            for ($i = 0; $i < $middleIndex && $i < $totalLessons; $i++) {
                $lesson = $lessons[$i];
                $existingCompletion = $this->lessonCompletionRepository->findOneBy(['lesson' => $lesson, 'user' => $user]);
                if ($existingCompletion === null) {
                    $completion = new \App\Entity\LessonCompletion();
                    if ($user instanceof \App\Entity\User) {
                        $completion->setUser($user);
                    }
                    $completion->setLesson($lesson);
                    $completion->setCourse($course);
                    $completion->setCompletedAt(new \DateTimeImmutable());
                    $this->entityManager->persist($completion);
                }
            }
            
            // Update progress
            $completedCount = $middleIndex;
            $progress = $totalLessons > 0 ? ($completedCount / $totalLessons) * 100 : 0;
            $enrollment->setProgress($progress);
            
        } else {
            // Student should start from beginning
            $firstLesson = $lessons[0] ?? null;
            $enrollment->setStartingLesson($firstLesson);
            $enrollment->setProgress(0);
        }
    }
}
