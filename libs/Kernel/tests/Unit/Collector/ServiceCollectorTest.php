<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\ServiceCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Event\MethodCallRecord;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;
use stdClass;

final class ServiceCollectorTest extends AbstractCollectorTestCase
{
    /**
     * @param CollectorInterface|ServiceCollector $collector
     */
    protected function collectTestData(CollectorInterface $collector): void
    {
        $time = microtime(true);
        $collector->collect(
            new MethodCallRecord('test', stdClass::class, 'test', [], '', 'success', null, $time, $time + 1),
        );
    }

    protected function getCollector(): CollectorInterface
    {
        return new ServiceCollector(new TimelineCollector());
    }

    protected function checkCollectedData(array $data): void
    {
        parent::checkCollectedData($data);

        $this->assertCount(1, $data);
        $this->assertSame('test', $data[0]['service']);
        $this->assertSame(stdClass::class, $data[0]['class']);
        $this->assertSame('test', $data[0]['method']);
        $this->assertSame([], $data[0]['arguments']);
        $this->assertSame('success', $data[0]['status']);
        $this->assertNull($data[0]['error']);
    }

    protected function checkSummaryData(array $data): void
    {
        parent::checkSummaryData($data);

        $this->assertArrayHasKey('service', $data);
        $this->assertSame(1, $data['service']['total']);
    }

    public function testCollectMultipleRecords(): void
    {
        $collector = new ServiceCollector(new TimelineCollector());
        $collector->startup();

        $time = microtime(true);
        $collector->collect(
            new MethodCallRecord(
                'svc1',
                stdClass::class,
                'method1',
                ['a'],
                'result1',
                'success',
                null,
                $time,
                $time + 0.01,
            ),
        );
        $collector->collect(
            new MethodCallRecord(
                'svc2',
                stdClass::class,
                'method2',
                null,
                null,
                'error',
                new \RuntimeException('fail'),
                $time,
                $time + 0.02,
            ),
        );

        $data = $collector->getCollected();
        $this->assertCount(2, $data);
        $this->assertSame('svc1', $data[0]['service']);
        $this->assertSame('svc2', $data[1]['service']);
        $this->assertSame('error', $data[1]['status']);

        $summary = $collector->getSummary();
        $this->assertSame(2, $summary['service']['total']);
    }
}
