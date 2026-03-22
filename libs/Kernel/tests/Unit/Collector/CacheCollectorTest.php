<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\CacheCollector;
use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;

final class CacheCollectorTest extends AbstractCollectorTestCase
{
    protected function getCollector(): CollectorInterface
    {
        return new CacheCollector(new TimelineCollector());
    }

    protected function collectTestData(CollectorInterface $collector): void
    {
        assert($collector instanceof CacheCollector, 'Expected CacheCollector instance');
        $collector->logCacheOperation('app.cache', 'get', 'user.1', hit: true, duration: 0.001, value: [
            'name' => 'Alice',
        ]);
        $collector->logCacheOperation('app.cache', 'get', 'user.2', hit: false, duration: 0.002);
        $collector->logCacheOperation('app.cache', 'set', 'user.2', hit: false, duration: 0.003, value: [
            'name' => 'Bob',
        ]);
    }

    protected function checkCollectedData(array $data): void
    {
        parent::checkCollectedData($data);

        $this->assertSame(3, $data['totalOperations']);
        $this->assertSame(1, $data['hits']);
        $this->assertSame(1, $data['misses']);
        $this->assertCount(3, $data['operations']);

        $op = $data['operations'][0];
        $this->assertSame('app.cache', $op['pool']);
        $this->assertSame('get', $op['operation']);
        $this->assertSame('user.1', $op['key']);
        $this->assertTrue($op['hit']);
        $this->assertSame(['name' => 'Alice'], $op['value']);

        $this->assertNull($data['operations'][1]['value']);
        $this->assertSame(['name' => 'Bob'], $data['operations'][2]['value']);
    }

    protected function checkSummaryData(array $data): void
    {
        parent::checkSummaryData($data);

        $this->assertArrayHasKey('cache', $data);
        $this->assertSame(1, $data['cache']['hits']);
        $this->assertSame(1, $data['cache']['misses']);
        $this->assertSame(3, $data['cache']['totalOperations']);
    }
}
