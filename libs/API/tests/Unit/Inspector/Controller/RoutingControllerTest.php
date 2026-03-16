<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Controller;

use AppDevPanel\Api\Inspector\Controller\RoutingController;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Yiisoft\Router\MatchingResult;
use Yiisoft\Router\Route;
use Yiisoft\Router\RouteCollectionInterface;
use Yiisoft\Router\UrlMatcherInterface;

final class RoutingControllerTest extends ControllerTestCase
{
    private function createController(): RoutingController
    {
        return new RoutingController($this->createResponseFactory());
    }

    public function testRoutes(): void
    {
        $route = Route::get('/test')->name('test-route');

        $routeCollection = $this->createMock(RouteCollectionInterface::class);
        $routeCollection->method('getRoutes')->willReturn([$route]);

        $controller = $this->createController();
        $response = $controller->routes($routeCollection);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testRoutesEmpty(): void
    {
        $routeCollection = $this->createMock(RouteCollectionInterface::class);
        $routeCollection->method('getRoutes')->willReturn([]);

        $controller = $this->createController();
        $response = $controller->routes($routeCollection);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testCheckRouteNoPath(): void
    {
        $matcher = $this->createMock(UrlMatcherInterface::class);
        $factory = new Psr17Factory();

        $controller = $this->createController();
        $response = $controller->checkRoute($this->get(), $matcher, $factory);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function testCheckRouteNotFound(): void
    {
        $matcher = $this->createMock(UrlMatcherInterface::class);
        $matcher->method('match')->willReturn(MatchingResult::fromFailure([405]));

        $factory = new Psr17Factory();

        $controller = $this->createController();
        $response = $controller->checkRoute($this->get(['route' => '/unknown']), $matcher, $factory);

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertFalse($data['result']);
    }

    public function testCheckRouteFound(): void
    {
        $route = Route::get('/test')->name('test')->middleware('handler');

        $result = MatchingResult::fromSuccess($route, ['id' => '1']);

        $matcher = $this->createMock(UrlMatcherInterface::class);
        $matcher->method('match')->willReturn($result);

        $factory = new Psr17Factory();

        $controller = $this->createController();
        $response = $controller->checkRoute($this->get(['route' => '/test']), $matcher, $factory);

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertTrue($data['result']);
        $this->assertArrayHasKey('action', $data);
    }

    public function testCheckRouteWithMethod(): void
    {
        $route = Route::post('/submit')->name('submit')->middleware('handler');

        $result = MatchingResult::fromSuccess($route, []);

        $matcher = $this->createMock(UrlMatcherInterface::class);
        $matcher->method('match')->willReturn($result);

        $factory = new Psr17Factory();

        $controller = $this->createController();
        $response = $controller->checkRoute($this->get(['route' => 'POST /submit']), $matcher, $factory);

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertTrue($data['result']);
    }
}
