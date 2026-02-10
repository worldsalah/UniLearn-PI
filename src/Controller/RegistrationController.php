<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationType;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    #[Route('/sign-up', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, RoleRepository $roleRepository): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('password')->getData()
                )
            );

            // Assign a default role
            $defaultRole = $roleRepository->findOneBy(['name' => 'USER']);
            if (!$defaultRole) {
                // It's better to throw an exception in a dev environment if a core part of the system is missing.
                throw new \Exception("Default role 'USER' not found. Please add it to the 'role' table.");
            }
            $user->setRole($defaultRole);

            $entityManager->persist($user);
            $entityManager->flush();

            // do anything else you need here, like send an email
            $this->addFlash('success', 'You have successfully registered! Please sign in.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('auth/sign-up.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}