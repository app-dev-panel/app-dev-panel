<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Tests\Unit\Container;

use AppDevPanel\Adapter\Spiral\Container\CacheProxyInjector;
use AppDevPanel\Kernel\Collector\CacheCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Proxy\Psr16CacheProxy;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use ReflectionProperty;
use Spiral\Core\Container;

final class CacheProxyInjectorTest extends TestCase
{
    public function testProxyDecoratesUnderlyingService(): void
    {
        $container = new Container();
        $binder = $container->getBinder();
        $collector = new CacheCollector(new TimelineCollector());

        $fake = self::arrayCache();

        $container->bindSingleton(CacheInterface::class, $fake);

        $injector = new CacheProxyInjector($container, $binder, $collector);
        $injector->setUnderlying($fake);
        $container->bindSingleton(CacheProxyInjector::class, $injector);

        $binder->bindInjector(CacheInterface::class, CacheProxyInjector::class);

        $resolved = $container->get(CacheInterface::class);

        self::assertInstanceOf(Psr16CacheProxy::class, $resolved);
        $reflection = new ReflectionProperty(Psr16CacheProxy::class, 'inner');
        self::assertSame($fake, $reflection->getValue($resolved));
    }

    public function testFallsBackToDefaultWhenNothingBound(): void
    {
        $container = new Container();
        $binder = $container->getBinder();
        $collector = new CacheCollector(new TimelineCollector());

        $injector = new CacheProxyInjector($container, $binder, $collector);
        $container->bindSingleton(CacheProxyInjector::class, $injector);

        $binder->bindInjector(CacheInterface::class, CacheProxyInjector::class);

        $resolved = $container->get(CacheInterface::class);

        self::assertInstanceOf(Psr16CacheProxy::class, $resolved);
        $reflection = new ReflectionProperty(Psr16CacheProxy::class, 'inner');
        $inner = $reflection->getValue($resolved);
        self::assertInstanceOf(CacheInterface::class, $inner);

        // The fallback is a working in-memory cache.
        self::assertTrue($inner->set('foo', 'bar'));
        self::assertSame('bar', $inner->get('foo'));
    }

    public function testCollectorReceivesIntercept(): void
    {
        $container = new Container();
        $binder = $container->getBinder();
        $collector = new CacheCollector(new TimelineCollector());
        $collector->startup();

        $fake = self::arrayCache();
        $fake->set('hit-key', 'hit-value');

        $injector = new CacheProxyInjector($container, $binder, $collector);
        $injector->setUnderlying($fake);
        $container->bindSingleton(CacheProxyInjector::class, $injector);

        $binder->bindInjector(CacheInterface::class, CacheProxyInjector::class);

        /** @var CacheInterface $cache */
        $cache = $container->get(CacheInterface::class);
        $cache->get('hit-key');
        $cache->get('miss-key');
        $cache->set('new-key', 'new-value');

        $entries = $collector->getCollected();
        $operations = $entries['operations'];
        self::assertCount(3, $operations);
        self::assertSame('get', $operations[0]['operation']);
        self::assertSame('hit-key', $operations[0]['key']);
        self::assertTrue($operations[0]['hit']);
        self::assertSame('get', $operations[1]['operation']);
        self::assertFalse($operations[1]['hit']);
        self::assertSame('set', $operations[2]['operation']);
        self::assertSame('new-key', $operations[2]['key']);
    }

    public function testCollectorIgnoresIntercepWhenInactive(): void
    {
        // Collector NOT started up — must remain inactive but proxy must not throw.
        $container = new Container();
        $binder = $container->getBinder();
        $collector = new CacheCollector(new TimelineCollector());

        $fake = self::arrayCache();
        $injector = new CacheProxyInjector($container, $binder, $collector);
        $injector->setUnderlying($fake);
        $container->bindSingleton(CacheProxyInjector::class, $injector);

        $binder->bindInjector(CacheInterface::class, CacheProxyInjector::class);

        /** @var CacheInterface $cache */
        $cache = $container->get(CacheInterface::class);
        self::assertTrue($cache->set('k', 'v'));
        self::assertSame('v', $cache->get('k'));

        $entries = $collector->getCollected();
        self::assertSame([], $entries['operations']);
    }

    private static function arrayCache(): CacheInterface
    {
        return new class implements CacheInterface {
            /** @var array<string, mixed> */
            private array $store = [];

            public function get(string $key, mixed $default = null): mixed
            {
                return $this->store[$key] ?? $default;
            }

            public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
            {
                $this->store[$key] = $value;
                return true;
            }

            public function delete(string $key): bool
            {
                unset($this->store[$key]);
                return true;
            }

            public function clear(): bool
            {
                $this->store = [];
                return true;
            }

            public function getMultiple(iterable $keys, mixed $default = null): iterable
            {
                $out = [];
                foreach ($keys as $key) {
                    $out[(string) $key] = $this->get((string) $key, $default);
                }
                return $out;
            }

            public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
            {
                foreach ($values as $key => $value) {
                    $this->set((string) $key, $value, $ttl);
                }
                return true;
            }

            public function deleteMultiple(iterable $keys): bool
            {
                foreach ($keys as $key) {
                    $this->delete((string) $key);
                }
                return true;
            }

            public function has(string $key): bool
            {
                return array_key_exists($key, $this->store);
            }
        };
    }
}
