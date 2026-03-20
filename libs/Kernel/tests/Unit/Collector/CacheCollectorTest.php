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
        assert($collector instanceof CacheCollector);
        $collector->logCacheOperation('app.cache', 'get', 'user.1', hit: true, duration: 0.001);
        $collector->logCacheOperation('app.cache', 'get', 'user.2', hit: false, duration: 0.002);
        $collector->logCacheOperation('app.cache', 'set', 'user.2', hit: false, duration: 0.003);
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
