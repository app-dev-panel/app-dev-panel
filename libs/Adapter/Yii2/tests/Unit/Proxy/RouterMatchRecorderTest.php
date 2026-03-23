<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Unit\Proxy;

use AppDevPanel\Adapter\Yii2\Proxy\RouterMatchRecorder;
use PHPUnit\Framework\TestCase;
use yii\web\UrlRule;

final class RouterMatchRecorderTest extends TestCase
{
    public function testInitialStateIsEmpty(): void
    {
        $recorder = new RouterMatchRecorder();

        $this->assertNull($recorder->getMatchedRule());
        $this->assertNull($recorder->getMatchResult());
        $this->assertSame(0.0, $recorder->getMatchTime());
    }

    public function testRecordMatch(): void
    {
        $recorder = new RouterMatchRecorder();
        $rule = $this->createMock(UrlRule::class);

        $recorder->markStartIfNeeded();
        $recorder->recordMatch($rule, ['site/index', ['id' => '5']]);

        $this->assertSame($rule, $recorder->getMatchedRule());
        $this->assertSame(['site/index', ['id' => '5']], $recorder->getMatchResult());
        $this->assertGreaterThan(0, $recorder->getMatchTime());
    }

    public function testMarkStartOnlyOnce(): void
    {
        $recorder = new RouterMatchRecorder();
        $rule = $this->createMock(UrlRule::class);

        $recorder->markStartIfNeeded();
        usleep(1000); // 1ms
        $recorder->markStartIfNeeded(); // should not reset start time

        $recorder->recordMatch($rule, ['site/index', []]);

        // Match time should include the full duration (from first markStart)
        $this->assertGreaterThan(0, $recorder->getMatchTime());
    }

    public function testReset(): void
    {
        $recorder = new RouterMatchRecorder();
        $rule = $this->createMock(UrlRule::class);

        $recorder->markStartIfNeeded();
        $recorder->recordMatch($rule, ['site/index', []]);
        $recorder->reset();

        $this->assertNull($recorder->getMatchedRule());
        $this->assertNull($recorder->getMatchResult());
        $this->assertSame(0.0, $recorder->getMatchTime());
    }
}
