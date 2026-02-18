<?php

namespace App\Controller;

use App\Entity\Role;
use App\Entity\User;
use App\Form\Form\StudentType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/freelancers')]
class StudentController extends AbstractController
{
    #[Route('/{id}', name: 'app_student_show', methods: ['GET'])]
    public function show(User $student): Response
    {
        return $this->render('student/show.html.twig', [
            'student' => $student,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_student_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $student, EntityManagerInterface $entityManager): Response
    {
        // Get the currently logged-in user
        $user = $this->getUser();

        if (!$user) {
            // If no user is logged in, redirect to login
            return $this->redirectToRoute('app_login');
        }

        if ($student !== $user) {
            throw $this->createAccessDeniedException('You can only edit your own freelancer profile.');
        }

        $form = $this->createForm(StudentType::class, $student);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Assigner le rôle "student" par défaut
            $studentRole = $entityManager->getRepository(Role::class)->findOneBy(['name' => 'student']);
            if ($studentRole) {
                $student->setRole($studentRole);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Profil mis à jour avec succès.');

            return $this->redirectToRoute('app_marketplace_dashboard');
        }

        // Si le formulaire est soumis mais invalide, afficher les erreurs
        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Veuillez corriger les erreurs dans le formulaire.');
        }

        return $this->render('student/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
