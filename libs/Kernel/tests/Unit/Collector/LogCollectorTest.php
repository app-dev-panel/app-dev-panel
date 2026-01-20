<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use Psr\Log\LogLevel;
use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\LogCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;

final class LogCollectorTest extends AbstractCollectorTestCase
{
    /**
     * @param CollectorInterface|LogCollector $collector
     */
    protected function collectTestData(CollectorInterface $collector): void
    {
        $collector->collect(LogLevel::ALERT, 'test', ['context'], __FILE__ . ':' . __LINE__);
    }

    protected function getCollector(): CollectorInterface
    {
        return new LogCollector(new TimelineCollector());
    }
}
