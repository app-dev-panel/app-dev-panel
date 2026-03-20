<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Unit\Collector;

use AppDevPanel\Adapter\Symfony\Collector\RouterDataExtractor;
use AppDevPanel\Kernel\Collector\RouterCollector;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

final class RouterDataExtractorTest extends TestCase
{
    public function testExtractMatchedRoute(): void
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add('home', new Route('/', defaults: ['_controller' => 'App\\Controller\\HomeController']));
        $routeCollection->add('user_show', new Route('/users/{id}', defaults: [
            '_controller' => 'App\\Controller\\UserController::show',
        ]));

        $router = $this->createMock(RouterInterface::class);
        $router->method('getRouteCollection')->willReturn($routeCollection);

        $collector = new RouterCollector();
        $collector->startup();

        $extractor = new RouterDataExtractor($collector, $router);

        $request = Request::create('/users/42');
        $request->attributes->set('_route', 'user_show');
        $request->attributes->set('_route_params', ['id' => '42']);
        $request->attributes->set('_controller', 'App\\Controller\\UserController::show');

        $extractor->extract($request);

        $collected = $collector->getCollected();

        $this->assertNotNull($collected['currentRoute']);
        $this->assertSame('user_show', $collected['currentRoute']['name']);
        $this->assertSame('/users/{id}', $collected['currentRoute']['pattern']);
        $this->assertSame(['id' => '42'], $collected['currentRoute']['arguments']);
        $this->assertSame('/users/42', $collected['currentRoute']['uri']);
        $this->assertSame('App\\Controller\\UserController::show', $collected['currentRoute']['action']);

        $this->assertCount(2, $collected['routes']);
        $this->assertSame('home', $collected['routes'][0]['name']);
        $this->assertSame('/', $collected['routes'][0]['pattern']);
        $this->assertSame('user_show', $collected['routes'][1]['name']);
        $this->assertSame('/users/{id}', $collected['routes'][1]['pattern']);
    }

    public function testExtractWithNoMatchedRoute(): void
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add('home', new Route('/'));

        $router = $this->createMock(RouterInterface::class);
        $router->method('getRouteCollection')->willReturn($routeCollection);

        $collector = new RouterCollector();
        $collector->startup();

        $extractor = new RouterDataExtractor($collector, $router);

        $request = Request::create('/not-found');
        // No _route attribute set — route was not matched

        $extractor->extract($request);

        $collected = $collector->getCollected();

        $this->assertNull($collected['currentRoute']);
        $this->assertCount(1, $collected['routes']);
    }

    public function testExtractWithNoRouter(): void
    {
        $collector = new RouterCollector();
        $collector->startup();

        $extractor = new RouterDataExtractor($collector);

        $request = Request::create('/test');
        $request->attributes->set('_route', 'test_route');
        $request->attributes->set('_controller', 'TestController');

        $extractor->extract($request);

        $collected = $collector->getCollected();

        // Current route is set but pattern falls back to route name
        $this->assertNotNull($collected['currentRoute']);
        $this->assertSame('test_route', $collected['currentRoute']['name']);
        $this->assertSame('test_route', $collected['currentRoute']['pattern']);

        // No routes collected without router
        $this->assertArrayNotHasKey('routes', $collected);
    }

    public function testExtractRouteWithMethods(): void
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add('api_create', new Route('/api/items')->setMethods(['POST', 'PUT']));
        $routeCollection->add('api_list', new Route('/api/items'));

        $router = $this->createMock(RouterInterface::class);
        $router->method('getRouteCollection')->willReturn($routeCollection);

        $collector = new RouterCollector();
        $collector->startup();

        $extractor = new RouterDataExtractor($collector, $router);

        $request = Request::create('/api/items', 'POST');
        $request->attributes->set('_route', 'api_create');
        $request->attributes->set('_route_params', []);
        $request->attributes->set('_controller', 'ApiController::create');

        $extractor->extract($request);

        $collected = $collector->getCollected();

        $this->assertSame(['POST', 'PUT'], $collected['routes'][0]['methods']);
        $this->assertSame(['ANY'], $collected['routes'][1]['methods']);
    }

    public function testExtractRouteWithHost(): void
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add('admin', new Route('/admin')->setHost('admin.example.com'));
        $routeCollection->add('home', new Route('/'));

        $router = $this->createMock(RouterInterface::class);
        $router->method('getRouteCollection')->willReturn($routeCollection);

        $collector = new RouterCollector();
        $collector->startup();

        $extractor = new RouterDataExtractor($collector, $router);

        $request = Request::create('http://admin.example.com/admin');
        $request->attributes->set('_route', 'admin');
        $request->attributes->set('_route_params', []);
        $request->attributes->set('_controller', 'AdminController');

        $extractor->extract($request);

        $collected = $collector->getCollected();

        $this->assertSame('admin.example.com', $collected['currentRoute']['host']);
        $this->assertSame('admin.example.com', $collected['routes'][0]['host']);
        $this->assertNull($collected['routes'][1]['host']);
    }
}
