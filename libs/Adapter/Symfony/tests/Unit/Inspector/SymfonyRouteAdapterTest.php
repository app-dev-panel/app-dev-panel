<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Unit\Inspector;

use AppDevPanel\Adapter\Symfony\Inspector\SymfonyRouteAdapter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Route;

final class SymfonyRouteAdapterTest extends TestCase
{
    public function testDebugInfoContainsExpectedKeys(): void
    {
        $route = new Route('/users', defaults: ['_controller' => 'App\\UserController::list'], methods: ['GET']);
        $adapter = new SymfonyRouteAdapter('user_list', $route);

        $info = $adapter->__debugInfo();

        $this->assertSame('user_list', $info['name']);
        $this->assertSame('/users', $info['pattern']);
        $this->assertSame(['GET'], $info['methods']);
        $this->assertSame([], $info['defaults']);
        $this->assertSame(['App\\UserController::list'], $info['middlewares']);
        $this->assertSame([], $info['hosts']);
        $this->assertSame(0, $info['override']);
    }

    public function testDebugInfoWithHostRestriction(): void
    {
        $route = new Route('/api', methods: ['GET']);
        $route->setHost('api.example.com');
        $adapter = new SymfonyRouteAdapter('api_root', $route);

        $info = $adapter->__debugInfo();

        $this->assertSame(['api.example.com'], $info['hosts']);
    }

    public function testDebugInfoWithNoMethodsReturnsAny(): void
    {
        $route = new Route('/catchall');
        $adapter = new SymfonyRouteAdapter('catchall', $route);

        $info = $adapter->__debugInfo();

        $this->assertSame(['ANY'], $info['methods']);
    }

    public function testDebugInfoStripsControllerFromDefaults(): void
    {
        $route = new Route('/posts/{id}', defaults: ['_controller' => 'App\\PostController::show', 'id' => '1']);
        $adapter = new SymfonyRouteAdapter('post_show', $route);

        $info = $adapter->__debugInfo();

        $this->assertArrayNotHasKey('_controller', $info['defaults']);
        $this->assertSame('1', $info['defaults']['id']);
        $this->assertSame(['App\\PostController::show'], $info['middlewares']);
    }

    public function testDebugInfoWithNoController(): void
    {
        $route = new Route('/static');
        $adapter = new SymfonyRouteAdapter('static_page', $route);

        $info = $adapter->__debugInfo();

        $this->assertSame([], $info['middlewares']);
    }

    #[DataProvider('multipleMethodsProvider')]
    public function testDebugInfoWithMultipleMethods(array $methods): void
    {
        $route = new Route('/resource', methods: $methods);
        $adapter = new SymfonyRouteAdapter('resource', $route);

        $info = $adapter->__debugInfo();

        $this->assertSame($methods, $info['methods']);
    }

    /**
     * @return iterable<string, array{list<string>}>
     */
    public static function multipleMethodsProvider(): iterable
    {
        yield 'get-post' => [['GET', 'POST']];
        yield 'all-methods' => [['GET', 'POST', 'PUT', 'DELETE', 'PATCH']];
    }
}
