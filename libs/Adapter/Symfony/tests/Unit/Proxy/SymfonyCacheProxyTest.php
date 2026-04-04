<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Unit\Proxy;

use AppDevPanel\Adapter\Symfony\Proxy\SymfonyCacheProxy;
use AppDevPanel\Kernel\Collector\CacheCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

final class SymfonyCacheProxyTest extends TestCase
{
    private CacheCollector $collector;

    protected function setUp(): void
    {
        $this->collector = new CacheCollector(new TimelineCollector());
        $this->collector->startup();
    }

    public function testGetItemCollectsHit(): void
    {
        $item = $this->createMock(CacheItemInterface::class);
        $item->method('isHit')->willReturn(true);
        $item->method('get')->willReturn(['id' => 42]);

        $pool = $this->createMock(CacheItemPoolInterface::class);
        $pool->method('getItem')->with('user:42')->willReturn($item);

        $proxy = new SymfonyCacheProxy($pool, $this->collector, 'app');

        $result = $proxy->getItem('user:42');

        $this->assertSame($item, $result);

        $collected = $this->collector->getCollected();
        $this->assertCount(1, $collected['operations']);
        $this->assertSame('get', $collected['operations'][0]['operation']);
        $this->assertSame('user:42', $collected['operations'][0]['key']);
        $this->assertTrue($collected['operations'][0]['hit']);
        $this->assertSame('app', $collected['operations'][0]['pool']);
    }

    public function testGetItemCollectsMiss(): void
    {
        $item = $this->createMock(CacheItemInterface::class);
        $item->method('isHit')->willReturn(false);
        $item->method('get')->willReturn(null);

        $pool = $this->createMock(CacheItemPoolInterface::class);
        $pool->method('getItem')->with('user:99')->willReturn($item);

        $proxy = new SymfonyCacheProxy($pool, $this->collector, 'default');

        $proxy->getItem('user:99');

        $collected = $this->collector->getCollected();
        $this->assertFalse($collected['operations'][0]['hit']);
    }

    public function testSaveCollectsSetOperation(): void
    {
        $item = $this->createMock(CacheItemInterface::class);
        $item->method('getKey')->willReturn('user:42');
        $item->method('get')->willReturn(['name' => 'John']);

        $pool = $this->createMock(CacheItemPoolInterface::class);
        $pool->method('save')->willReturn(true);

        $proxy = new SymfonyCacheProxy($pool, $this->collector);

        $result = $proxy->save($item);

        $this->assertTrue($result);

        $collected = $this->collector->getCollected();
        $this->assertSame('set', $collected['operations'][0]['operation']);
        $this->assertSame('user:42', $collected['operations'][0]['key']);
    }

    public function testDeleteItemCollectsDeleteOperation(): void
    {
        $pool = $this->createMock(CacheItemPoolInterface::class);
        $pool->method('deleteItem')->willReturn(true);

        $proxy = new SymfonyCacheProxy($pool, $this->collector);

        $result = $proxy->deleteItem('user:42');

        $this->assertTrue($result);

        $collected = $this->collector->getCollected();
        $this->assertSame('delete', $collected['operations'][0]['operation']);
        $this->assertSame('user:42', $collected['operations'][0]['key']);
    }

    public function testHasItemCollectsHasOperation(): void
    {
        $pool = $this->createMock(CacheItemPoolInterface::class);
        $pool->method('hasItem')->willReturn(true);

        $proxy = new SymfonyCacheProxy($pool, $this->collector);

        $result = $proxy->hasItem('user:42');

        $this->assertTrue($result);

        $collected = $this->collector->getCollected();
        $this->assertSame('has', $collected['operations'][0]['operation']);
        $this->assertTrue($collected['operations'][0]['hit']);
    }

    public function testClearCollectsClearOperation(): void
    {
        $pool = $this->createMock(CacheItemPoolInterface::class);
        $pool->method('clear')->willReturn(true);

        $proxy = new SymfonyCacheProxy($pool, $this->collector);

        $proxy->clear();

        $collected = $this->collector->getCollected();
        $this->assertSame('clear', $collected['operations'][0]['operation']);
    }

    public function testCommitDelegatesToDecorated(): void
    {
        $pool = $this->createMock(CacheItemPoolInterface::class);
        $pool->expects($this->once())->method('commit')->willReturn(true);

        $proxy = new SymfonyCacheProxy($pool, $this->collector);

        $this->assertTrue($proxy->commit());
    }
}
