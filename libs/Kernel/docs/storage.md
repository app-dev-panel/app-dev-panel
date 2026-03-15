# Storage

The storage layer is responsible for persisting and retrieving debug data.

## StorageInterface

```php
interface StorageInterface
{
    public function read(string $type, ?string $id = null): array;
    public function write(string $type, string $id, array $data): void;
    public function clear(): void;
}
```

Three data types are stored per debug entry:

| Constant | Content | Typical Size |
|----------|---------|--------------|
| `TYPE_SUMMARY` | Lightweight metadata (timestamp, URL, status, collector list) | Small |
| `TYPE_DATA` | Full collector payloads | Medium-Large |
| `TYPE_OBJECTS` | Serialized object dumps for deep inspection | Large |

## FileStorage

Default storage implementation. Writes JSON files organized by date and entry ID.

### Directory Layout

```
{storage_path}/
└── {YYYY-MM-DD}/
    └── {entry-id}/
        ├── summary.json
        ├── data.json
        └── objects.json
```

### Garbage Collection

FileStorage implements automatic cleanup. When writing a new entry, it checks the total
number of stored entries and removes the oldest ones when the configured `historySize` is exceeded.

### Configuration

```php
'storage' => [
    'path' => '@runtime/debug',  // Storage directory (uses framework aliases)
    'historySize' => 50,         // Max entries to keep
],
```

## MemoryStorage

Lightweight in-memory implementation for testing. Data is lost when the process ends.

```php
$storage = new MemoryStorage();
// Use in tests where persistence is not needed
```

## Implementing Custom Storage

To implement a custom storage backend (Redis, database, etc.):

1. Implement `StorageInterface`
2. Register your implementation in the adapter's DI configuration
3. Handle the three data types (`TYPE_SUMMARY`, `TYPE_DATA`, `TYPE_OBJECTS`)

Example use cases:
- Redis storage for distributed debugging across multiple servers
- Database storage for long-term debug data retention
- S3/cloud storage for serverless environments
