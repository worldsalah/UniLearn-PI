<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 1024)]
class QueryParameterFixListener
{
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        
        // Only process main requests, not sub-requests
        if (!$event->isMainRequest()) {
            return;
        }
        
        // Fix query parameters that might be strings but should be arrays
        $this->fixQueryParameter($request, 'categories');
        $this->fixQueryParameter($request, 'levels');
        $this->fixQueryParameter($request, 'languages');
    }
    
    private function fixQueryParameter(Request $request, string $paramName): void
    {
        $query = $request->query;
        
        // Check if parameter exists as a single value (not array)
        if ($query->has($paramName)) {
            $allValues = $query->all($paramName);
            
            // If it's not an array or is empty, try to get single value
            if (empty($allValues)) {
                $value = $query->get($paramName);
                
                // Convert single string value to array
                if (is_string($value) && $value !== '') {
                    // Remove the original parameter
                    $query->remove($paramName);
                    
                    // Add it as an array parameter by setting multiple values
                    $query->set($paramName, [$value]);
                } elseif ($value === '' || $value === null) {
                    // Remove empty parameter
                    $query->remove($paramName);
                }
            }
        }
    }
}
