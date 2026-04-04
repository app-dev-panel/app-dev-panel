<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[Route('/test/fixtures/security', name: 'test_security', methods: ['GET'])]
final readonly class SecurityAction
{
    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker,
        private TokenStorageInterface $tokenStorage,
    ) {}

    public function __invoke(): JsonResponse
    {
        // Use Symfony's Security component — the AuthorizationSubscriber listens to
        // Security events (LoginSuccess, VoteEvent, etc.) and feeds data to
        // AuthorizationCollector.
        //
        // isGranted() calls trigger VoteEvent which is captured by AuthorizationSubscriber.
        $isAdmin = $this->authorizationChecker->isGranted('ROLE_ADMIN');
        $isUser = $this->authorizationChecker->isGranted('ROLE_USER');

        $token = $this->tokenStorage->getToken();
        $user = $token?->getUserIdentifier();

        return new JsonResponse([
            'fixture' => 'security:basic',
            'status' => 'ok',
            'isAdmin' => $isAdmin,
            'isUser' => $isUser,
            'user' => $user,
        ]);
    }
}
