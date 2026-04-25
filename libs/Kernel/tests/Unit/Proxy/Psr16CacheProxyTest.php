<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Proxy;

use AppDevPanel\Kernel\Collector\CacheCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Proxy\Psr16CacheProxy;
use DateInterval;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

final class Psr16CacheProxyTest extends TestCase
{
    private function makeInnerCache(array $storage = []): CacheInterface
    {
        return new class($storage) implements CacheInterface {
            /** @var array<string, mixed> */
            public array $storage;

            public function __construct(array $storage)
            {
                $this->storage = $storage;
            }

            public function get(string $key, mixed $default = null): mixed
            {
                return $this->storage[$key] ?? $default;
            }

            public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
            {
                $this->storage[$key] = $value;
                return true;
            }

            public function delete(string $key): bool
            {
                unset($this->storage[$key]);
                return true;
            }

            public function clear(): bool
            {
                $this->storage = [];
                return true;
            }

            public function getMultiple(iterable $keys, mixed $default = null): iterable
            {
                $result = [];
                foreach ($keys as $key) {
                    $result[$key] = $this->storage[$key] ?? $default;
                }
                return $result;
            }

            public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
            {
                foreach ($values as $key => $value) {
                    $this->storage[$key] = $value;
                }
                return true;
            }

            public function deleteMultiple(iterable $keys): bool
            {
                foreach ($keys as $key) {
                    unset($this->storage[$key]);
                }
                return true;
            }

            public function has(string $key): bool
            {
                return array_key_exists($key, $this->storage);
            }
        };
    }

    private function makeProxy(CacheInterface $inner, string $pool = 'default'): array
    {
        $collector = new CacheCollector(new TimelineCollector());
        $collector->startup();
        $proxy = new Psr16CacheProxy($inner, $collector, $pool);

        return [$proxy, $collector];
    }

    public function testSetRecordsOperation(): void
    {
        [$proxy, $collector] = $this->makeProxy($this->makeInnerCache());

        $result = $proxy->set('user:1', ['name' => 'Alice']);

        $this->assertTrue($result);
        $data = $collector->getCollected();
        $this->assertCount(1, $data['operations']);

        $op = $data['operations'][0];
        $this->assertSame('default', $op['pool']);
        $this->assertSame('set', $op['operation']);
        $this->assertSame('user:1', $op['key']);
        $this->assertFalse($op['hit']);
        $this->assertSame(['name' => 'Alice'], $op['value']);
        $this->assertGreaterThanOrEqual(0.0, $op['duration']);
    }

    public function testGetHitRecordsValue(): void
    {
        $inner = $this->makeInnerCache(['user:1' => ['name' => 'Alice']]);
        [$proxy, $collector] = $this->makeProxy($inner, 'sessions');

        $value = $proxy->get('user:1');

        $this->assertSame(['name' => 'Alice'], $value);
        $op = $collector->getCollected()['operations'][0];
        $this->assertSame('sessions', $op['pool']);
        $this->assertSame('get', $op['operation']);
        $this->assertSame('user:1', $op['key']);
        $this->assertTrue($op['hit']);
        $this->assertSame(['name' => 'Alice'], $op['value']);
    }

    public function testGetMissRecordsNullValue(): void
    {
        [$proxy, $collector] = $this->makeProxy($this->makeInnerCache());

        $value = $proxy->get('missing');

        $this->assertNull($value);
        $op = $collector->getCollected()['operations'][0];
        $this->assertSame('get', $op['operation']);
        $this->assertSame('missing', $op['key']);
        $this->assertFalse($op['hit']);
        $this->assertNull($op['value']);

        // Miss counter must advance.
        $this->assertSame(1, $collector->getCollected()['misses']);
        $this->assertSame(0, $collector->getCollected()['hits']);
    }

    public function testDeleteRecordsOperationWithNullValue(): void
    {
        $inner = $this->makeInnerCache(['user:1' => 'v']);
        [$proxy, $collector] = $this->makeProxy($inner);

        $result = $proxy->delete('user:1');

        $this->assertTrue($result);
        $op = $collector->getCollected()['operations'][0];
        $this->assertSame('delete', $op['operation']);
        $this->assertSame('user:1', $op['key']);
        $this->assertFalse($op['hit']);
        $this->assertNull($op['value']);
    }

