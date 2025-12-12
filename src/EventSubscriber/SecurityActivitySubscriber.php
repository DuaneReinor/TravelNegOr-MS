<?php

namespace App\EventSubscriber;

use App\Entity\ActivityLog;
use App\Entity\User;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Logs security-related events like user login/logout
 */
class SecurityActivitySubscriber
{
    private ?User $previousUser = null;

    public function __construct(
        private \Doctrine\ORM\EntityManagerInterface $entityManager,
        private \Symfony\Component\HttpFoundation\RequestStack $requestStack,
        private TokenStorageInterface $tokenStorage
    ) {
    }

    #[AsEventListener(event: SecurityEvents::INTERACTIVE_LOGIN)]
    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();
        
        if (!$user instanceof User) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        
        $activityLog = ActivityLog::create(
            action: 'LOGIN',
            entityType: 'User',
            entityId: $user->getId(),
            entityName: $user->getFirstName() . ' ' . $user->getLastName(),
            user: $user,
            description: "User successfully logged in: {$user->getEmail()}"
        );

        if ($request) {
            $activityLog->setIpAddress($request->getClientIp())
                       ->setUserAgent($request->headers->get('User-Agent'));
        }

        $this->entityManager->persist($activityLog);
        $this->entityManager->flush();
    }

    #[AsEventListener(event: KernelEvents::REQUEST)]
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        
        // Store the current user before potential logout
        $token = $this->tokenStorage->getToken();
        $user = $token?->getUser();
        
        if ($user instanceof User) {
            $this->previousUser = $user;
        }
        
        // Check if this is a logout request
        if ($request->attributes->get('_route') === 'app_logout' && $this->previousUser) {
            $activityLog = ActivityLog::create(
                action: 'LOGOUT',
                entityType: 'User',
                entityId: $this->previousUser->getId(),
                entityName: $this->previousUser->getFirstName() . ' ' . $this->previousUser->getLastName(),
                user: $this->previousUser,
                description: "User logged out: {$this->previousUser->getEmail()}"
            );

            $activityLog->setIpAddress($request->getClientIp())
                       ->setUserAgent($request->headers->get('User-Agent'));

            $this->entityManager->persist($activityLog);
            $this->entityManager->flush();
            
            // Clear the previous user after logging
            $this->previousUser = null;
        }
    }

    #[AsEventListener(event: 'security.logout')]
    public function onLogout(LogoutEvent $event): void
    {
        // Use the LogoutEvent to capture logout events
        // This is more reliable than checking route names
        $token = $event->getToken();
        $user = $token?->getUser();
        
        if ($user instanceof User) {
            $request = $this->requestStack->getCurrentRequest();
            
            $activityLog = ActivityLog::create(
                action: 'LOGOUT',
                entityType: 'User',
                entityId: $user->getId(),
                entityName: $user->getFirstName() . ' ' . $user->getLastName(),
                user: $user,
                description: "User logged out: {$user->getEmail()}"
            );

            if ($request) {
                $activityLog->setIpAddress($request->getClientIp())
                           ->setUserAgent($request->headers->get('User-Agent'));
            }

            $this->entityManager->persist($activityLog);
            $this->entityManager->flush();
        }
    }
}