<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Unit\Collector;

use AppDevPanel\Adapter\Yii2\Collector\DebugLogTarget;
use AppDevPanel\Kernel\Collector\LogCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use PHPUnit\Framework\TestCase;
use yii\log\Logger;

final class DebugLogTargetTest extends TestCase
{
    public function testExportFeedsLogCollector(): void
    {
        $timeline = new TimelineCollector();
        $timeline->startup();
        $logCollector = new LogCollector($timeline);
        $logCollector->startup();

        $target = new DebugLogTarget($logCollector);

        // Simulate Yii logger flushing messages to the target
        $target->messages = [
            ['Test error message', Logger::LEVEL_ERROR, 'app', microtime(true)],
            ['Test warning message', Logger::LEVEL_WARNING, 'app\models', microtime(true)],
            ['Test info message', Logger::LEVEL_INFO, 'app\controllers', microtime(true)],
        ];

        $target->export();

        $collected = $logCollector->getCollected();
        $this->assertCount(3, $collected);

        $this->assertSame('error', $collected[0]['level']);
        $this->assertSame('Test error message', $collected[0]['message']);
        $this->assertSame(['category' => 'app'], $collected[0]['context']);

        $this->assertSame('warning', $collected[1]['level']);
        $this->assertSame('info', $collected[2]['level']);
    }

    public function testLevelMapping(): void
    {
        $timeline = new TimelineCollector();
        $timeline->startup();
        $logCollector = new LogCollector($timeline);
        $logCollector->startup();

        $target = new DebugLogTarget($logCollector);

        $target->messages = [
            ['Error', Logger::LEVEL_ERROR, 'test', microtime(true)],
            ['Warning', Logger::LEVEL_WARNING, 'test', microtime(true)],
            ['Info', Logger::LEVEL_INFO, 'test', microtime(true)],
            ['Trace', Logger::LEVEL_TRACE, 'test', microtime(true)],
            ['Profile', Logger::LEVEL_PROFILE, 'test', microtime(true)],
        ];

        $target->export();

        $collected = $logCollector->getCollected();
        $this->assertSame('error', $collected[0]['level']);
        $this->assertSame('warning', $collected[1]['level']);
        $this->assertSame('info', $collected[2]['level']);
        $this->assertSame('debug', $collected[3]['level']);
        $this->assertSame('debug', $collected[4]['level']);
    }

    public function testNonStringMessageIsConverted(): void
    {
        $timeline = new TimelineCollector();
        $timeline->startup();
        $logCollector = new LogCollector($timeline);
        $logCollector->startup();

        $target = new DebugLogTarget($logCollector);

        $target->messages = [
            [['key' => 'value'], Logger::LEVEL_INFO, 'test', microtime(true)],
        ];

        $target->export();

        $collected = $logCollector->getCollected();
        $this->assertCount(1, $collected);
        $this->assertStringContainsString('key', $collected[0]['message']);
        $this->assertStringContainsString('value', $collected[0]['message']);
    }

    public function testExportIntervalIsOne(): void
    {
        $timeline = new TimelineCollector();
        $logCollector = new LogCollector($timeline);

        $target = new DebugLogTarget($logCollector);

        $this->assertSame(1, $target->exportInterval);
    }
}
