<?php

namespace App\Controller;

use App\Entity\Quiz;
use App\Entity\QuizSettings;
use App\Form\QuizSettingsType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/instructor/quiz')]
class QuizSettingsController extends AbstractController
{
    #[Route('/{id}/settings', name: 'app_quiz_settings', methods: ['GET', 'POST'])]
    public function settings(Quiz $quiz, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Check if quiz settings already exist
        $quizSettings = $entityManager->getRepository(QuizSettings::class)->findOneBy(['quiz' => $quiz]);

        if (!$quizSettings) {
            $quizSettings = new QuizSettings();
            $quizSettings->setQuiz($quiz);
        }

        $form = $this->createForm(QuizSettingsType::class, $quizSettings);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($quizSettings);
            $entityManager->flush();

            $this->addFlash('success', 'Les paramètres du quiz ont été mis à jour avec succès.');

            return $this->redirectToRoute('app_quiz_settings', ['id' => $quiz->getId()], Response::HTTP_SEE_OTHER);
        }

        // Si le formulaire est soumis mais invalide, afficher les erreurs
        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Veuillez corriger les erreurs dans le formulaire.');
        }

        return $this->render('instructor/quiz-settings.html.twig', [
            'quiz' => $quiz,
            'quizSettingsForm' => $form->createView(),
        ]);
    }
}
