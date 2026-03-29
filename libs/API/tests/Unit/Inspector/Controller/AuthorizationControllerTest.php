<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Controller;

use AppDevPanel\Api\Inspector\Authorization\AuthorizationConfigProviderInterface;
use AppDevPanel\Api\Inspector\Authorization\NullAuthorizationConfigProvider;
use AppDevPanel\Api\Inspector\Controller\AuthorizationController;

final class AuthorizationControllerTest extends ControllerTestCase
{
    private function createController(?AuthorizationConfigProviderInterface $configProvider = null): AuthorizationController
    {
        return new AuthorizationController(
            $this->createResponseFactory(),
            $configProvider ?? new NullAuthorizationConfigProvider(),
        );
    }

    public function testIndexReturnsEmptyWhenNoProvider(): void
    {
        $controller = $this->createController();
        $response = $controller->index($this->get());

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame([], $data['guards']);
        $this->assertSame([], $data['roleHierarchy']);
        $this->assertSame([], $data['voters']);
        $this->assertSame([], $data['config']);
    }

    public function testIndexReturnsGuards(): void
    {
        $provider = $this->createMock(AuthorizationConfigProviderInterface::class);
        $provider
            ->method('getGuards')
            ->willReturn([
                ['name' => 'web', 'provider' => 'users', 'config' => ['driver' => 'session']],
                ['name' => 'api', 'provider' => 'tokens', 'config' => ['driver' => 'token']],
            ]);
        $provider->method('getRoleHierarchy')->willReturn([]);
        $provider->method('getVoters')->willReturn([]);
        $provider->method('getSecurityConfig')->willReturn([]);

        $controller = $this->createController($provider);
        $response = $controller->index($this->get());

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertCount(2, $data['guards']);
        $this->assertSame('web', $data['guards'][0]['name']);
        $this->assertSame('api', $data['guards'][1]['name']);
    }

    public function testIndexReturnsRoleHierarchy(): void
    {
        $provider = $this->createMock(AuthorizationConfigProviderInterface::class);
        $provider->method('getGuards')->willReturn([]);
        $provider
            ->method('getRoleHierarchy')
            ->willReturn([
                'ROLE_ADMIN' => ['ROLE_USER', 'ROLE_EDITOR'],
                'ROLE_EDITOR' => ['ROLE_USER'],
            ]);
        $provider->method('getVoters')->willReturn([]);
        $provider->method('getSecurityConfig')->willReturn([]);

        $controller = $this->createController($provider);
        $response = $controller->index($this->get());

        $data = $this->responseData($response);
        $this->assertSame(['ROLE_USER', 'ROLE_EDITOR'], $data['roleHierarchy']['ROLE_ADMIN']);
        $this->assertSame(['ROLE_USER'], $data['roleHierarchy']['ROLE_EDITOR']);
    }

    public function testIndexReturnsVoters(): void
    {
        $provider = $this->createMock(AuthorizationConfigProviderInterface::class);
        $provider->method('getGuards')->willReturn([]);
        $provider->method('getRoleHierarchy')->willReturn([]);
        $provider
            ->method('getVoters')
            ->willReturn([
                ['name' => 'RoleVoter', 'type' => 'voter', 'priority' => 255],
                ['name' => 'PostPolicy', 'type' => 'policy', 'priority' => null],
            ]);
        $provider->method('getSecurityConfig')->willReturn([]);

        $controller = $this->createController($provider);
        $response = $controller->index($this->get());

        $data = $this->responseData($response);
        $this->assertCount(2, $data['voters']);
        $this->assertSame('RoleVoter', $data['voters'][0]['name']);
        $this->assertSame('PostPolicy', $data['voters'][1]['name']);
    }

    public function testIndexReturnsSecurityConfig(): void
    {
        $provider = $this->createMock(AuthorizationConfigProviderInterface::class);
        $provider->method('getGuards')->willReturn([]);
        $provider->method('getRoleHierarchy')->willReturn([]);
        $provider->method('getVoters')->willReturn([]);
        $provider
            ->method('getSecurityConfig')
            ->willReturn([
                'access_decision_manager' => ['strategy' => 'affirmative'],
                'session_fixation_strategy' => 'migrate',
            ]);

        $controller = $this->createController($provider);
        $response = $controller->index($this->get());

        $data = $this->responseData($response);
        $this->assertSame('affirmative', $data['config']['access_decision_manager']['strategy']);
    }
}