    public function testClearRecordsWildcardKey(): void
    {
        $inner = $this->makeInnerCache(['a' => 1, 'b' => 2]);
        [$proxy, $collector] = $this->makeProxy($inner);

        $result = $proxy->clear();

        $this->assertTrue($result);
        $op = $collector->getCollected()['operations'][0];
        $this->assertSame('clear', $op['operation']);
        $this->assertSame('*', $op['key']);
        $this->assertNull($op['value']);
    }

    public function testHasRecordsHitFlag(): void
    {
        $inner = $this->makeInnerCache(['user:1' => 'v']);
        [$proxy, $collector] = $this->makeProxy($inner);

        $this->assertTrue($proxy->has('user:1'));
        $this->assertFalse($proxy->has('missing'));

        $operations = $collector->getCollected()['operations'];
        $this->assertCount(2, $operations);

        $this->assertSame('has', $operations[0]['operation']);
        $this->assertSame('user:1', $operations[0]['key']);
        $this->assertTrue($operations[0]['hit']);

        $this->assertSame('has', $operations[1]['operation']);
        $this->assertSame('missing', $operations[1]['key']);
        $this->assertFalse($operations[1]['hit']);
    }

    public function testGetMultipleEmitsPerKeyOperations(): void
    {
        $inner = $this->makeInnerCache(['a' => 1, 'b' => 2]);
        [$proxy, $collector] = $this->makeProxy($inner);

        $values = $proxy->getMultiple(['a', 'b', 'missing']);

        $materialized = [];
        foreach ($values as $k => $v) {
            $materialized[$k] = $v;
        }
        $this->assertSame(['a' => 1, 'b' => 2, 'missing' => null], $materialized);

        $operations = $collector->getCollected()['operations'];
        $this->assertCount(3, $operations);

        foreach ($operations as $op) {
            $this->assertSame('get', $op['operation']);
            $this->assertSame('default', $op['pool']);
        }

        $this->assertSame('a', $operations[0]['key']);
        $this->assertTrue($operations[0]['hit']);
        $this->assertSame(1, $operations[0]['value']);

        $this->assertSame('b', $operations[1]['key']);
        $this->assertTrue($operations[1]['hit']);
        $this->assertSame(2, $operations[1]['value']);

        $this->assertSame('missing', $operations[2]['key']);
        $this->assertFalse($operations[2]['hit']);
        $this->assertNull($operations[2]['value']);
    }

    public function testSetMultipleEmitsPerKeyOperations(): void
    {
        [$proxy, $collector] = $this->makeProxy($this->makeInnerCache());

        $result = $proxy->setMultiple(['a' => 1, 'b' => 2]);

        $this->assertTrue($result);
        $operations = $collector->getCollected()['operations'];
        $this->assertCount(2, $operations);

        $this->assertSame('set', $operations[0]['operation']);
        $this->assertSame('a', $operations[0]['key']);
        $this->assertSame(1, $operations[0]['value']);

        $this->assertSame('set', $operations[1]['operation']);
        $this->assertSame('b', $operations[1]['key']);
        $this->assertSame(2, $operations[1]['value']);
    }

    public function testDeleteMultipleEmitsPerKeyOperations(): void
    {
        $inner = $this->makeInnerCache(['a' => 1, 'b' => 2]);
        [$proxy, $collector] = $this->makeProxy($inner);

        $result = $proxy->deleteMultiple(['a', 'b']);

        $this->assertTrue($result);
        $operations = $collector->getCollected()['operations'];
        $this->assertCount(2, $operations);

        $this->assertSame('delete', $operations[0]['operation']);
        $this->assertSame('a', $operations[0]['key']);
        $this->assertNull($operations[0]['value']);

        $this->assertSame('delete', $operations[1]['operation']);
        $this->assertSame('b', $operations[1]['key']);
    }

    public function testSetRespectsTtl(): void
    {
        $inner = new class() implements CacheInterface {
            public mixed $receivedTtl = 'not-set';

            public function get(string $key, mixed $default = null): mixed
            {
                return $default;
            }

            public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
            {
                $this->receivedTtl = $ttl;
                return true;
            }

            public function delete(string $key): bool
            {
                return true;
            }

            public function clear(): bool
            {
                return true;
            }

            public function getMultiple(iterable $keys, mixed $default = null): iterable
            {
                return [];
            }

            public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
            {
                return true;
            }

            public function deleteMultiple(iterable $keys): bool
            {
                return true;
            }

            public function has(string $key): bool
            {
                return false;
            }
        };

        [$proxy] = $this->makeProxy($inner);
        $proxy->set('k', 'v', 60);

        $this->assertSame(60, $inner->receivedTtl);
    }
}
