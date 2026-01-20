<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\LogCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;

final class TimelineCollectorTest extends AbstractCollectorTestCase
{
    /**
     * @param CollectorInterface|TimelineCollector $collector
     */
    protected function collectTestData(CollectorInterface $collector): void
    {
        $collector->collect(new LogCollector($collector), '123');
        $collector->collect(new LogCollector($collector), '345', 'context2', __FILE__ . ':' . 123);
    }

    protected function getCollector(): CollectorInterface
    {
        return new TimelineCollector();
    }

    protected function checkCollectedData(array $data): void
    {
        parent::checkCollectedData($data);

        $this->assertNotEmpty($data);
        $this->assertCount(2, $data);
        $this->assertCount(4, $data[0]);
        $this->assertSame(LogCollector::class, $data[0][2]);
        $this->assertSame('123', $data[0][1]);
        $this->assertSame([], $data[0][3]);

        $this->assertCount(4, $data[1]);
        $this->assertSame(LogCollector::class, $data[1][2]);
        $this->assertSame('345', $data[1][1]);
        $this->assertSame(['context2', __FILE__ . ':' . 123], $data[1][3]);
    }
}
