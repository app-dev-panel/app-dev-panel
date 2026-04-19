<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Proxy;

use AppDevPanel\Kernel\Collector\CacheCollector;
use AppDevPanel\Kernel\Collector\CacheOperationRecord;
use DateInterval;
use Psr\SimpleCache\CacheInterface;

/**
 * PSR-16 (SimpleCache) decorator that feeds every cache operation into
 * {@see CacheCollector}.
 *
 * Framework-neutral: works with any `Psr\SimpleCache\CacheInterface`
 * implementation (yiisoft/cache, Symfony cache PSR-16 bridge, Laravel's
 * PSR-16 wrapper, custom array/memory drivers, etc.). Adapters wire it in
 * by replacing the inner binding with the proxy.
 *
 * Each public method delegates to the wrapped implementation, measures
 * duration with `microtime(true)`, and pushes a {@see CacheOperationRecord}
 * to the collector. Multi-methods produce one record per key with the same
 * operation name.
 */
final class Psr16CacheProxy implements CacheInterface
{
    public function __construct(
        private readonly CacheInterface $inner,
        private readonly CacheCollector $collector,
        private readonly string $pool = 'default',
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $start = microtime(true);
        $value = $this->inner->get($key, $default);
        $duration = microtime(true) - $start;

        $hit = $value !== null;

        $this->collector->logCacheOperation(new CacheOperationRecord(
            pool: $this->pool,
            operation: 'get',
            key: $key,
            hit: $hit,
            duration: $duration,
            value: $hit ? $value : null,
        ));

        return $value;
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $start = microtime(true);
        $result = $this->inner->set($key, $value, $ttl);
        $duration = microtime(true) - $start;

        $this->collector->logCacheOperation(new CacheOperationRecord(
            pool: $this->pool,
            operation: 'set',
            key: $key,
            duration: $duration,
            value: $value,
        ));

        return $result;
    }

    public function delete(string $key): bool
    {
        $start = microtime(true);
        $result = $this->inner->delete($key);
        $duration = microtime(true) - $start;

        $this->collector->logCacheOperation(new CacheOperationRecord(
            pool: $this->pool,
            operation: 'delete',
            key: $key,
            duration: $duration,
        ));

        return $result;
    }

    public function clear(): bool
    {
        $start = microtime(true);
        $result = $this->inner->clear();
        $duration = microtime(true) - $start;

        $this->collector->logCacheOperation(new CacheOperationRecord(
            pool: $this->pool,
            operation: 'clear',
            key: '*',
            duration: $duration,
        ));

        return $result;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $start = microtime(true);
        $values = $this->inner->getMultiple($keys, $default);
        // Materialize so we can iterate while still returning a useful result.
        $materialized = [];
        foreach ($values as $k => $v) {
            $materialized[$k] = $v;
        }
        $duration = microtime(true) - $start;

        foreach ($materialized as $key => $value) {
            $hit = $value !== null;
            $this->collector->logCacheOperation(new CacheOperationRecord(
                pool: $this->pool,
                operation: 'get',
                key: $key,
                hit: $hit,
                duration: $duration,
                value: $hit ? $value : null,
            ));
        }

        return $materialized;
    }

    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        // Materialize once — $values may be a generator and the inner impl will consume it.
        $materialized = [];
        foreach ($values as $k => $v) {
            $materialized[$k] = $v;
        }

        $start = microtime(true);
        $result = $this->inner->setMultiple($materialized, $ttl);
        $duration = microtime(true) - $start;

        foreach ($materialized as $key => $value) {
            $this->collector->logCacheOperation(new CacheOperationRecord(
                pool: $this->pool,
                operation: 'set',
                key: (string) $key,
                duration: $duration,
                value: $value,
            ));
        }

        return $result;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $materialized = [];
        foreach ($keys as $k) {
            $materialized[] = $k;
        }

        $start = microtime(true);
        $result = $this->inner->deleteMultiple($materialized);
        $duration = microtime(true) - $start;

        foreach ($materialized as $key) {
            $this->collector->logCacheOperation(new CacheOperationRecord(
                pool: $this->pool,
                operation: 'delete',
                key: $key,
                duration: $duration,
            ));
        }

        return $result;
    }

    public function has(string $key): bool
    {
        $start = microtime(true);
        $result = $this->inner->has($key);
        $duration = microtime(true) - $start;

        $this->collector->logCacheOperation(new CacheOperationRecord(
            pool: $this->pool,
            operation: 'has',
            key: $key,
            hit: $result,
            duration: $duration,
        ));

        return $result;
    }
}
