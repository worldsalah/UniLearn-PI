<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Form\NotificationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/notification')]
class NotificationController extends AbstractController
{
    #[Route('/', name: 'app_notification', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $notification = new Notification();
        $form = $this->createForm(NotificationType::class, $notification);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($notification);
            $entityManager->flush();

            $this->addFlash('success', 'Merci! Nous vous notifierons dès notre lancement.');

            return $this->redirectToRoute('app_notification', [], Response::HTTP_SEE_OTHER);
        }

        // Si le formulaire est soumis mais invalide, afficher les erreurs
        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Veuillez corriger les erreurs dans le formulaire.');
        }

        return $this->render('utility/coming-soon.html.twig', [
            'notificationForm' => $form->createView(),
        ]);
    }
}
