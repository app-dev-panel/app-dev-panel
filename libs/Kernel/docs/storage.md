# Storage

Persists and retrieves debug data.

## StorageInterface

```php
interface StorageInterface
{
    public function addCollector(CollectorInterface $collector): void;
    public function getData(): array;
    public function read(string $type, ?string $id): array;
    public function flush(): void;
    public function clear(): void;
}
```

Three data types per debug entry:

| Constant | Content |
|----------|---------|
| `TYPE_SUMMARY` | Lightweight metadata: ID, collector names, summary from `SummaryCollectorInterface` |
| `TYPE_DATA` | Full collector payloads serialized via `Dumper::asJson(30)` |
| `TYPE_OBJECTS` | Object map via `Dumper::asJsonObjectsMap(30)` |

## FileStorage

Default implementation. Writes JSON files. Uses `json_decode` for reading, `Dumper` for writing.

### Directory Layout

```
{path}/
└── {YYYY-MM-DD}/
    └── {entry-id}/
        ├── summary.json
        ├── data.json
        └── objects.json
```

### Constructor

```php
new FileStorage(string $path, DebuggerIdGenerator $idGenerator, array $excludedClasses = [])
```

`$excludedClasses` are passed to `Dumper` to skip serialization of certain classes.

### Garbage Collection

On each `flush()`, checks total entry count against `historySize` (default 50, configurable via `setHistorySize()`). Removes oldest entries and cleans up empty date directories.

### Reading

`read(string $type, ?string $id)` uses `glob()` to find matching JSON files across all date directories. Results sorted by file modification time.

## MemoryStorage

In-memory implementation for testing. Data lost when process ends.

## Implementing Custom Storage

1. Implement `StorageInterface`
2. Handle the three data types (`TYPE_SUMMARY`, `TYPE_DATA`, `TYPE_OBJECTS`)
3. Register in the adapter's DI configuration
