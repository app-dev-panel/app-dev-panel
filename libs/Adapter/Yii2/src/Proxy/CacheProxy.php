<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Proxy;

use AppDevPanel\Kernel\Collector\CacheCollector;
use AppDevPanel\Kernel\Collector\CacheOperationRecord;
use yii\caching\Cache;
use yii\caching\CacheInterface;

/**
 * Decorates a Yii 2 cache component to feed operations into CacheCollector.
 *
 * Extends {@see Cache} so it satisfies both `yii\caching\CacheInterface` and
 * type hints like `Cache $cache`. All public cache operations are overridden to
 * delegate to the wrapped {@see CacheInterface} instance and to emit a
 * {@see CacheOperationRecord} for each call. The abstract `*Value()` methods
 * throw — this class must not be used as a raw cache backend.
 *
 * Registered by `Module::registerCacheProfiling()` when the application has a
 * `cache` component configured.
 */
final class CacheProxy extends Cache
{
    public function __construct(
        private readonly CacheInterface $inner,
        private readonly CacheCollector $collector,
        private readonly string $poolName = 'default',
        array $config = [],
    ) {
        parent::__construct($config);
    }

    public function init(): void
    {
        // Skip parent::init() — the inner cache is already initialized and
        // we must not mutate its state here.
    }

    public function getInner(): CacheInterface
    {
        return $this->inner;
    }

    public function buildKey($key): string
    {
        return $this->inner->buildKey($key);
    }

    public function get($key): mixed
    {
        $start = microtime(true);
        $value = $this->inner->get($key);
        $duration = microtime(true) - $start;

        $this->collector->logCacheOperation(new CacheOperationRecord(
            pool: $this->poolName,
            operation: 'get',
            key: self::stringifyKey($key),
            hit: $value !== false,
            duration: $duration,
            value: $value === false ? null : $value,
        ));

        return $value;
    }

    public function exists($key): bool
    {
        $start = microtime(true);
        $result = $this->inner->exists($key);
        $duration = microtime(true) - $start;

        $this->collector->logCacheOperation(new CacheOperationRecord(
            pool: $this->poolName,
            operation: 'exists',
            key: self::stringifyKey($key),
            hit: $result,
            duration: $duration,
        ));

        return $result;
    }

    public function multiGet($keys): array
    {
        $start = microtime(true);
        $values = $this->inner->multiGet($keys);
        $duration = microtime(true) - $start;

        $count = count($keys) > 0 ? count($keys) : 1;
        $perCallDuration = $duration / $count;

        foreach ($keys as $key) {
            $value = $values[$key] ?? false;
            $this->collector->logCacheOperation(new CacheOperationRecord(
                pool: $this->poolName,
                operation: 'get',
                key: self::stringifyKey($key),
                hit: $value !== false,
                duration: $perCallDuration,
                value: $value === false ? null : $value,
            ));
        }

        return $values;
    }

    public function set($key, $value, $duration = null, $dependency = null): bool
    {
        $start = microtime(true);
        $result = $this->inner->set($key, $value, $duration, $dependency);
        $elapsed = microtime(true) - $start;

        $this->collector->logCacheOperation(new CacheOperationRecord(
            pool: $this->poolName,
            operation: 'set',
            key: self::stringifyKey($key),
            duration: $elapsed,
            value: $value,
        ));

        return $result;
    }

    public function multiSet($items, $duration = null, $dependency = null): array
    {
        $start = microtime(true);
        $result = $this->inner->multiSet($items, $duration, $dependency);
        $elapsed = microtime(true) - $start;

        $count = count($items) > 0 ? count($items) : 1;
        $perCallDuration = $elapsed / $count;

        foreach ($items as $key => $value) {
            $this->collector->logCacheOperation(new CacheOperationRecord(
                pool: $this->poolName,
                operation: 'set',
                key: self::stringifyKey($key),
                duration: $perCallDuration,
                value: $value,
            ));
        }

        return is_array($result) ? $result : [];
    }

    public function add($key, $value, $duration = 0, $dependency = null): bool
    {
        $start = microtime(true);
        $result = $this->inner->add($key, $value, $duration, $dependency);
        $elapsed = microtime(true) - $start;

        $this->collector->logCacheOperation(new CacheOperationRecord(
            pool: $this->poolName,
            operation: 'set',
            key: self::stringifyKey($key),
            duration: $elapsed,
            value: $value,
        ));

        return $result;
    }

    public function multiAdd($items, $duration = 0, $dependency = null): array
    {
        $start = microtime(true);
        $result = $this->inner->multiAdd($items, $duration, $dependency);
        $elapsed = microtime(true) - $start;

        $count = count($items) > 0 ? count($items) : 1;
        $perCallDuration = $elapsed / $count;

        foreach ($items as $key => $value) {
            $this->collector->logCacheOperation(new CacheOperationRecord(
                pool: $this->poolName,
                operation: 'set',
                key: self::stringifyKey($key),
                duration: $perCallDuration,
                value: $value,
            ));
        }

        return is_array($result) ? $result : [];
    }

    public function delete($key): bool
    {
        $start = microtime(true);
        $result = $this->inner->delete($key);
        $elapsed = microtime(true) - $start;

        $this->collector->logCacheOperation(new CacheOperationRecord(
            pool: $this->poolName,
            operation: 'delete',
            key: self::stringifyKey($key),
            duration: $elapsed,
        ));

        return $result;
    }

    public function flush(): bool
    {
        $start = microtime(true);
        $result = $this->inner->flush();
        $elapsed = microtime(true) - $start;

        $this->collector->logCacheOperation(new CacheOperationRecord(
            pool: $this->poolName,
            operation: 'clear',
            key: '*',
            duration: $elapsed,
        ));

        return $result;
    }

    /**
     * @template TResult
     *
     * @param callable(self): TResult $callable
     *
     * @return TResult
     */
    public function getOrSet($key, $callable, $duration = null, $dependency = null): mixed
    {
        $value = $this->get($key);
        if ($value !== false) {
            /** @var TResult */
            return $value;
        }

        $value = \call_user_func($callable, $this);
        if ($value !== false) {
            $this->set($key, $value, $duration, $dependency);
        }

        /** @var TResult */
        return $value;
    }

    protected function getValue($key): mixed
    {
        throw new \RuntimeException('CacheProxy delegates to an inner cache; getValue() must not be called.');
    }

    protected function setValue($key, $value, $duration): bool
    {
        throw new \RuntimeException('CacheProxy delegates to an inner cache; setValue() must not be called.');
    }

    protected function addValue($key, $value, $duration): bool
    {
        throw new \RuntimeException('CacheProxy delegates to an inner cache; addValue() must not be called.');
    }

    protected function deleteValue($key): bool
    {
        throw new \RuntimeException('CacheProxy delegates to an inner cache; deleteValue() must not be called.');
    }

    protected function flushValues(): bool
    {
        throw new \RuntimeException('CacheProxy delegates to an inner cache; flushValues() must not be called.');
    }

    /**
     * Cache keys may be strings or nested arrays — normalise to a loggable string.
     */
    private static function stringifyKey(mixed $key): string
    {
        if (is_string($key)) {
            return $key;
        }

        try {
            return \json_encode($key, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return md5(serialize($key));
        }
    }
}
