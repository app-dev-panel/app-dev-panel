<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\EventListener;

use AppDevPanel\Kernel\Collector\CacheCollector;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgotten;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Listens for Laravel cache events and feeds the CacheCollector.
 *
 * Laravel fires these events from CacheManager when event dispatching is enabled.
 */
final class CacheListener
{
    /** @var \Closure(): CacheCollector */
    private \Closure $collectorFactory;

    /**
     * @param \Closure(): CacheCollector $collectorFactory
     */
    public function __construct(\Closure $collectorFactory)
    {
        $this->collectorFactory = $collectorFactory;
    }

    public function register(Dispatcher $events): void
    {
        $events->listen(CacheHit::class, function (CacheHit $event): void {
            ($this->collectorFactory)()->logCacheOperation(
                pool: $event->storeName ?? 'default',
                operation: 'get',
                key: $event->key,
                hit: true,
                duration: 0,
                value: $event->value,
            );
        });

        $events->listen(CacheMissed::class, function (CacheMissed $event): void {
            ($this->collectorFactory)()->logCacheOperation(
                pool: $event->storeName ?? 'default',
                operation: 'get',
                key: $event->key,
                hit: false,
                duration: 0,
            );
        });

        $events->listen(KeyWritten::class, function (KeyWritten $event): void {
            ($this->collectorFactory)()->logCacheOperation(
                pool: $event->storeName ?? 'default',
                operation: 'set',
                key: $event->key,
                hit: false,
                duration: 0,
                value: $event->value,
            );
        });

        $events->listen(KeyForgotten::class, function (KeyForgotten $event): void {
            ($this->collectorFactory)()->logCacheOperation(
                pool: $event->storeName ?? 'default',
                operation: 'delete',
                key: $event->key,
                hit: false,
                duration: 0,
            );
        });
    }
}
