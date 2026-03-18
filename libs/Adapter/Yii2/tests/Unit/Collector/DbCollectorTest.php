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
        $this->assertArrayHasKey('type', $firstQuery);
        $this->assertArrayHasKey('backtrace', $firstQuery);
        $this->assertSame('SELECT', $firstQuery['type']);

        $secondQuery = $data['queries'][1];
        $this->assertSame('INSERT', $secondQuery['type']);
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

    public function testTimingWithBeginQuery(): void
    {
        $collector = new DbCollector(new TimelineCollector());
        $collector->startup();

        $collector->beginQuery();
        // Small delay to ensure measurable time
        usleep(1000); // 1ms
        $collector->logQuery('SELECT 1', [], 1);

        $queries = $collector->getCollected()['queries'];
        $this->assertGreaterThan(0.0, $queries[0]['time']);
        $this->assertGreaterThan(0.0, $collector->getCollected()['totalTime']);
    }

    public function testTimingWithoutBeginQueryIsZero(): void
    {
        $collector = new DbCollector(new TimelineCollector());
        $collector->startup();

        // No beginQuery call — time should be 0
        $collector->logQuery('SELECT 1', [], 1);

        $queries = $collector->getCollected()['queries'];
        $this->assertSame(0.0, $queries[0]['time']);
    }

    public function testSqlTypeDetection(): void
    {
        $collector = new DbCollector(new TimelineCollector());
        $collector->startup();

        $collector->logQuery('SELECT * FROM users', [], 0);
        $collector->logQuery('INSERT INTO users (name) VALUES (?)', ['test'], 1);
        $collector->logQuery('UPDATE users SET name = ?', ['test'], 1);
        $collector->logQuery('DELETE FROM users WHERE id = 1', [], 1);
        $collector->logQuery('CREATE TABLE test (id INT)', [], 0);
        $collector->logQuery('BEGIN', [], 0);
        $collector->logQuery('COMMIT', [], 0);
        $collector->logQuery('SHOW TABLES', [], 0);

        $queries = $collector->getCollected()['queries'];
        $this->assertSame('SELECT', $queries[0]['type']);
        $this->assertSame('INSERT', $queries[1]['type']);
        $this->assertSame('UPDATE', $queries[2]['type']);
        $this->assertSame('DELETE', $queries[3]['type']);
        $this->assertSame('CREATE', $queries[4]['type']);
        $this->assertSame('TRANSACTION', $queries[5]['type']);
        $this->assertSame('COMMIT', $queries[6]['type']);
        $this->assertSame('SHOW', $queries[7]['type']);
    }
}
