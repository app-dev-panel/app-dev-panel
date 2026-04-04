<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use App\Security\InMemoryUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[Route('/test/fixtures/security', name: 'test_security', methods: ['GET'])]
final readonly class SecurityAction
{
    public function __construct(
        private Security $security,
        private AuthorizationCheckerInterface $authorizationChecker,
    ) {}

    public function __invoke(): JsonResponse
    {
        // Programmatically log in a user — triggers LoginSuccessEvent which the
        // AuthorizationSubscriber captures (user identity, firewall, auth event).
        $user = new InMemoryUser('admin@example.com', ['ROLE_USER']);
        $this->security->login($user, firewallName: 'main');

        // isGranted() calls trigger VoteEvent — captured by AuthorizationSubscriber
        // as access decisions. ROLE_USER should be granted, ROLE_ADMIN should be denied.
        $isUser = $this->authorizationChecker->isGranted('ROLE_USER');
        $isAdmin = $this->authorizationChecker->isGranted('ROLE_ADMIN');

        return new JsonResponse([
            'fixture' => 'security:basic',
            'status' => 'ok',
            'isUser' => $isUser,
            'isAdmin' => $isAdmin,
            'user' => $user->getUserIdentifier(),
        ]);
    }
}
