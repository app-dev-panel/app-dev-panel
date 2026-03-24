<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\RedisCollector;
use AppDevPanel\Kernel\Collector\RedisCommandRecord;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;

final class RedisCollectorTest extends AbstractCollectorTestCase
{
    protected function getCollector(): CollectorInterface
    {
        return new RedisCollector(new TimelineCollector());
    }

    protected function collectTestData(CollectorInterface $collector): void
    {
        assert($collector instanceof RedisCollector, 'Expected RedisCollector instance');
        $collector->logCommand(new RedisCommandRecord(
            connection: 'default',
            command: 'SET',
            arguments: ['user:1', 'Alice'],
            result: true,
            duration: 0.001,
        ));
        $collector->logCommand(new RedisCommandRecord(
            connection: 'default',
            command: 'GET',
            arguments: ['user:1'],
            result: 'Alice',
            duration: 0.002,
        ));
        $collector->logCommand(new RedisCommandRecord(
            connection: 'cache',
            command: 'DEL',
            arguments: ['session:abc'],
            result: 1,
            duration: 0.001,
            error: 'Connection timeout',
            line: '/app/src/Service.php:42',
        ));
    }

    protected function checkCollectedData(array $data): void
    {
        parent::checkCollectedData($data);

        $this->assertSame(3, $data['totalCommands']);
        $this->assertSame(1, $data['errorCount']);
        $this->assertCount(3, $data['commands']);
        $this->assertEqualsWithDelta(0.004, $data['totalTime'], 0.0001);
        $this->assertCount(2, $data['connections']);
        $this->assertContains('default', $data['connections']);
        $this->assertContains('cache', $data['connections']);

        $cmd = $data['commands'][0];
        $this->assertSame('default', $cmd['connection']);
        $this->assertSame('SET', $cmd['command']);
        $this->assertSame(['user:1', 'Alice'], $cmd['arguments']);
        $this->assertTrue($cmd['result']);
        $this->assertNull($cmd['error']);

        $cmd2 = $data['commands'][1];
        $this->assertSame('GET', $cmd2['command']);
        $this->assertSame('Alice', $cmd2['result']);

        $cmd3 = $data['commands'][2];
        $this->assertSame('cache', $cmd3['connection']);
        $this->assertSame('DEL', $cmd3['command']);
        $this->assertSame('Connection timeout', $cmd3['error']);
        $this->assertSame('/app/src/Service.php:42', $cmd3['line']);
    }

    protected function checkSummaryData(array $data): void
    {
        parent::checkSummaryData($data);

        $this->assertArrayHasKey('redis', $data);
        $this->assertSame(3, $data['redis']['commandCount']);
        $this->assertSame(1, $data['redis']['errorCount']);
        $this->assertEqualsWithDelta(0.004, $data['redis']['totalTime'], 0.0001);
    }
}
