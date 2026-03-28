<?php

namespace App\EventSubscriber;

use App\Service\ActivityLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class AuthenticationSubscriber implements EventSubscriberInterface
{
    private ActivityLogger $activityLogger;

    public function __construct(ActivityLogger $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            InteractiveLoginEvent::class => 'onLogin',
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();
        if ($user) {
            $this->activityLogger->log(
                'User Login',
                'User "' . $user->getUserIdentifier() . '" logged in successfully'
            );
        }
    }

    public function onLogout(LogoutEvent $event): void
    {
        $token = $event->getToken();
        if ($token && $token->getUser()) {
            $user = $token->getUser();
            $this->activityLogger->log(
                'User Logout',
                'User "' . $user->getUserIdentifier() . '" logged out'
            );
        }
    }
}
