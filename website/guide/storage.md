---
title: Storage
---

# Storage

ADP persists debug data through a <class>AppDevPanel\Kernel\Storage\StorageInterface</class> abstraction. The default implementation, <class>AppDevPanel\Kernel\Storage\FileStorage</class>, writes JSON files to disk.

## StorageInterface

```php
interface StorageInterface
{
    public function addCollector(CollectorInterface $collector): void;
    public function getData(): array;
    public function read(string $type, ?string $id = null): array;
    public function write(string $id, array $summary, array $data, array $objects): void;
    public function flush(): void;
    public function clear(): void;
}
```

### Data Types

Each debug entry is stored as three separate pieces:

| Type | Constant | Contents |
|------|----------|----------|
| Summary | `TYPE_SUMMARY` | Timestamp, URL, HTTP status, collector names |
| Data | `TYPE_DATA` | Full collector payloads |
| Objects | `TYPE_OBJECTS` | Serialized PHP objects for deep inspection |

This separation allows the frontend to load summaries quickly without fetching full data.

## FileStorage

<class>AppDevPanel\Kernel\Storage\FileStorage</class> is the default production implementation. It writes JSON files to a configurable directory.

**Key behaviors:**

- Each debug entry produces three JSON files (summary, data, objects)
- Uses `LOCK_EX` for atomic writes to prevent corruption
- Uses `flock` for garbage collection mutual exclusion
- Supports configurable entry limit with automatic GC of old entries

### Directory Structure

```
debug-data/
├── 000001.summary.json
├── 000001.data.json
├── 000001.objects.json
├── 000002.summary.json
├── 000002.data.json
├── 000002.objects.json
└── ...
```

## MemoryStorage

<class>AppDevPanel\Kernel\Storage\MemoryStorage</class> is an in-memory implementation used exclusively for testing. It stores all data in PHP arrays with no disk I/O.

## Write Sources

Storage receives data from two sources:

1. **Debugger flush** -- After a request or console command completes, the <class>AppDevPanel\Kernel\Debugger</class> calls `flush()` on the storage, which serializes all collector data.
2. **Ingestion API** -- The <class>AppDevPanel\Api\Ingestion\Controller\IngestionController</class> calls `write()` directly, allowing external (non-PHP) applications to send debug data via HTTP.

## Extending Storage

To create a custom storage backend (e.g., Redis, database), implement <class>AppDevPanel\Kernel\Storage\StorageInterface</class> and register it in your DI container. All six methods must be implemented. The `read()` method must support filtering by `$type` and optionally by `$id`.
