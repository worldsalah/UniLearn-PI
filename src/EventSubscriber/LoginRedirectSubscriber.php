<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class LoginRedirectSubscriber implements EventSubscriberInterface
{
    private RouterInterface $router;
    private TokenStorageInterface $tokenStorage;

    public function __construct(RouterInterface $router, TokenStorageInterface $tokenStorage)
    {
        $this->router = $router;
        $this->tokenStorage = $tokenStorage;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 0],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        // List of routes that authenticated users should not access
        $restrictedRoutes = ['app_login', 'app_register'];

        // Check if user is authenticated
        $token = $this->tokenStorage->getToken();
        $isAuthenticated = $token && $token->getUser() && is_object($token->getUser());

        // If user is authenticated and trying to access login/register, redirect
        if ($isAuthenticated && in_array($route, $restrictedRoutes)) {
            $url = $this->router->generate('app_welcome');
            $event->setResponse(new RedirectResponse($url));
        }
    }
}
