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
        $this->assertFileExists($data[0]['file']);
    }
}
