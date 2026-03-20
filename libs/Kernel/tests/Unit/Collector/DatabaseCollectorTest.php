<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\DatabaseCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;

final class DatabaseCollectorTest extends AbstractCollectorTestCase
{
    protected function getCollector(): CollectorInterface
    {
        return new DatabaseCollector(new TimelineCollector());
    }

    protected function collectTestData(CollectorInterface $collector): void
    {
        /** @var DatabaseCollector $collector */
        $collector->collectQueryStart(
            'q1',
            'SELECT * FROM users WHERE id = ?',
            'SELECT * FROM users WHERE id = 1',
            [':id' => 1],
            '/src/Repo.php:10',
        );
        $collector->collectQueryEnd('q1', 5);
    }

    protected function checkCollectedData(array $data): void
    {
        $this->assertArrayHasKey('queries', $data);
        $this->assertArrayHasKey('transactions', $data);
        $this->assertCount(1, $data['queries']);

        $query = $data['queries']['q1'];
        $this->assertSame('SELECT * FROM users WHERE id = ?', $query['sql']);
        $this->assertSame('SELECT * FROM users WHERE id = 1', $query['rawSql']);
        $this->assertSame('success', $query['status']);
        $this->assertSame(5, $query['rowsNumber']);
        $this->assertCount(2, $query['actions']);
        $this->assertSame('query.start', $query['actions'][0]['action']);
        $this->assertSame('query.end', $query['actions'][1]['action']);
    }

    protected function checkSummaryData(array $data): void
    {
        $this->assertArrayHasKey('db', $data);
        $this->assertSame(1, $data['db']['queries']['total']);
        $this->assertSame(0, $data['db']['queries']['error']);
        $this->assertSame(0, $data['db']['transactions']['total']);
    }

    public function testLogQuery(): void
    {
        $collector = new DatabaseCollector(new TimelineCollector());
        $collector->startup();

        $start = microtime(true);
        $end = $start + 0.015;
        $collector->logQuery('SELECT 1', 'SELECT 1', [], '/test.php:1', $start, $end, 1);

        $data = $collector->getCollected();
        $this->assertCount(1, $data['queries']);

        $query = $data['queries'][0];
        $this->assertSame('SELECT 1', $query['sql']);
        $this->assertSame('success', $query['status']);
        $this->assertSame(1, $query['rowsNumber']);
        $this->assertSame($start, $query['actions'][0]['time']);
        $this->assertSame($end, $query['actions'][1]['time']);
    }

    public function testQueryError(): void
    {
        $collector = new DatabaseCollector(new TimelineCollector());
        $collector->startup();

        $collector->collectQueryStart('e1', 'BAD SQL', 'BAD SQL', [], '/test.php:1');
        $collector->collectQueryError('e1', new \RuntimeException('syntax error'));

        $data = $collector->getCollected();
        $this->assertSame('error', $data['queries']['e1']['status']);
        $this->assertInstanceOf(\RuntimeException::class, $data['queries']['e1']['exception']);

        $summary = $collector->getSummary();
        $this->assertSame(1, $summary['db']['queries']['error']);
    }

    public function testTransactions(): void
    {
        $collector = new DatabaseCollector(new TimelineCollector());
        $collector->startup();

        $collector->collectTransactionStart('SERIALIZABLE', '/test.php:1');
        $collector->collectQueryStart('q1', 'INSERT INTO t VALUES(1)', 'INSERT INTO t VALUES(1)', [], '/test.php:2');
        $collector->collectQueryEnd('q1', 1);
        $collector->collectTransactionCommit('/test.php:3');

        $data = $collector->getCollected();
        $this->assertCount(1, $data['transactions']);

        $tx = $data['transactions'][1];
        $this->assertSame('commit', $tx['status']);
        $this->assertSame('SERIALIZABLE', $tx['level']);

        $summary = $collector->getSummary();
        $this->assertSame(1, $summary['db']['transactions']['total']);
        $this->assertSame(0, $summary['db']['transactions']['error']);
    }

    public function testTransactionRollback(): void
    {
        $collector = new DatabaseCollector(new TimelineCollector());
        $collector->startup();

        $collector->collectTransactionStart(null, '/test.php:1');
        $collector->collectTransactionRollback('/test.php:2');

        $summary = $collector->getSummary();
        $this->assertSame(1, $summary['db']['transactions']['error']);
    }

    public function testInactiveGuards(): void
    {
        $collector = new DatabaseCollector(new TimelineCollector());

        $collector->collectQueryStart('q1', 'SELECT 1', 'SELECT 1', [], '/test.php:1');
        $collector->collectQueryEnd('q1', 0);
        $collector->collectQueryError('q1', new \RuntimeException('test'));
        $collector->logQuery('SELECT 1', 'SELECT 1', [], '/test.php:1', 0.0, 0.01);
        $collector->collectTransactionStart(null, '/test.php:1');
        $collector->collectTransactionCommit('/test.php:2');
        $collector->collectTransactionRollback('/test.php:3');

        $this->assertSame([], $collector->getCollected());
        $this->assertSame([], $collector->getSummary());
    }

    public function testResetClearsData(): void
    {
        $collector = new DatabaseCollector(new TimelineCollector());
        $collector->startup();

        $collector->logQuery('SELECT 1', 'SELECT 1', [], '/test.php:1', 0.0, 0.01, 1);
        $this->assertCount(1, $collector->getCollected()['queries']);

        $collector->shutdown();
        $collector->startup();

        $this->assertCount(0, $collector->getCollected()['queries']);
        $this->assertCount(0, $collector->getCollected()['transactions']);
    }
}
