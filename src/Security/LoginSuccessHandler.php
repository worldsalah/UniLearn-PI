<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    private RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        // Get the user
        $user = $token->getUser();

        // Check user role and redirect accordingly
        $roles = $user->getRoles();

        if (in_array('ROLE_ADMIN', $roles)) {
            $redirectUrl = $this->router->generate('app_dashboard');
        } elseif (in_array('ROLE_INSTRUCTOR', $roles)) {
            $redirectUrl = $this->router->generate('app_welcome');
        } else {
            $redirectUrl = $this->router->generate('app_welcome');
        }

        return new RedirectResponse($redirectUrl);
    }
}
