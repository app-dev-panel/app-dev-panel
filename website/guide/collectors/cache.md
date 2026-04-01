---
title: Cache Collector
---

# Cache Collector

Captures cache operations (get, set, delete) with hit/miss rates, timing, and per-pool breakdowns.

![Cache Collector panel](/images/collectors/cache.png)

## What It Captures

| Field | Description |
|-------|-------------|
| `pool` | Cache pool name (e.g., `default`, `sessions`) |
| `operation` | Operation type (`get`, `set`, `delete`, `has`, `clear`) |
| `key` | Cache key |
| `hit` | Whether the operation was a cache hit |
| `duration` | Operation execution time in seconds |
| `value` | Cached value (for get/set operations) |

## Data Schema

```json
{
    "operations": [
        {
            "pool": "default",
            "operation": "get",
            "key": "user:42",
            "hit": true,
            "duration": 0.0003,
            "value": {"name": "John"}
        }
    ],
    "hits": 8,
    "misses": 2,
    "totalOperations": 10
}
```

**Summary** (shown in debug entry list):

```json
{
    "cache": {
        "hits": 8,
        "misses": 2,
        "totalOperations": 10
    }
}
```

## Contract

```php
use AppDevPanel\Kernel\Collector\CacheCollector;
use AppDevPanel\Kernel\Collector\CacheOperationRecord;

$collector->logCacheOperation(new CacheOperationRecord(
    pool: 'default',
    operation: 'get',
    key: 'user:42',
    hit: true,
    duration: 0.0003,
    value: ['name' => 'John'],
));
```

::: info
<class>\AppDevPanel\Kernel\Collector\CacheCollector</class> implements <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class> and depends on <class>\AppDevPanel\Kernel\Collector\TimelineCollector</class>.
:::

## How It Works

Framework adapters intercept PSR-16 <class>Psr\SimpleCache\CacheInterface</class> operations through the `CacheInterfaceProxy` decorator. Every `get()`, `set()`, `delete()`, `has()`, and `clear()` call is automatically captured.

## Debug Panel

- **Hit rate summary** — total operations, hits, misses with percentage
- **Per-pool breakdown** — statistics grouped by cache pool when multiple pools are used
- **Operation list** — filterable list with operation type, key, hit/miss status, and timing
- **Color coding** — hits (green), misses (orange), deletes (yellow)
- **Value preview** — expandable cached values for get/set operations
