---
title: Cache Inspector
---

# Cache Inspector

View, delete, and clear PSR-16 cache entries.

## What It Shows

| Feature | Description |
|---------|-------------|
| View | Retrieve a cached value by key |
| Delete | Remove a specific cache key |
| Clear | Flush the entire cache |

## How To Use

Enter a cache key to view its stored value. The inspector displays the deserialized value with type information.

## API Endpoints

| Method | Path | Description |
|--------|------|-------------|
| GET | `/inspect/api/cache?key=my_cache_key` | View cached value |
| DELETE | `/inspect/api/cache?key=my_cache_key` | Delete a cache key |
| POST | `/inspect/api/cache/clear` | Clear entire cache |

## Requirements

Requires a PSR-16 <class>Psr\SimpleCache\CacheInterface</class> implementation registered in the DI container.

::: warning
The **Clear** action is destructive — it removes all cache entries. Use with care in production.
:::
