<?php

declare(strict_types = 1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use stdClass;
use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\ServiceCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;

final class ServiceCollectorTest extends AbstractCollectorTestCase
{
    /**
     * @param CollectorInterface|ServiceCollector $collector
     */
    protected function collectTestData(CollectorInterface $collector): void
    {
        $time = microtime(true);
        $collector->collect('test', stdClass::class, 'test', [], '', 'success', null, $time, $time + 1);
    }

    protected function getCollector(): CollectorInterface
    {
        return new ServiceCollector(new TimelineCollector());
    }
}
