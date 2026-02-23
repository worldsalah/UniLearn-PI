<?php

namespace App\Controller;

use App\Form\LoginType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CustomSecurityController extends AbstractController
{
    #[Route('/custom-login', name: 'app_custom_login')]
    public function customLogin(
        Request $request,
        AuthenticationUtils $authenticationUtils,
        ValidatorInterface $validator,
    ): Response {
        // Create login form for validation
        $loginForm = $this->createForm(LoginType::class);

        // Get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // Last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        // If form is submitted, validate it server-side
        if ($request->isMethod('POST')) {
            $loginForm->handleRequest($request);

            if ($loginForm->isSubmitted()) {
                $data = $loginForm->getData();

                // Validate the submitted data
                $email = $request->request->get('email');
                $password = $request->request->get('password');

                // Create a temporary form data array for validation
                $formData = ['email' => $email, 'password' => $password];
                $loginForm->submit($formData);

                if (!$loginForm->isValid()) {
                    // Get validation errors
                    $errors = [];
                    foreach ($loginForm->getErrors(true) as $error) {
                        $errors[] = $error->getMessage();
                    }

                    // Add flash message with validation errors
                    $this->addFlash('error', implode('<br>', $errors));

                    return $this->render('auth/sign-in.html.twig', [
                        'last_username' => $lastUsername,
                        'error' => $error,
                        'loginForm' => $loginForm->createView(),
                    ]);
                }
            }
        }

        return $this->render('auth/sign-in.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'loginForm' => $loginForm->createView(),
        ]);
    }

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // Last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('auth/sign-in.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // controller can be blank: it will be intercepted by the logout key on your firewall
        throw new \LogicException('This should never be reached!');
    }
}
