<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Tests\Unit\Inspector;

use AppDevPanel\Adapter\Laravel\Inspector\LaravelUrlMatcherAdapter;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

final class LaravelUrlMatcherAdapterTest extends TestCase
{
    public function testMatchReturnsSuccessForExistingRoute(): void
    {
        $route = new Route(['GET'], '/users', ['uses' => 'App\\Http\\Controllers\\UserController@index']);

        $routeCollection = new RouteCollection();
        $routeCollection->add($route);

        $router = $this->createMock(Router::class);
        $router->method('getRoutes')->willReturn($routeCollection);

        $adapter = new LaravelUrlMatcherAdapter($router);
        $request = new ServerRequest('GET', 'http://localhost/users');
        $result = $adapter->match($request);

        $this->assertTrue($result->isSuccess());
    }

    public function testMatchReturnsFailureForMissingRoute(): void
    {
        $routeCollection = new RouteCollection();

        $router = $this->createMock(Router::class);
        $router->method('getRoutes')->willReturn($routeCollection);

        $adapter = new LaravelUrlMatcherAdapter($router);
        $request = new ServerRequest('GET', 'http://localhost/nonexistent');
        $result = $adapter->match($request);

        $this->assertFalse($result->isSuccess());
        $this->assertSame([], $result->middlewares);
    }

    public function testMatchClosureRouteHasNoController(): void
    {
        $route = new Route(['GET'], '/closure', fn() => 'hello');

        $routeCollection = new RouteCollection();
        $routeCollection->add($route);

        $router = $this->createMock(Router::class);
        $router->method('getRoutes')->willReturn($routeCollection);

        $adapter = new LaravelUrlMatcherAdapter($router);
        $request = new ServerRequest('GET', 'http://localhost/closure');
        $result = $adapter->match($request);

        $this->assertTrue($result->isSuccess());
        $this->assertSame([], $result->middlewares);
    }
}
