<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Router;

use AppDevPanel\Api\Router\Route;
use PHPUnit\Framework\TestCase;

final class RouteTest extends TestCase
{
    public function testExactMatch(): void
    {
        $route = new Route('GET', '/api/list', ['Controller', 'list']);

        $this->assertSame([], $route->match('GET', '/api/list'));
    }

    public function testExactMatchWrongPath(): void
    {
        $route = new Route('GET', '/api/list', ['Controller', 'list']);

        $this->assertNull($route->match('GET', '/api/view'));
    }

    public function testWrongMethodReturnsNull(): void
    {
        $route = new Route('GET', '/api/list', ['Controller', 'list']);

        $this->assertNull($route->match('POST', '/api/list'));
    }

    public function testParameterExtraction(): void
    {
        $route = new Route('GET', '/api/view/{id}', ['Controller', 'view']);

        $params = $route->match('GET', '/api/view/abc123');

        $this->assertSame(['id' => 'abc123'], $params);
    }

    public function testMultipleParameters(): void
    {
        $route = new Route('GET', '/api/{type}/{id}', ['Controller', 'show']);

        $params = $route->match('GET', '/api/users/42');

        $this->assertSame(['type' => 'users', 'id' => '42'], $params);
    }

    public function testParameterNoMatch(): void
    {
        $route = new Route('GET', '/api/view/{id}', ['Controller', 'view']);

        $this->assertNull($route->match('GET', '/api/view/'));
    }

    public function testConstructorProperties(): void
    {
        $route = new Route('POST', '/api/create', ['Controller', 'create'], 'create-route');

        $this->assertSame('POST', $route->method);
        $this->assertSame('/api/create', $route->pattern);
        $this->assertSame(['Controller', 'create'], $route->handler);
        $this->assertSame('create-route', $route->name);
    }

    public function testNullName(): void
    {
        $route = new Route('GET', '/', ['Controller', 'index']);

        $this->assertNull($route->name);
    }
}
