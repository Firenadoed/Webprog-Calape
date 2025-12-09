<?php

namespace App\EventSubscriber;

use App\Service\ActivityLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LoginLogoutSubscriber implements EventSubscriberInterface
{
    private $activityLogger;
    
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
        $this->activityLogger->log($user, 'LOGIN');
    }
    
    public function onLogout(LogoutEvent $event): void
    {
        $user = $event->getToken()->getUser();
        $this->activityLogger->log($user, 'LOGOUT');
    }
}