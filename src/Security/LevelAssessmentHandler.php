<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class LevelAssessmentHandler implements AuthenticationSuccessHandlerInterface
{
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $user = $token->getUser();
        
        // Check if user is a student
        if ($user instanceof UserInterface && in_array('ROLE_STUDENT', $user->getRoles())) {
            // Redirect to home with logged_in parameter to trigger modal
            $url = $this->urlGenerator->generate('app_home', ['logged_in' => 'true']);
            return new RedirectResponse($url);
        }
        
        // For other users, redirect to default home
        $url = $this->urlGenerator->generate('app_home');
        return new RedirectResponse($url);
    }
}
