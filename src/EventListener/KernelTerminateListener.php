<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::TERMINATE)]
class KernelTerminateListener
{
    public function onKernelTerminate(TerminateEvent $event): void
    {
        // This listener is called after the response has been sent to the client
        // You can add cleanup logic, logging, or other post-response tasks here

        // Example: Log request completion
        $request = $event->getRequest();
        $response = $event->getResponse();

        // Add your custom logic here
        // For now, this is a placeholder to satisfy service autowiring
    }
}
