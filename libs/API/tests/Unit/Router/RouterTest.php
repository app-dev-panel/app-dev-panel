<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Router;

use AppDevPanel\Api\Router\Route;
use AppDevPanel\Api\Router\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testMatchReturnsRouteAndParams(): void
    {
        $router = new Router();
        $route = new Route('GET', '/api/view/{id}', ['Controller', 'view']);
        $router->addRoute($route);

        $result = $router->match('GET', '/api/view/abc');

        $this->assertNotNull($result);
        $this->assertSame($route, $result['route']);
        $this->assertSame(['id' => 'abc'], $result['params']);
    }

    public function testMatchReturnsNullWhenNoMatch(): void
    {
        $router = new Router();
        $router->addRoute(new Route('GET', '/api/list', ['Controller', 'list']));

        $this->assertNull($router->match('GET', '/unknown'));
    }

    public function testMatchStripsTrailingSlash(): void
    {
        $router = new Router();
        $router->addRoute(new Route('GET', '/api/list', ['Controller', 'list']));

        $result = $router->match('GET', '/api/list/');

        $this->assertNotNull($result);
    }

    public function testMatchRootPathPreservesSlash(): void
    {
        $router = new Router();
        $router->addRoute(new Route('GET', '/', ['Controller', 'index']));

        $result = $router->match('GET', '/');

        $this->assertNotNull($result);
    }

    public function testAddRoutes(): void
    {
        $router = new Router();
        $routes = [
            new Route('GET', '/a', ['C', 'a']),
            new Route('POST', '/b', ['C', 'b']),
        ];
        $router->addRoutes($routes);

        $this->assertCount(2, $router->getRoutes());
    }

    public function testGetRoutesReturnsAllRoutes(): void
    {
        $router = new Router();
        $router->addRoute(new Route('GET', '/x', ['C', 'x']));
        $router->addRoute(new Route('POST', '/y', ['C', 'y']));

        $this->assertCount(2, $router->getRoutes());
    }

    public function testMatchReturnsFirstMatch(): void
    {
        $router = new Router();
        $first = new Route('GET', '/api/{id}', ['C', 'first']);
        $second = new Route('GET', '/api/{id}', ['C', 'second']);
        $router->addRoutes([$first, $second]);

        $result = $router->match('GET', '/api/123');

        $this->assertNotNull($result);
        $this->assertSame($first, $result['route']);
    }
}
