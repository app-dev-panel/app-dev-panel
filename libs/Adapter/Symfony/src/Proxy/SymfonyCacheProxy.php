<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Proxy;

use AppDevPanel\Kernel\Collector\CacheCollector;
use AppDevPanel\Kernel\Collector\CacheOperationRecord;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Decorates PSR-6 CacheItemPoolInterface to feed cache operations to CacheCollector.
 *
 * Intercepts getItem(), getItems(), save(), deleteItem(), hasItem(), and clear()
 * to capture cache hits, misses, and operation timing.
 */
final class SymfonyCacheProxy implements CacheItemPoolInterface
{
    public function __construct(
        private readonly CacheItemPoolInterface $decorated,
        private readonly CacheCollector $collector,
        private readonly string $poolName = 'default',
    ) {}

    public function getItem(string $key): CacheItemInterface
    {
        $start = microtime(true);
        $item = $this->decorated->getItem($key);
        $duration = microtime(true) - $start;

        $this->collector->logCacheOperation(new CacheOperationRecord(
            pool: $this->poolName,
            operation: 'get',
            key: $key,
            hit: $item->isHit(),
            duration: $duration,
            value: $item->isHit() ? $item->get() : null,
        ));

        return $item;
    }

    public function getItems(array $keys = []): iterable
    {
        $start = microtime(true);
        $items = $this->decorated->getItems($keys);
        $duration = microtime(true) - $start;

        $itemsArray = $items instanceof \Traversable ? iterator_to_array($items) : $items;

        foreach ($itemsArray as $key => $item) {
            $this->collector->logCacheOperation(new CacheOperationRecord(
                pool: $this->poolName,
                operation: 'get',
                key: (string) $key,
                hit: $item->isHit(),
                duration: $duration / max(count($itemsArray), 1),
                value: $item->isHit() ? $item->get() : null,
            ));
        }

        return $itemsArray;
    }

    public function hasItem(string $key): bool
    {
        $start = microtime(true);
        $has = $this->decorated->hasItem($key);
        $duration = microtime(true) - $start;

        $this->collector->logCacheOperation(new CacheOperationRecord(
            pool: $this->poolName,
            operation: 'has',
            key: $key,
            hit: $has,
            duration: $duration,
        ));

        return $has;
    }

    public function clear(): bool
    {
        $start = microtime(true);
        $result = $this->decorated->clear();
        $duration = microtime(true) - $start;

        $this->collector->logCacheOperation(new CacheOperationRecord(
            pool: $this->poolName,
            operation: 'clear',
            key: '*',
            duration: $duration,
        ));

        return $result;
    }

    public function deleteItem(string $key): bool
    {
        $start = microtime(true);
        $result = $this->decorated->deleteItem($key);
        $duration = microtime(true) - $start;

        $this->collector->logCacheOperation(new CacheOperationRecord(
            pool: $this->poolName,
            operation: 'delete',
            key: $key,
            duration: $duration,
        ));

        return $result;
    }

    public function deleteItems(array $keys): bool
    {
        $start = microtime(true);
        $result = $this->decorated->deleteItems($keys);
        $duration = microtime(true) - $start;

        foreach ($keys as $key) {
            $this->collector->logCacheOperation(new CacheOperationRecord(
                pool: $this->poolName,
                operation: 'delete',
                key: $key,
                duration: $duration / max(count($keys), 1),
            ));
        }

        return $result;
    }

    public function save(CacheItemInterface $item): bool
    {
        $start = microtime(true);
        $result = $this->decorated->save($item);
        $duration = microtime(true) - $start;

        $this->collector->logCacheOperation(new CacheOperationRecord(
            pool: $this->poolName,
            operation: 'set',
            key: $item->getKey(),
            duration: $duration,
            value: $item->get(),
        ));

        return $result;
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        $start = microtime(true);
        $result = $this->decorated->saveDeferred($item);
        $duration = microtime(true) - $start;

        $this->collector->logCacheOperation(new CacheOperationRecord(
            pool: $this->poolName,
            operation: 'set',
            key: $item->getKey(),
            duration: $duration,
            value: $item->get(),
        ));

        return $result;
    }

    public function commit(): bool
    {
        return $this->decorated->commit();
    }
}
