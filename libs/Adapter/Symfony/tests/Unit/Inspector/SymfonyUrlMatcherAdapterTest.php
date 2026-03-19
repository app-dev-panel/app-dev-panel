<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Unit\Inspector;

use AppDevPanel\Adapter\Symfony\Inspector\SymfonyMatchResult;
use AppDevPanel\Adapter\Symfony\Inspector\SymfonyUrlMatcherAdapter;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RouterInterface;

final class SymfonyUrlMatcherAdapterTest extends TestCase
{
    public function testMatchReturnsSuccessResult(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->method('match')
            ->with('/')
            ->willReturn(['_controller' => 'App\\HomeController::index', '_route' => 'home']);

        $adapter = new SymfonyUrlMatcherAdapter($router);
        $result = $adapter->match(new ServerRequest('GET', '/'));

        $this->assertTrue($result->isSuccess());
        $this->assertSame(['App\\HomeController::index'], $result->middlewares);
    }

    public function testMatchReturnsFailureOnResourceNotFound(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->method('match')->willThrowException(new ResourceNotFoundException());

        $adapter = new SymfonyUrlMatcherAdapter($router);
        $result = $adapter->match(new ServerRequest('GET', '/nonexistent'));

        $this->assertFalse($result->isSuccess());
    }

    public function testMatchReturnsFailureOnMethodNotAllowed(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->method('match')->willThrowException(new MethodNotAllowedException(['POST']));

        $adapter = new SymfonyUrlMatcherAdapter($router);
        $result = $adapter->match(new ServerRequest('GET', '/api/users'));

        $this->assertFalse($result->isSuccess());
    }

    public function testMatchWithoutController(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->method('match')->willReturn(['_route' => 'redirect']);

        $adapter = new SymfonyUrlMatcherAdapter($router);
        $result = $adapter->match(new ServerRequest('GET', '/old'));

        $this->assertTrue($result->isSuccess());
        $this->assertSame([], $result->middlewares);
    }
}
