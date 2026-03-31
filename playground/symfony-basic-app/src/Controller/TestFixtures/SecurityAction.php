<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use AppDevPanel\Kernel\Collector\AuthorizationCollector;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/test/fixtures/security', name: 'test_security', methods: ['GET'])]
final readonly class SecurityAction
{
    public function __construct(
        private AuthorizationCollector $authorizationCollector,
    ) {}

    public function __invoke(): JsonResponse
    {
        $this->authorizationCollector->collectUser('admin@example.com', ['ROLE_ADMIN', 'ROLE_USER'], true);
        $this->authorizationCollector->collectFirewall('main');
        $this->authorizationCollector->collectToken('jwt', ['sub' => '123', 'iss' => 'app'], '2026-12-31T23:59:59Z');
        $this->authorizationCollector->collectGuard('web', 'users', ['driver' => 'session']);
        $this->authorizationCollector->collectRoleHierarchy(['ROLE_ADMIN' => ['ROLE_USER', 'ROLE_EDITOR']]);
        $this->authorizationCollector->collectEffectiveRoles(['ROLE_ADMIN', 'ROLE_USER', 'ROLE_EDITOR']);
        $this->authorizationCollector->collectAuthenticationEvent('login', 'form_login', 'success', [
            'ip' => '127.0.0.1',
        ]);

        $this->authorizationCollector->logAccessDecision(
            'ROLE_ADMIN',
            'App\\Entity\\User',
            'ACCESS_GRANTED',
            [['voter' => 'RoleVoter', 'result' => 'ACCESS_GRANTED']],
            0.002,
            ['route' => '/admin'],
        );
        $this->authorizationCollector->logAccessDecision(
            'EDIT',
            'App\\Entity\\Post',
            'ACCESS_DENIED',
            [['voter' => 'PostVoter', 'result' => 'ACCESS_DENIED']],
            0.001,
        );

        return new JsonResponse(['fixture' => 'security:basic', 'status' => 'ok']);
    }
}
