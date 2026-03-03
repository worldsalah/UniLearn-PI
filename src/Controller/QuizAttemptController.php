<?php

namespace App\Controller;

use App\Entity\Quiz;
use App\Entity\Question;
use App\Entity\QuizResult;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;

#[Route('/quiz')]
class QuizAttemptController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private Security $security;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    #[Route('/{id}/take', name: 'quiz_take', methods: ['GET'])]
    public function takeQuiz(int $id): Response
    {
        $user = $this->security->getUser();
        if ($user === null) {
            return $this->redirectToRoute('app_login');
        }

        $quiz = $this->entityManager->getRepository(Quiz::class)->find($id);
        if ($quiz === null) {
            throw $this->createNotFoundException('Quiz not found');
        }

        // Get questions for this quiz
        $questions = $this->entityManager->getRepository(Question::class)->findBy(['quiz' => $quiz]);
        
        if (empty($questions)) {
            $this->addFlash('warning', 'This quiz has no questions yet.');
            return $this->redirectToRoute('app_student_quizzes');
        }

        // Shuffle questions if needed
        shuffle($questions);

        return $this->render('quiz/take.html.twig', [
            'quiz' => $quiz,
            'questions' => $questions,
            'totalQuestions' => count($questions)
        ]);
    }

    #[Route('/{id}/questions', name: 'quiz_get_questions', methods: ['GET'])]
    public function getQuizQuestions(int $id): JsonResponse
    {
        $user = $this->security->getUser();
        if ($user === null) {
            return new JsonResponse(['error' => 'User not authenticated'], 401);
        }

        $quiz = $this->entityManager->getRepository(Quiz::class)->find($id);
        if ($quiz === null) {
            return new JsonResponse(['error' => 'Quiz not found'], 404);
        }

        $questions = $this->entityManager->getRepository(Question::class)->findBy(['quiz' => $quiz]);
        
        $questionData = [];
        foreach ($questions as $question) {
            $questionData[] = [
                'id' => $question->getId(),
                'question' => $question->getQuestion(),
                'option_a' => $question->getOptionA(),
                'option_b' => $question->getOptionB(),
                'option_c' => $question->getOptionC(),
                'option_d' => $question->getOptionD(),
                'correct_option' => $question->getCorrectOption()
            ];
        }

        // Shuffle questions for randomness
        shuffle($questionData);

        return new JsonResponse([
            'success' => true,
            'quiz' => [
                'id' => $quiz->getId(),
                'title' => $quiz->getTitle(),
                'description' => $quiz->getDescription()
            ],
            'questions' => $questionData,
            'totalQuestions' => count($questionData)
        ]);
    }

    #[Route('/{id}/submit', name: 'quiz_submit', methods: ['POST'])]
    public function submitQuiz(int $id, Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if ($user === null) {
            return new JsonResponse(['error' => 'User not authenticated'], 401);
        }

        $quiz = $this->entityManager->getRepository(Quiz::class)->find($id);
        if ($quiz === null) {
            return new JsonResponse(['error' => 'Quiz not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $answers = $data['answers'] ?? [];

        if (empty($answers)) {
            return new JsonResponse(['error' => 'No answers provided'], 400);
        }

        // Get questions and calculate score
        $questions = $this->entityManager->getRepository(Question::class)->findBy(['quiz' => $quiz]);
        $totalQuestions = count($questions);
        $correctAnswers = 0;
        $results = [];

        foreach ($questions as $question) {
            $questionId = $question->getId();
            $userAnswer = $answers[$questionId] ?? null;
            $correctAnswer = $question->getCorrectOption();
            
            $isCorrect = $userAnswer === $correctAnswer;
            if ($isCorrect) {
                $correctAnswers++;
            }

            $results[] = [
                'question_id' => $questionId,
                'question' => $question->getQuestion(),
                'user_answer' => $userAnswer,
                'correct_answer' => $correctAnswer,
                'is_correct' => $isCorrect,
                'options' => [
                    'a' => $question->getOptionA(),
                    'b' => $question->getOptionB(),
                    'c' => $question->getOptionC(),
                    'd' => $question->getOptionD()
                ]
            ];
        }

        // Calculate percentage
        $percentage = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100, 2) : 0;

        // Save quiz result
        $quizResult = new QuizResult();
        if ($user instanceof \App\Entity\User) {
            $quizResult->setUser($user);
        }
        $quizResult->setQuiz($quiz);
        $quizResult->setScore((int) $percentage);
        $quizResult->setCorrectAnswers($correctAnswers);
        $quizResult->setTotalQuestions($totalQuestions);
        $quizResult->setTakenAt(new \DateTimeImmutable());
        $quizResult->setAnswers(json_encode($results));

        $this->entityManager->persist($quizResult);
        $this->entityManager->flush();

        $takenAt = $quizResult->getTakenAt();

        return new JsonResponse([
            'success' => true,
            'result' => [
                'result_id' => $quizResult->getId(),
                'quiz_id' => $quiz->getId(),
                'quiz_title' => $quiz->getTitle(),
                'score' => $percentage,
                'correct_answers' => $correctAnswers,
                'total_questions' => $totalQuestions,
                'passed' => $percentage >= 70, // Assuming 70% is passing
                'results' => $results,
                'taken_at' => $takenAt !== null ? $takenAt->format('Y-m-d H:i:s') : null
            ]
        ]);
    }

    #[Route('/result/{resultId}', name: 'quiz_result', methods: ['GET'])]
    public function showResult(int $resultId): Response
    {
        $user = $this->security->getUser();
        if ($user === null) {
            return $this->redirectToRoute('app_login');
        }

        $quizResult = $this->entityManager->getRepository(QuizResult::class)->find($resultId);
        if ($quizResult === null || $quizResult->getUser() !== $user) {
            throw $this->createNotFoundException('Quiz result not found');
        }

        $results = json_decode($quizResult->getAnswers(), true) ?? [];

        return $this->render('quiz/result.html.twig', [
            'quizResult' => $quizResult,
            'quiz' => $quizResult->getQuiz(),
            'results' => $results,
            'passed' => $quizResult->getScore() >= 70
        ]);
    }

    #[Route('/my-results', name: 'quiz_my_results', methods: ['GET'])]
    public function myResults(): Response
    {
        $user = $this->security->getUser();
        if ($user === null) {
            return $this->redirectToRoute('app_login');
        }

        $quizResults = $this->entityManager->getRepository(QuizResult::class)
            ->findBy(['user' => $user], ['takenAt' => 'DESC']);

        return $this->render('quiz/my-results.html.twig', [
            'quizResults' => $quizResults
        ]);
    }
}
