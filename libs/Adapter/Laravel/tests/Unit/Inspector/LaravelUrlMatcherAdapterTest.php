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
        $route = new Route(['GET'], '/closure', static fn() => 'hello');

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

    public function testMatchPostRoute(): void
    {
        $route = new Route(['POST'], '/users', ['uses' => 'App\\Http\\Controllers\\UserController@store']);

        $routeCollection = new RouteCollection();
        $routeCollection->add($route);

        $router = $this->createMock(Router::class);
        $router->method('getRoutes')->willReturn($routeCollection);

        $adapter = new LaravelUrlMatcherAdapter($router);
        $request = new ServerRequest('POST', 'http://localhost/users');
        $result = $adapter->match($request);

        $this->assertTrue($result->isSuccess());
    }

    public function testMatchWithRouteParameters(): void
    {
        $route = new Route(['GET'], '/users/{id}', ['uses' => 'App\\Http\\Controllers\\UserController@show']);

        $routeCollection = new RouteCollection();
        $routeCollection->add($route);

        $router = $this->createMock(Router::class);
        $router->method('getRoutes')->willReturn($routeCollection);

        $adapter = new LaravelUrlMatcherAdapter($router);
        $request = new ServerRequest('GET', 'http://localhost/users/42');
        $result = $adapter->match($request);

        $this->assertTrue($result->isSuccess());
    }

    public function testMatchWrongMethodReturnsFailure(): void
    {
        $route = new Route(['GET'], '/users', ['uses' => 'App\\Http\\Controllers\\UserController@index']);

        $routeCollection = new RouteCollection();
        $routeCollection->add($route);

        $router = $this->createMock(Router::class);
        $router->method('getRoutes')->willReturn($routeCollection);

        $adapter = new LaravelUrlMatcherAdapter($router);
        // DELETE is not registered for /users
        $request = new ServerRequest('DELETE', 'http://localhost/users');
        $result = $adapter->match($request);

        // Should fail since DELETE method is not registered
        $this->assertFalse($result->isSuccess());
    }
}
