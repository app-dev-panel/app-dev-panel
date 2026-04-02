<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\EventCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;
use AppDevPanel\Kernel\Tests\Unit\Support\DummyEvent;

final class EventCollectorTest extends AbstractCollectorTestCase
{
    /**
     * @param CollectorInterface|EventCollector $collector
     */
    protected function collectTestData(CollectorInterface $collector): void
    {
        $collector->collect(new DummyEvent(), __FILE__ . ':' . __LINE__);
    }

    protected function getCollector(): CollectorInterface
    {
        return new EventCollector(new TimelineCollector());
    }

    protected function checkCollectedData(array $data): void
    {
        $this->assertCount(1, $data);
        $this->assertSame(DummyEvent::class, $data[0]['name']);
        $this->assertFileExists($data[0]['file']);
        $this->assertArrayHasKey('line', $data[0]);
        $this->assertArrayHasKey('time', $data[0]);
        $this->assertIsFloat($data[0]['time']);
    }

    protected function checkSummaryData(array $data): void
    {
        parent::checkSummaryData($data);

        $this->assertArrayHasKey('event', $data);
        $this->assertSame(1, $data['event']['total']);
    }

    public function testCollectMultipleEvents(): void
    {
        $collector = new EventCollector(new TimelineCollector());
        $collector->startup();

        $collector->collect(new DummyEvent(), 'file1.php:10');
        $collector->collect(new DummyEvent(), 'file2.php:20');

        $data = $collector->getCollected();
        $this->assertCount(2, $data);

        $summary = $collector->getSummary();
        $this->assertSame(2, $summary['event']['total']);
    }
}
