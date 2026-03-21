<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Tests\Unit\Inspector;

use AppDevPanel\Adapter\Laravel\Inspector\LaravelRouteAdapter;
use Illuminate\Routing\Route;
use PHPUnit\Framework\TestCase;

final class LaravelRouteAdapterTest extends TestCase
{
    public function testDebugInfoReturnsRouteData(): void
    {
        $route = new Route(['GET'], '/users/{id}', ['uses' => 'App\\Http\\Controllers\\UserController@show']);
        $route->name('users.show');

        $adapter = new LaravelRouteAdapter($route);
        $info = $adapter->__debugInfo();

        $this->assertSame('users.show', $info['name']);
        $this->assertSame('/users/{id}', $info['pattern']);
        $this->assertContains('GET', $info['methods']);
        $this->assertSame(0, $info['override']);
    }

    public function testDebugInfoFallsBackToUriWhenNoName(): void
    {
        $route = new Route(['POST'], '/api/data', ['uses' => 'App\\Http\\Controllers\\DataController@store']);

        $adapter = new LaravelRouteAdapter($route);
        $info = $adapter->__debugInfo();

        $this->assertSame('api/data', $info['name']);
        $this->assertSame('/api/data', $info['pattern']);
    }

    public function testDebugInfoWithClosureAction(): void
    {
        $route = new Route(['GET'], '/closure', fn() => 'hello');

        $adapter = new LaravelRouteAdapter($route);
        $info = $adapter->__debugInfo();

        // Closure actions should not be in middlewares
        $this->assertNotContains('Closure', $info['middlewares']);
    }

    public function testDebugInfoWithDomain(): void
    {
        $route = new Route(['GET'], '/test', ['uses' => 'Controller@method']);
        $route->domain('api.example.com');

        $adapter = new LaravelRouteAdapter($route);
        $info = $adapter->__debugInfo();

        $this->assertContains('api.example.com', $info['hosts']);
    }

    public function testDebugInfoWithoutDomain(): void
    {
        $route = new Route(['GET'], '/test', ['uses' => 'Controller@method']);

        $adapter = new LaravelRouteAdapter($route);
        $info = $adapter->__debugInfo();

        $this->assertSame([], $info['hosts']);
    }
}
