<?php

declare(strict_types=1);

namespace App\Web\TestFixtures;

use AppDevPanel\Kernel\Collector\SecurityCollector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;

final readonly class SecurityAction implements RequestHandlerInterface
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private SecurityCollector $securityCollector,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->securityCollector->collectUser('admin@example.com', ['ROLE_ADMIN', 'ROLE_USER'], true);
        $this->securityCollector->collectFirewall('main');
        $this->securityCollector->collectToken('jwt', ['sub' => '123', 'iss' => 'app'], '2026-12-31T23:59:59Z');
        $this->securityCollector->collectGuard('web', 'users', ['driver' => 'session']);
        $this->securityCollector->collectRoleHierarchy(['ROLE_ADMIN' => ['ROLE_USER', 'ROLE_EDITOR']]);
        $this->securityCollector->collectEffectiveRoles(['ROLE_ADMIN', 'ROLE_USER', 'ROLE_EDITOR']);
        $this->securityCollector->collectAuthenticationEvent('login', 'form_login', 'success', ['ip' => '127.0.0.1']);

        $this->securityCollector->logAccessDecision(
            'ROLE_ADMIN',
            'App\\Entity\\User',
            'ACCESS_GRANTED',
            [['voter' => 'RoleVoter', 'result' => 'ACCESS_GRANTED']],
            0.002,
            ['route' => '/admin'],
        );
        $this->securityCollector->logAccessDecision(
            'EDIT',
            'App\\Entity\\Post',
            'ACCESS_DENIED',
            [['voter' => 'PostVoter', 'result' => 'ACCESS_DENIED']],
            0.001,
        );

        return $this->responseFactory->createResponse(['fixture' => 'security:basic', 'status' => 'ok']);
    }
}
