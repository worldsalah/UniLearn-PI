<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Quiz;
use App\Entity\Question;
use App\Entity\QuizResult;
use App\Entity\QuizAttempt;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CourseQuizController extends AbstractController
{
    #[Route('/course/{id}/quiz', name: 'app_course_quiz', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_STUDENT')]
    public function courseQuiz(Course $course, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        // Vérifier si l'utilisateur est connecté
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Chercher le quiz final pour ce cours
        $finalQuiz = $entityManager->getRepository(Quiz::class)->findOneBy([
            'course' => $course,
            'title' => 'Quiz Final - ' . $course->getTitle()
        ]);

        // Si aucun quiz n'existe, en créer un automatiquement
        if (!$finalQuiz) {
            $finalQuiz = $this->createFinalQuiz($course, $entityManager);
        }

        // Vérifier si l'utilisateur a déjà passé ce quiz
        $existingAttempt = $entityManager->getRepository(QuizAttempt::class)->findOneBy([
            'quiz' => $finalQuiz,
            'user' => $user
        ]);

        return $this->render('course/quiz.html.twig', [
            'course' => $course,
            'quiz' => $finalQuiz,
            'existingAttempt' => $existingAttempt,
            'questions' => $finalQuiz->getQuestions()->toArray()
        ]);
    }

    #[Route('/course/{id}/quiz/submit', name: 'app_course_quiz_submit', methods: ['POST'])]
    #[IsGranted('ROLE_STUDENT')]
    public function submitQuiz(Course $course, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], 401);
        }

        // Récupérer le quiz final
        $finalQuiz = $entityManager->getRepository(Quiz::class)->findOneBy([
            'course' => $course,
            'title' => 'Quiz Final - ' . $course->getTitle()
        ]);

        if (!$finalQuiz) {
            return new JsonResponse(['error' => 'Quiz not found'], 404);
        }

        // Vérifier si l'utilisateur a déjà passé ce quiz
        $existingAttempt = $entityManager->getRepository(QuizAttempt::class)->findOneBy([
            'quiz' => $finalQuiz,
            'user' => $user
        ]);

        if ($existingAttempt) {
            return new JsonResponse(['error' => 'You have already taken this quiz'], 400);
        }

        // Récupérer les réponses
        $answers = $request->request->all();
        $score = 0;
        $totalQuestions = $finalQuiz->getQuestions()->count();

        // Calculer le score
        foreach ($finalQuiz->getQuestions() as $question) {
            $questionId = $question->getId();
            if (isset($answers['question_' . $questionId])) {
                $userAnswer = $answers['question_' . $questionId];
                if ($userAnswer === $question->getCorrectOption()) {
                    $score++;
                }
            }
        }

        // Calculer le pourcentage
        $percentage = $totalQuestions > 0 ? ($score / $totalQuestions) * 100 : 0;

        // Créer une tentative de quiz
        $attempt = new QuizAttempt();
        $attempt->setQuiz($finalQuiz);
        $attempt->setUser($user);
        $attempt->setScore($percentage);
        $attempt->setStartedAt(new \DateTimeImmutable());
        $attempt->setCompletedAt(new \DateTimeImmutable());
        $attempt->setStatus('completed');

        // Créer un résultat
        $result = new QuizResult();
        $result->setQuiz($finalQuiz);
        $result->setUser($user);
        $result->setScore($percentage);
        $result->setTotalQuestions($totalQuestions);
        $result->setCorrectAnswers($score);
        $result->setAttempt($attempt);
        $result->setCompletedAt(new \DateTimeImmutable());

        $entityManager->persist($attempt);
        $entityManager->persist($result);
        $entityManager->flush();

        // Déterminer le niveau de l'utilisateur
        $level = $this->determineLevel($percentage);

        return new JsonResponse([
            'success' => true,
            'score' => $score,
            'total' => $totalQuestions,
            'percentage' => round($percentage, 2),
            'level' => $level,
            'message' => $this->getResultMessage($percentage)
        ]);
    }

    private function createFinalQuiz(Course $course, EntityManagerInterface $entityManager): Quiz
    {
        $quiz = new Quiz();
        $quiz->setTitle('Quiz Final - ' . $course->getTitle());
        $quiz->setCourse($course);
        $quiz->setDuration(30); // 30 minutes
        $quiz->setCreatedAt(new \DateTimeImmutable());

        // Créer des questions automatiquement basées sur le contenu du cours
        $this->generateQuizQuestions($quiz, $course);

        $entityManager->persist($quiz);
        $entityManager->flush();

        return $quiz;
    }

    private function generateQuizQuestions(Quiz $quiz, Course $course): void
    {
        $questions = [
            [
                'question' => 'Quel est l\'objectif principal de ce cours "' . $course->getTitle() . '" ?',
                'optionA' => 'Apprendre les bases fondamentales',
                'optionB' => 'Devenir un expert avancé',
                'optionC' => 'Explorer de nouveaux concepts',
                'optionD' => 'Pratiquer uniquement',
                'correctOption' => 'A'
            ],
            [
                'question' => 'Quel niveau de compétence est nécessaire pour ce cours ?',
                'optionA' => 'Débutant absolu',
                'optionB' => $course->getLevel() ?? 'Intermédiaire',
                'optionC' => 'Avancé',
                'optionD' => 'Expert',
                'correctOption' => 'B'
            ],
            [
                'question' => 'Combien de temps faut-il prévoir pour maîtriser le contenu de ce cours ?',
                'optionA' => 'Quelques heures',
                'optionB' => 'Une journée',
                'optionC' => ($course->getDuration() ?? 'Plusieurs jours') . ' de pratique',
                'optionD' => 'Plusieurs semaines',
                'correctOption' => 'C'
            ],
            [
                'question' => 'Quelle compétence principale allez-vous acquérir ?',
                'optionA' => 'Théorie uniquement',
                'optionB' => 'Pratique et application',
                'optionC' => 'Certification',
                'optionD' => 'Réseautage',
                'correctOption' => 'B'
            ],
            [
                'question' => 'Êtes-vous prêt(e) à passer le quiz final ?',
                'optionA' => 'Oui, je suis confiant(e)',
                'optionB' => 'J\'ai besoin de réviser',
                'optionC' => 'Je ne suis pas sûr(e)',
                'optionD' => 'Je préfère sauter',
                'correctOption' => 'A'
            ]
        ];

        foreach ($questions as $index => $qData) {
            $question = new Question();
            $question->setQuestion($qData['question']);
            $question->setOptionA($qData['optionA']);
            $question->setOptionB($qData['optionB']);
            $question->setOptionC($qData['optionC']);
            $question->setOptionD($qData['optionD']);
            $question->setCorrectOption($qData['correctOption']);
            $question->setQuiz($quiz);

            $quiz->addQuestion($question);
        }
    }

    private function determineLevel(float $percentage): string
    {
        if ($percentage >= 90) {
            return 'Expert';
        } elseif ($percentage >= 75) {
            return 'Avancé';
        } elseif ($percentage >= 60) {
            return 'Intermédiaire';
        } elseif ($percentage >= 40) {
            return 'Débutant';
        } else {
            return 'Novice';
        }
    }

    private function getResultMessage(float $percentage): string
    {
        if ($percentage >= 90) {
            return 'Excellent ! Vous maîtrisez parfaitement le contenu du cours !';
        } elseif ($percentage >= 75) {
            return 'Très bien ! Vous avez une excellente compréhension du cours.';
        } elseif ($percentage >= 60) {
            return 'Bien ! Vous avez compris les concepts principaux du cours.';
        } elseif ($percentage >= 40) {
            return 'Passable. Une révision supplémentaire pourrait être bénéfique.';
        } else {
            return 'Nous vous recommandons de revoir le cours plus attentivement.';
        }
    }
}
