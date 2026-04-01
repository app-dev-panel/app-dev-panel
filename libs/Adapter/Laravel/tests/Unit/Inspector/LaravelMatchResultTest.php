<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Tests\Unit\Inspector;

use AppDevPanel\Adapter\Laravel\Inspector\LaravelMatchResult;
use PHPUnit\Framework\TestCase;

final class LaravelMatchResultTest extends TestCase
{
    public function testSuccessfulMatchWithController(): void
    {
        $result = new LaravelMatchResult(true, 'App\\Http\\Controllers\\HomeController@index');

        $this->assertTrue($result->isSuccess());
        $this->assertSame(['App\\Http\\Controllers\\HomeController@index'], $result->middlewares);
    }

    public function testSuccessfulMatchWithoutController(): void
    {
        $result = new LaravelMatchResult(true);

        $this->assertTrue($result->isSuccess());
        $this->assertSame([], $result->middlewares);
    }

    public function testFailedMatch(): void
    {
        $result = new LaravelMatchResult(false);

        $this->assertFalse($result->isSuccess());
        $this->assertSame([], $result->middlewares);
    }

    public function testRouteReturnsSelf(): void
    {
        $result = new LaravelMatchResult(true);

        $this->assertSame($result, $result->route());
    }

    public function testFailedMatchWithController(): void
    {
        $result = new LaravelMatchResult(false, 'App\\Http\\Controllers\\FallbackController@handle');

        $this->assertFalse($result->isSuccess());
        $this->assertSame(['App\\Http\\Controllers\\FallbackController@handle'], $result->middlewares);
    }

    public function testMiddlewaresIsListType(): void
    {
        $result = new LaravelMatchResult(true, 'App\\Http\\Controllers\\UserController@show');

        $this->assertCount(1, $result->middlewares);
        $this->assertSame('App\\Http\\Controllers\\UserController@show', $result->middlewares[0]);
    }

    public function testRouteChainability(): void
    {
        $result = new LaravelMatchResult(true, 'Controller@action');

        // route() returns self, so middlewares should be accessible
        $this->assertSame(['Controller@action'], $result->route()->middlewares);
        $this->assertTrue($result->route()->isSuccess());
    }

    public function testNullControllerExplicit(): void
    {
        $result = new LaravelMatchResult(true, null);

        $this->assertTrue($result->isSuccess());
        $this->assertSame([], $result->middlewares);
    }
}
