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
        $collector->beginQuery();
        $collector->logQuery('SELECT * FROM users WHERE id = 1', [], 1);
        $collector->beginQuery();
        $collector->logQuery('INSERT INTO logs (message) VALUES (?)', ['hello'], 1);
    }

    protected function checkCollectedData(array $data): void
    {
        $this->assertArrayHasKey('queries', $data);
        $this->assertArrayHasKey('transactions', $data);
        $this->assertCount(2, $data['queries']);
        $this->assertSame([], $data['transactions']);

        $firstQuery = $data['queries'][0];
        $this->assertSame('SELECT * FROM users WHERE id = 1', $firstQuery['sql']);
        $this->assertSame('SELECT * FROM users WHERE id = 1', $firstQuery['rawSql']);
        $this->assertSame([], $firstQuery['params']);
        $this->assertSame(1, $firstQuery['rowsNumber']);
        $this->assertSame('success', $firstQuery['status']);
        $this->assertArrayHasKey('line', $firstQuery);
        $this->assertCount(2, $firstQuery['actions']);
        $this->assertSame('query.start', $firstQuery['actions'][0]['action']);
        $this->assertSame('query.end', $firstQuery['actions'][1]['action']);
    }

    protected function checkSummaryData(array $data): void
    {
        $this->assertArrayHasKey('db', $data);
        $this->assertArrayHasKey('queries', $data['db']);
        $this->assertSame(2, $data['db']['queries']['total']);
        $this->assertSame(0, $data['db']['queries']['error']);
    }

    public function testLogQueryIgnoredWhenInactive(): void
    {
        $collector = new DbCollector(new TimelineCollector());

        // Don't call startup — collector is inactive
        $collector->logQuery('SELECT 1', [], 0);
        $collector->logConnection();

        $this->assertSame([], $collector->getCollected());
    }

    public function testBeginQueryIgnoredWhenInactive(): void
    {
        $collector = new DbCollector(new TimelineCollector());

        $collector->beginQuery();
        $collector->logQuery('SELECT 1', [], 0);

        $this->assertSame([], $collector->getCollected());
    }

    public function testResetClearsData(): void
    {
        $collector = new DbCollector(new TimelineCollector());
        $collector->startup();

        $collector->logConnection();
        $collector->logQuery('SELECT 1', [], 1);

        $this->assertCount(1, $collector->getCollected()['queries']);

        $collector->shutdown();

        // After shutdown + re-startup, data should be clean
        $collector->startup();
        $this->assertCount(0, $collector->getCollected()['queries']);
    }

    public function testBacktraceFiltersVendor(): void
    {
        $collector = new DbCollector(new TimelineCollector());
        $collector->startup();

        $collector->logQuery('SELECT 1', [], 0);

        $queries = $collector->getCollected()['queries'];
        $this->assertCount(1, $queries);
        // Line should point to this test file (non-vendor)
        $this->assertStringContainsString('DbCollectorTest.php', $queries[0]['line']);
    }

    public function testTimingWithBeginQuery(): void
    {
        $collector = new DbCollector(new TimelineCollector());
        $collector->startup();

        $collector->beginQuery();
        // Small delay to ensure measurable time
        usleep(1000); // 1ms
        $collector->logQuery('SELECT 1', [], 1);

        $queries = $collector->getCollected()['queries'];
        $startTime = $queries[0]['actions'][0]['time'];
        $endTime = $queries[0]['actions'][1]['time'];
        $this->assertGreaterThan(0.0, $endTime - $startTime);
    }

    public function testTimingWithoutBeginQuery(): void
    {
        $collector = new DbCollector(new TimelineCollector());
        $collector->startup();

        // No beginQuery call — start and end time should be very close
        $collector->logQuery('SELECT 1', [], 1);

        $queries = $collector->getCollected()['queries'];
        $startTime = $queries[0]['actions'][0]['time'];
        $endTime = $queries[0]['actions'][1]['time'];
        $this->assertLessThan(0.001, $endTime - $startTime);
    }
}
