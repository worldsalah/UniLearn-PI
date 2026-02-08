<?php

namespace App\Controller;

use App\Entity\Role;
use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // If user is already authenticated, redirect to welcome page
        if ($this->getUser()) {
            return $this->redirectToRoute('app_welcome');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('auth/sign-in.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        // If user is already authenticated, redirect to welcome page
        if ($this->getUser()) {
            return $this->redirectToRoute('app_welcome');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash the password
            $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    $form->get('password')->getData()
                )
            );

            // Set default role (e.g., role with ID 3 for 'user')
            $defaultRole = $entityManager->getRepository(Role::class)->find(3);
            if ($defaultRole) {
                $user->setRole($defaultRole);
            } else {
                // Handle case where default role is not found
                $this->addFlash('error', 'Default user role not configured.');
                return $this->render('auth/sign-up.html.twig', [
                    'registrationForm' => $form->createView(),
                ]);
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Your account has been created successfully! Please log in.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('auth/sign-up.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // This method can be blank - it will be intercepted by the logout key on your firewall
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}