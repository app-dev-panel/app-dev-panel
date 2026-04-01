<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Unit\Inspector;

use AppDevPanel\Adapter\Yii2\Inspector\Yii2MatchResult;
use PHPUnit\Framework\TestCase;

final class Yii2MatchResultTest extends TestCase
{
    public function testSuccessfulMatch(): void
    {
        $result = new Yii2MatchResult(true, 'site/index');

        $this->assertTrue($result->isSuccess());
        $this->assertSame(['site/index'], $result->middlewares);
    }

    public function testFailedMatch(): void
    {
        $result = new Yii2MatchResult(false);

        $this->assertFalse($result->isSuccess());
        $this->assertSame([], $result->middlewares);
    }

    public function testRouteReturnsSelf(): void
    {
        $result = new Yii2MatchResult(true, 'user/view');

        $this->assertSame($result, $result->route());
    }

    public function testSuccessWithNullRoute(): void
    {
        $result = new Yii2MatchResult(true);

        $this->assertTrue($result->isSuccess());
        $this->assertSame([], $result->middlewares);
    }
}
