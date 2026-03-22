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
}
