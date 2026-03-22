<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Tests\Unit\Collector;

use AppDevPanel\Adapter\Laravel\Collector\RouterDataExtractor;
use AppDevPanel\Kernel\Collector\RouterCollector;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;
use PHPUnit\Framework\TestCase;

final class RouterDataExtractorTest extends TestCase
{
    public function testExtractCollectsMatchedRouteAndAllRoutes(): void
    {
        $routerCollector = new RouterCollector();
        $routerCollector->startup();

        $matchedRoute = new Route(['GET'], '/users/{id}', ['uses' => 'App\\Http\\Controllers\\UserController@show']);
        $matchedRoute->name('users.show');

        $request = Request::create('/users/42', 'GET');
        $matchedRoute->bind($request);
        $matchedRoute->setParameter('id', '42');
        $request->setRouteResolver(static fn() => $matchedRoute);

        $routeCollection = new RouteCollection();
        $routeCollection->add(new Route(['GET'], '/users', ['uses' => 'UserController@index']));
        $routeCollection->add(new Route(['GET'], '/users/{id}', ['uses' => 'UserController@show']));

        $router = $this->createMock(Router::class);
        $router->method('getRoutes')->willReturn($routeCollection);

        $extractor = new RouterDataExtractor($routerCollector, $router);
        $extractor->extract($request);

        $collected = $routerCollector->getCollected();

        $this->assertArrayHasKey('currentRoute', $collected);
        $this->assertSame('users.show', $collected['currentRoute']['name']);
        $this->assertSame('/users/{id}', $collected['currentRoute']['pattern']);

        $this->assertArrayHasKey('routes', $collected);
        $this->assertCount(2, $collected['routes']);
    }

    public function testExtractWithNoMatchedRoute(): void
    {
        $routerCollector = new RouterCollector();
        $routerCollector->startup();

        $request = Request::create('/not-found', 'GET');

        $routeCollection = new RouteCollection();
        $routeCollection->add(new Route(['GET'], '/users', ['uses' => 'UserController@index']));

        $router = $this->createMock(Router::class);
        $router->method('getRoutes')->willReturn($routeCollection);

        $extractor = new RouterDataExtractor($routerCollector, $router);
        $extractor->extract($request);

        $collected = $routerCollector->getCollected();

        $this->assertArrayHasKey('routes', $collected);
        $this->assertCount(1, $collected['routes']);
    }
}
