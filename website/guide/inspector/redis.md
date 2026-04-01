---
title: Redis Inspector
---

# Redis Inspector

Browse Redis keys, view values by type, manage keys, and monitor server status.

## Features

| Feature | Description |
|---------|-------------|
| Ping | Test Redis server connection |
| Server info | Full `INFO` output (memory, clients, stats, etc.) |
| DB size | Number of keys in the current database |
| Key browser | Browse keys with pattern matching via `SCAN` |
| Value viewer | Type-aware display (string, list, set, zset, hash, stream) |
| Delete | Remove individual keys |
| Flush DB | Clear all keys in the current database |

## Key Browser

Browse keys using glob patterns (default: `*`). Uses `SCAN` for safe iteration (no blocking). Supports pagination with cursor and limit.

## Type-Aware Value Display

The inspector automatically detects the Redis data type and displays values appropriately:
- **String** — raw value
- **List** — ordered elements
- **Set** — unique members
- **Sorted Set** — members with scores
- **Hash** — field-value pairs
- **Stream** — stream entries

## API Endpoints

| Method | Path | Description |
|--------|------|-------------|
| GET | `/inspect/api/redis/ping` | Test connection |
| GET | `/inspect/api/redis/info?section=memory` | Server info (optional section filter) |
| GET | `/inspect/api/redis/db-size` | Key count |
| GET | `/inspect/api/redis/keys?pattern=user:*&limit=100&cursor=0` | Browse keys |
| GET | `/inspect/api/redis/get?key=user:1` | Get value (type-aware) with TTL |
| DELETE | `/inspect/api/redis/delete?key=user:1` | Delete a key |
| POST | `/inspect/api/redis/flush-db` | Flush current database |

## Requirements

Requires the **phpredis** extension (`\Redis` class) registered in the DI container.

::: warning
**Flush DB** is destructive — it removes all keys in the current database. Use with extreme care.
:::
