<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Unit\Collector;

use AppDevPanel\Adapter\Symfony\Collector\DoctrineCollector;
use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;

final class DoctrineCollectorTest extends AbstractCollectorTestCase
{
    protected function getCollector(): CollectorInterface
    {
        return new DoctrineCollector(new TimelineCollector());
    }

    /**
     * @param CollectorInterface|DoctrineCollector $collector
     */
    protected function collectTestData(CollectorInterface $collector): void
    {
        $collector->logQuery('SELECT * FROM users WHERE id = ?', [1], ['integer'], 0.015);
        $collector->logQuery('INSERT INTO logs (message) VALUES (?)', ['test'], ['string'], 0.003);
    }

    protected function checkCollectedData(array $data): void
    {
        parent::checkCollectedData($data);

        $this->assertSame(2, $data['queryCount']);
        $this->assertSame(0.018, $data['totalTime']);
        $this->assertCount(2, $data['queries']);

        $query = $data['queries'][0];
        $this->assertSame('SELECT * FROM users WHERE id = ?', $query['sql']);
        $this->assertSame([1], $query['params']);
        $this->assertSame(['integer'], $query['types']);
        $this->assertSame(0.015, $query['executionTime']);
        $this->assertNotEmpty($query['backtrace']);
    }

    protected function checkSummaryData(array $data): void
    {
        parent::checkSummaryData($data);

        $this->assertArrayHasKey('doctrine', $data);
        $this->assertSame(2, $data['doctrine']['queryCount']);
        $this->assertSame(0.018, $data['doctrine']['totalTime']);
    }
}
