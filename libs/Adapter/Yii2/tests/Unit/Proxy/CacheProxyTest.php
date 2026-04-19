<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Unit\Proxy;

use AppDevPanel\Adapter\Yii2\Proxy\CacheProxy;
use AppDevPanel\Kernel\Collector\CacheCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use PHPUnit\Framework\TestCase;
use yii\caching\ArrayCache;

final class CacheProxyTest extends TestCase
{
    private ArrayCache $inner;
    private CacheCollector $collector;
    private CacheProxy $proxy;

    protected function setUp(): void
    {
        $timeline = new TimelineCollector();
        $timeline->startup();

        $this->collector = new CacheCollector($timeline);
        $this->collector->startup();

        $this->inner = new ArrayCache();
        $this->proxy = new CacheProxy($this->inner, $this->collector, 'default');
    }

    public function testSetStoresInInnerAndLogsOperation(): void
    {
        $this->proxy->set('user:1', ['name' => 'Alice']);

        $data = $this->collector->getCollected();
        $this->assertCount(1, $data['operations']);
        $this->assertSame('set', $data['operations'][0]['operation']);
        $this->assertSame('user:1', $data['operations'][0]['key']);
        $this->assertSame('default', $data['operations'][0]['pool']);
        $this->assertSame(['name' => 'Alice'], $data['operations'][0]['value']);

        // Inner cache actually has the value
        $this->assertSame(['name' => 'Alice'], $this->inner->get('user:1'));
    }

    public function testGetHitIncrementsHits(): void
    {
        $this->inner->set('user:1', 'Alice');

        $value = $this->proxy->get('user:1');

        $this->assertSame('Alice', $value);

        $data = $this->collector->getCollected();
        $this->assertCount(1, $data['operations']);
        $this->assertSame('get', $data['operations'][0]['operation']);
        $this->assertTrue($data['operations'][0]['hit']);
        $this->assertSame(1, $data['hits']);
        $this->assertSame(0, $data['misses']);
    }

    public function testGetMissIncrementsMisses(): void
    {
        $value = $this->proxy->get('user:missing');

        $this->assertFalse($value);

        $data = $this->collector->getCollected();
        $this->assertCount(1, $data['operations']);
        $this->assertFalse($data['operations'][0]['hit']);
        $this->assertSame(0, $data['hits']);
        $this->assertSame(1, $data['misses']);
    }

    public function testDeleteLogsDeleteOperation(): void
    {
        $this->inner->set('user:1', 'Alice');

        $result = $this->proxy->delete('user:1');

        $this->assertTrue($result);
        $data = $this->collector->getCollected();
        $this->assertCount(1, $data['operations']);
        $this->assertSame('delete', $data['operations'][0]['operation']);
        $this->assertSame('user:1', $data['operations'][0]['key']);

        // Inner cache no longer has the value
        $this->assertFalse($this->inner->get('user:1'));
    }

    public function testExistsLogsExistsOperation(): void
    {
        $this->inner->set('user:1', 'Alice');

        $result = $this->proxy->exists('user:1');

        $this->assertTrue($result);
        $data = $this->collector->getCollected();
        $this->assertSame('exists', $data['operations'][0]['operation']);
        $this->assertTrue($data['operations'][0]['hit']);
    }

    public function testFlushLogsClearOperation(): void
    {
        $this->inner->set('user:1', 'Alice');

        $this->proxy->flush();

        $data = $this->collector->getCollected();
        $this->assertSame('clear', $data['operations'][0]['operation']);
        $this->assertSame('*', $data['operations'][0]['key']);
    }

    public function testMultiGetLogsOneOperationPerKey(): void
    {
        $this->inner->set('a', 1);
        $this->inner->set('b', 2);

        $result = $this->proxy->multiGet(['a', 'b', 'missing']);

        $this->assertSame(1, $result['a']);
        $this->assertSame(2, $result['b']);
        $this->assertFalse($result['missing']);

        $data = $this->collector->getCollected();
        $this->assertCount(3, $data['operations']);
        $this->assertSame(2, $data['hits']);
        $this->assertSame(1, $data['misses']);
    }

    public function testMultiSetLogsOneOperationPerItem(): void
    {
        $this->proxy->multiSet(['a' => 1, 'b' => 2]);

        $data = $this->collector->getCollected();
        $this->assertCount(2, $data['operations']);
        $this->assertSame('set', $data['operations'][0]['operation']);
        $this->assertSame('set', $data['operations'][1]['operation']);

        $this->assertSame(1, $this->inner->get('a'));
        $this->assertSame(2, $this->inner->get('b'));
    }

    public function testDurationIsNonNegative(): void
    {
        $this->proxy->set('k', 'v');
        $this->proxy->get('k');

        $data = $this->collector->getCollected();
        foreach ($data['operations'] as $operation) {
            $this->assertGreaterThanOrEqual(0.0, $operation['duration']);
        }
    }

    public function testCustomPoolName(): void
    {
        $proxy = new CacheProxy($this->inner, $this->collector, 'redis');
        $proxy->set('k', 'v');

        $data = $this->collector->getCollected();
        $this->assertSame('redis', $data['operations'][0]['pool']);
    }

    public function testGetInnerReturnsWrappedCache(): void
    {
        $this->assertSame($this->inner, $this->proxy->getInner());
    }

    public function testArrayKeyIsStringified(): void
    {
        // Yii 2 allows complex keys (e.g. ['top-products', 'limit' => 10])
        $this->proxy->set(['top-products', 'limit' => 10], ['a', 'b']);

        $data = $this->collector->getCollected();
        $this->assertIsString($data['operations'][0]['key']);
        $this->assertStringContainsString('top-products', $data['operations'][0]['key']);
    }
}
