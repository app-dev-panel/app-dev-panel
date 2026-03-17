<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Unit\Inspector;

use AppDevPanel\Adapter\Symfony\Inspector\SymfonyMatchResult;
use PHPUnit\Framework\TestCase;

final class SymfonyMatchResultTest extends TestCase
{
    public function testSuccessfulMatch(): void
    {
        $result = new SymfonyMatchResult(true, 'App\\Controller::action');

        $this->assertTrue($result->isSuccess());
        $this->assertSame(['App\\Controller::action'], $result->middlewares);
    }

    public function testFailedMatch(): void
    {
        $result = new SymfonyMatchResult(false);

        $this->assertFalse($result->isSuccess());
        $this->assertSame([], $result->middlewares);
    }

    public function testRouteReturnsSelf(): void
    {
        $result = new SymfonyMatchResult(true, 'App\\Controller::action');

        $this->assertSame($result, $result->route());
    }

    public function testSuccessWithNullController(): void
    {
        $result = new SymfonyMatchResult(true);

        $this->assertTrue($result->isSuccess());
        $this->assertSame([], $result->middlewares);
    }
}
