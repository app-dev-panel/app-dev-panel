<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Unit\Collector;

use AppDevPanel\Adapter\Yii2\Collector\DbCollector;
use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;

final class DbCollectorTest extends AbstractCollectorTestCase
{
    protected function getCollector(): CollectorInterface
    {
        return new DbCollector(new TimelineCollector());
    }

    protected function collectTestData(CollectorInterface $collector): void
    {
        /** @var DbCollector $collector */
        $collector->logConnection();
        $collector->logQuery('SELECT * FROM users WHERE id = 1', [], 1);
        $collector->logQuery('INSERT INTO logs (message) VALUES (?)', ['hello'], 1);
    }

    protected function checkCollectedData(array $data): void
    {
        $this->assertArrayHasKey('queries', $data);
        $this->assertArrayHasKey('queryCount', $data);
        $this->assertArrayHasKey('connectionCount', $data);
        $this->assertArrayHasKey('totalTime', $data);

        $this->assertSame(2, $data['queryCount']);
        $this->assertSame(1, $data['connectionCount']);
        $this->assertCount(2, $data['queries']);

        $firstQuery = $data['queries'][0];
        $this->assertSame('SELECT * FROM users WHERE id = 1', $firstQuery['sql']);
        $this->assertSame([], $firstQuery['params']);
        $this->assertSame(1, $firstQuery['rowCount']);
        $this->assertArrayHasKey('time', $firstQuery);
        $this->assertArrayHasKey('backtrace', $firstQuery);
    }

    protected function checkSummaryData(array $data): void
    {
        $this->assertArrayHasKey('db', $data);
        $this->assertArrayHasKey('queryCount', $data['db']);
        $this->assertSame(2, $data['db']['queryCount']);
    }

    public function testLogQueryIgnoredWhenInactive(): void
    {
        $collector = new DbCollector(new TimelineCollector());

        // Don't call startup — collector is inactive
        $collector->logQuery('SELECT 1', [], 0);
        $collector->logConnection();

        $this->assertSame([], $collector->getCollected());
    }

    public function testResetClearsData(): void
    {
        $collector = new DbCollector(new TimelineCollector());
        $collector->startup();

        $collector->logConnection();
        $collector->logQuery('SELECT 1', [], 1);

        $this->assertSame(1, $collector->getCollected()['queryCount']);

        $collector->shutdown();

        // After shutdown + re-startup, data should be clean
        $collector->startup();
        $this->assertSame(0, $collector->getCollected()['queryCount']);
        $this->assertSame(0, $collector->getCollected()['connectionCount']);
    }

    public function testBacktraceFiltersVendor(): void
    {
        $collector = new DbCollector(new TimelineCollector());
        $collector->startup();

        $collector->logQuery('SELECT 1', [], 0);

        $queries = $collector->getCollected()['queries'];
        $this->assertCount(1, $queries);
        // Backtrace should point to this test file (non-vendor)
        $this->assertStringContainsString('DbCollectorTest.php', $queries[0]['backtrace']);
    }
}
