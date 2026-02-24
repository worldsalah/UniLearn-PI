<?php

namespace App\Controller;

use App\Entity\QuizResult;
use App\Repository\QuizResultRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/verification')]
class QuizVerificationController extends AbstractController
{
    public function __construct(
        private QuizResultRepository $quizResultRepository
    ) {}

    #[Route('/quiz/{resultId}/{token}', name: 'quiz_verification')]
    public function verifyQuizResult(int $resultId, string $token): Response
    {
        $quizResult = $this->quizResultRepository->find($resultId);
        
        if (!$quizResult) {
            throw new NotFoundHttpException('Résultat de quiz non trouvé');
        }
        
        // Verify token
        $expectedToken = md5($quizResult->getId() . $quizResult->getCreatedAt()->format('Y-m-d H:i:s'));
        
        if ($token !== $expectedToken) {
            throw new NotFoundHttpException('Token de vérification invalide');
        }
        
        return $this->render('verification/quiz_result.html.twig', [
            'quizResult' => $quizResult,
            'user' => $quizResult->getUser(),
            'quiz' => $quizResult->getQuiz(),
            'verificationDate' => new \DateTime(),
            'isValid' => true
        ]);
    }
}
