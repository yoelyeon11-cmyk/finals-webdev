<?php

namespace App\Service;

use App\Entity\ActivityLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ActivityLogger
{
    private EntityManagerInterface $entityManager;
    private RequestStack $requestStack;
    private TokenStorageInterface $tokenStorage;

    public function __construct(
        EntityManagerInterface $entityManager,
        RequestStack $requestStack,
        TokenStorageInterface $tokenStorage
    ) {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
        $this->tokenStorage = $tokenStorage;
    }

    public function log(string $action, ?string $targetData = null): void
    {
        error_log('🔵 ActivityLogger::log() called - Action: ' . $action . ', Target: ' . $targetData);
        
        $token = $this->tokenStorage->getToken();
        if (!$token || !$token->getUser()) {
            error_log('❌ ActivityLogger: No authenticated user, skipping log');
            return; // Don't log if no authenticated user
        }

        $user = $token->getUser();
        $request = $this->requestStack->getCurrentRequest();

        $log = new ActivityLog();
        $log->setUsername($user->getUserIdentifier());
        $log->setRole($this->getUserRole($user));
        $log->setAction($action);
        $log->setTargetData($targetData);

        if ($request) {
            $log->setIpAddress($request->getClientIp());
            $log->setUserAgent($request->headers->get('User-Agent'));
        }

        $this->entityManager->persist($log);
        $this->entityManager->flush();
        
        error_log('✅ ActivityLogger: Log persisted successfully - ID: ' . $log->getId());
    }

    private function getUserRole($user): string
    {
        $roles = $user->getRoles();
        
        if (in_array('ROLE_ADMIN', $roles)) {
            return 'ADMIN';
        } elseif (in_array('ROLE_STAFF', $roles)) {
            return 'STAFF';
        }
        
        return 'USER';
    }
}
