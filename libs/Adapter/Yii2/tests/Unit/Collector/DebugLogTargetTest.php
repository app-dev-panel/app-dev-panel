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
            ['Test error message',   Logger::LEVEL_ERROR,   'app',             microtime(true)],
            ['Test warning message', Logger::LEVEL_WARNING, 'app\models',      microtime(true)],
            ['Test info message',    Logger::LEVEL_INFO,    'app\controllers', microtime(true)],
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
            ['Error',   Logger::LEVEL_ERROR,   'test', microtime(true)],
            ['Warning', Logger::LEVEL_WARNING, 'test', microtime(true)],
            ['Info',    Logger::LEVEL_INFO,    'test', microtime(true)],
            ['Trace',   Logger::LEVEL_TRACE,   'test', microtime(true)],
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

    public function testArrayMessageIsDumpedAndPreservedInContext(): void
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

        // Message must be a string so downstream tooling can concat safely
        $this->assertIsString($collected[0]['message']);
        $this->assertStringContainsString('key', $collected[0]['message']);
        $this->assertStringContainsString('value', $collected[0]['message']);

        // Original data preserved in context
        $this->assertSame(['key' => 'value'], $collected[0]['context']['raw_message']);
        $this->assertSame('test', $collected[0]['context']['category']);
    }

    public function testThrowableMessageIsNormalized(): void
    {
        $timeline = new TimelineCollector();
        $timeline->startup();
        $logCollector = new LogCollector($timeline);
        $logCollector->startup();

        $target = new DebugLogTarget($logCollector);

        $exception = new \RuntimeException('Boom');

        $target->messages = [
            [$exception, Logger::LEVEL_ERROR, 'app', microtime(true)],
        ];

        $target->export();

        $collected = $logCollector->getCollected();
        $this->assertCount(1, $collected);
        $this->assertSame('Boom', $collected[0]['message']);
        $this->assertSame('Boom', $collected[0]['context']['exception']);
        $this->assertSame(\RuntimeException::class, $collected[0]['context']['class']);
        $this->assertIsString($collected[0]['context']['trace']);
    }

    public function testStringableMessageIsCastToString(): void
    {
        $timeline = new TimelineCollector();
        $timeline->startup();
        $logCollector = new LogCollector($timeline);
        $logCollector->startup();

        $target = new DebugLogTarget($logCollector);

        $stringable = new class implements \Stringable {
            public function __toString(): string
            {
                return 'stringable-value';
            }
        };

        $target->messages = [
            [$stringable, Logger::LEVEL_INFO, 'app', microtime(true)],
        ];

        $target->export();

        $collected = $logCollector->getCollected();
        $this->assertSame('stringable-value', $collected[0]['message']);
        $this->assertArrayNotHasKey('raw_message', $collected[0]['context']);
    }

    public function testPlainStringMessageIsPreserved(): void
    {
        $timeline = new TimelineCollector();
        $timeline->startup();
        $logCollector = new LogCollector($timeline);
        $logCollector->startup();

        $target = new DebugLogTarget($logCollector);

        $target->messages = [
            ['plain string', Logger::LEVEL_INFO, 'app', microtime(true)],
        ];

        $target->export();

        $collected = $logCollector->getCollected();
        $this->assertSame('plain string', $collected[0]['message']);
        $this->assertArrayNotHasKey('raw_message', $collected[0]['context']);
    }

    public function testExportIntervalIsOne(): void
    {
        $timeline = new TimelineCollector();
        $logCollector = new LogCollector($timeline);

        $target = new DebugLogTarget($logCollector);

        $this->assertSame(1, $target->exportInterval);
    }
}
