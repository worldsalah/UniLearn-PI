<?php

namespace App\Controller;

use App\Service\GoogleAuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class GoogleController extends AbstractController
{
    private GoogleAuthService $googleAuthService;
    private TokenStorageInterface $tokenStorage;

    public function __construct(
        GoogleAuthService $googleAuthService,
        TokenStorageInterface $tokenStorage
    ) {
        $this->googleAuthService = $googleAuthService;
        $this->tokenStorage = $tokenStorage;
    }

    #[Route('/connect/google', name: 'connect_google')]
    public function connect(): Response
    {
        return $this->redirect(
            $this->googleAuthService->getAuthorizationUrl()
        );
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function check(Request $request): Response
    {
        try {
            $user = $this->googleAuthService->authenticateUser($request);

            if (!$user) {
                $this->addFlash('error', 'Google authentication failed.');
                return $this->redirectToRoute('app_login');
            }

            // 🔐 Authenticate user in Symfony
            $token = new UsernamePasswordToken(
                $user,
                'main',
                $user->getRoles()
            );

            $this->tokenStorage->setToken($token);
            $request->getSession()->set('_security_main', serialize($token));

            $this->addFlash('success', 'Logged in with Google successfully');

            return $this->redirectToRoute('app_welcome');

        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('app_login');
        }
    }
}