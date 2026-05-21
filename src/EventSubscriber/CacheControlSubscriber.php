<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Bundle\SecurityBundle\Security;

class CacheControlSubscriber implements EventSubscriberInterface
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        // Check if this is an admin or staff path
        $isAdminPath = strpos($request->getPathInfo(), '/admin') === 0;
        
        // If authenticated user is viewing admin pages, add no-cache headers
        if ($isAdminPath && $this->security->getUser()) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT');
        }
    }
}
