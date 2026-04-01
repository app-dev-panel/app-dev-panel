<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Controller;

use AppDevPanel\Api\Inspector\Controller\RoutingController;
use Yiisoft\Router\MatchingResult;
use Yiisoft\Router\Route;
use Yiisoft\Router\RouteCollectionInterface;
use Yiisoft\Router\UrlMatcherInterface;

final class RoutingControllerTest extends ControllerTestCase
{
    private function createController(
        ?RouteCollectionInterface $routeCollection = null,
        ?UrlMatcherInterface $urlMatcher = null,
    ): RoutingController {
        return new RoutingController($this->createResponseFactory(), $routeCollection, $urlMatcher);
    }

    public function testRoutes(): void
    {
        $route = Route::get('/test')->name('test-route');

        $routeCollection = $this->createMock(RouteCollectionInterface::class);
        $routeCollection->method('getRoutes')->willReturn([$route]);

        $controller = $this->createController($routeCollection);
        $response = $controller->routes($this->get());

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testRoutesEmpty(): void
    {
        $routeCollection = $this->createMock(RouteCollectionInterface::class);
        $routeCollection->method('getRoutes')->willReturn([]);

        $controller = $this->createController($routeCollection);
        $response = $controller->routes($this->get());

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testRoutesReturns501WhenNoRouteCollection(): void
    {
        $controller = $this->createController(null, null);
        $response = $controller->routes($this->get());

        $this->assertSame(501, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('framework integration', $data['error']);
    }

    public function testCheckRouteNoPath(): void
    {
        $matcher = $this->createMock(UrlMatcherInterface::class);

        $controller = $this->createController(urlMatcher: $matcher);
        $response = $controller->checkRoute($this->get());

        $this->assertSame(422, $response->getStatusCode());
    }

    public function testCheckRouteReturns501WhenNoUrlMatcher(): void
    {
        $controller = $this->createController(null, null);
        $response = $controller->checkRoute($this->get(['route' => '/test']));

        $this->assertSame(501, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('framework integration', $data['error']);
    }

    public function testCheckRouteNotFound(): void
    {
        $matcher = $this->createMock(UrlMatcherInterface::class);
        $matcher->method('match')->willReturn(MatchingResult::fromFailure([405]));

        $controller = $this->createController(urlMatcher: $matcher);
        $response = $controller->checkRoute($this->get(['route' => '/unknown']));

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

        $controller = $this->createController(urlMatcher: $matcher);
        $response = $controller->checkRoute($this->get(['route' => '/test']));

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

        $controller = $this->createController(urlMatcher: $matcher);
        $response = $controller->checkRoute($this->get(['route' => 'POST /submit']));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertTrue($data['result']);
    }

    public function testCheckRouteWithPutMethod(): void
    {
        $route = Route::put('/update')->name('update')->middleware('handler');
        $result = MatchingResult::fromSuccess($route, []);

        $matcher = $this->createMock(UrlMatcherInterface::class);
        $matcher->method('match')->willReturn($result);

        $controller = $this->createController(urlMatcher: $matcher);
        $response = $controller->checkRoute($this->get(['route' => 'PUT /update']));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertTrue($data['result']);
    }

    public function testCheckRouteWithDeleteMethod(): void
    {
        $route = Route::delete('/item')->name('delete-item')->middleware('handler');
        $result = MatchingResult::fromSuccess($route, []);

        $matcher = $this->createMock(UrlMatcherInterface::class);
        $matcher->method('match')->willReturn($result);

        $controller = $this->createController(urlMatcher: $matcher);
        $response = $controller->checkRoute($this->get(['route' => 'DELETE /item']));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertTrue($data['result']);
    }

    public function testCheckRouteWithPatchMethod(): void
    {
        $route = Route::patch('/patch-item')->name('patch-item')->middleware('handler');
        $result = MatchingResult::fromSuccess($route, []);

        $matcher = $this->createMock(UrlMatcherInterface::class);
        $matcher->method('match')->willReturn($result);

        $controller = $this->createController(urlMatcher: $matcher);
        $response = $controller->checkRoute($this->get(['route' => 'PATCH /patch-item']));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertTrue($data['result']);
    }

    public function testCheckRouteWithNonMethodPrefix(): void
    {
        // A route starting with a word that is NOT an HTTP method should be treated as path
        $matcher = $this->createMock(UrlMatcherInterface::class);
        $matcher->method('match')->willReturn(MatchingResult::fromFailure([405]));

        $controller = $this->createController(urlMatcher: $matcher);
        $response = $controller->checkRoute($this->get(['route' => 'NOTMETHOD /path']));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertFalse($data['result']);
    }

    public function testCheckRouteNoPathReturnsMessage(): void
    {
        $matcher = $this->createMock(UrlMatcherInterface::class);

        $controller = $this->createController(urlMatcher: $matcher);
        $response = $controller->checkRoute($this->get());

        $this->assertSame(422, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertArrayHasKey('message', $data);
        $this->assertStringContainsString('not specified', $data['message']);
    }

    public function testRoutesWithMultipleRoutes(): void
    {
        $routes = [
            Route::get('/users')->name('users.index'),
            Route::post('/users')->name('users.create'),
            Route::get('/posts')->name('posts.index'),
        ];

        $routeCollection = $this->createMock(RouteCollectionInterface::class);
        $routeCollection->method('getRoutes')->willReturn($routes);

        $controller = $this->createController($routeCollection);
        $response = $controller->routes($this->get());

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertCount(3, $data);
    }
}
