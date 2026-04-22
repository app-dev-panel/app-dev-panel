<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\LogCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;
use Psr\Log\LogLevel;

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

    public function testSummaryGroupsCountsByLevel(): void
    {
        $collector = new LogCollector(new TimelineCollector());
        $collector->startup();
        $collector->collect(LogLevel::ERROR, 'boom', [], 'file.php:1');
        $collector->collect(LogLevel::ERROR, 'boom again', [], 'file.php:2');
        $collector->collect(LogLevel::WARNING, 'careful', [], 'file.php:3');
        $collector->collect(LogLevel::INFO, 'fyi', [], 'file.php:4');
        $collector->collect(LogLevel::INFO, 'fyi2', [], 'file.php:5');
        $collector->collect(LogLevel::INFO, 'fyi3', [], 'file.php:6');

        $summary = $collector->getSummary();

        $this->assertSame(6, $summary['logger']['total']);
        $this->assertSame(
            [LogLevel::ERROR => 2, LogLevel::WARNING => 1, LogLevel::INFO => 3],
            $summary['logger']['byLevel'],
        );
    }
}
