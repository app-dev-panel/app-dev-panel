# Kernel Module

Core engine of ADP. Framework-independent. Manages the debugger lifecycle,
data collectors, storage, PSR proxy system, and object serialization.

## Dependencies

Kernel depends on PSR interfaces and these core infrastructure helpers:

- `yiisoft/strings` — `CombinedRegexp` (hot path in stream proxies), `WildcardPattern` for ignore globs
- `yiisoft/files` — `FileHelper::ensureDirectory` / `removeDirectory` / `isEmptyDirectory` for `FileStorage` + GC
- `yiisoft/var-dumper` — `ClosureExporter` + `VarDumper::create()` at the core of the object dumper
- `symfony/console` — Console event types for `CommandCollector`
- `symfony/var-dumper` — Variable dumper integration
- `guzzlehttp/psr7` — PSR-7 HTTP message implementation

**Core infra policy**: the remaining `yiisoft/*` packages are pure utility libraries with no
framework coupling. Despite the `yiisoft/` vendor prefix, they are not Yii framework
dependencies. Each kept dep carries real logic (regex compilation, recursive directory
cleanup, closure export, pretty dumping) that would cost more to re-implement than it
saves. `yiisoft/json` was dropped in favour of an internal `AppDevPanel\Kernel\Helper\Json`
wrapping native `json_encode/decode` with `JSON_THROW_ON_ERROR`.

Note: `yiisoft/proxy` was removed from Kernel. Container proxying (`ContainerInterfaceProxy`,
`ServiceProxy`, `ServiceMethodProxy`, `ContainerProxyConfig`, `ProxyLogTrait`) and
`VarDumperHandlerInterfaceProxy` now live in the Yii adapter (`libs/Adapter/Yii3`).
`LoggerDecorator` and `VarDumperHandler` remain in Kernel (`DebugServer/`) since they
only depend on `yiisoft/var-dumper` (core infra).

## Package

- Composer: `app-dev-panel/kernel`
- Namespace: `AppDevPanel\Kernel\`
- PHP: 8.4+

## Key Classes

| Class | Purpose |
|-------|---------|
| `Debugger` | Central orchestrator: startup → collect → shutdown → flush |
| `DebuggerIdGenerator` | Generates unique IDs for debug entries |
| `Dumper` | Serializes PHP objects with depth control and circular ref detection |
| `StorageInterface` | Abstraction for persisting debug data |
| `FileStorage` | JSON file-based storage with garbage collection |
| `SqliteStorage` | SQLite-backed storage with WAL journaling and prepared statements |
| `BroadcastingStorage` | Decorator that broadcasts entry-created UDP notifications via `Broadcaster` |
| `StorageFactory` | Resolves a driver name (`file`, `sqlite`) or class name into a `StorageInterface` instance |
| `MemoryStorage` | In-memory storage for testing |
| `CollectorInterface` | Interface all collectors must implement |
| `ServiceRegistryInterface` | Registry for external app service descriptors |
| `FileServiceRegistry` | JSON file-based service registry |
| `ServiceDescriptor` | Value object: service identity, URL, capabilities, heartbeat |
| `Inspector\Primitives` | `Primitives::dump($value, $depth)` — canonical serializer for inspector HTTP responses. Walks arrays, replaces every `Closure` with a `ClosureDescriptor` marker, then delegates to `VarDumper::asPrimitives()` |
| `Inspector\ClosureDescriptor` | `ClosureDescriptor::describe($closure)` — emits `{__closure: true, source, file, startLine, endLine}` for frontend code rendering |
| `Inspector\ClosureDescriptorTrait` | Sugar for adapter `ConfigProvider`s — exposes `self::describeClosure()` delegating to `ClosureDescriptor::describe()` |

## Directory Structure

```
src/
├── Debugger.php                  # Main debugger class
├── DebuggerIdGenerator.php       # ID generation
├── DebuggerIgnoreConfig.php      # Ignore patterns for debugger
├── Dumper.php                    # Object serialization with depth/circular-ref control
├── DumpContext.php               # Context for dump operations
├── FlattenException.php          # Serializable exception representation
├── IgnoreConfig.php              # General ignore configuration
├── ProxyDecoratedCalls.php       # Trait for proxy delegation (__call, __get, __set)
├── StartupContext.php            # Debugger startup context
├── Collector/                    # Collectors and colocated PSR proxies
│   ├── CollectorInterface.php
│   ├── CollectorTrait.php
│   ├── SummaryCollectorInterface.php
│   ├── DuplicateDetectionTrait.php       # Shared utility for duplicate entry detection
│   ├── LogCollector.php
│   ├── LoggerInterfaceProxy.php          # PSR-3 proxy (feeds LogCollector)
│   ├── EventCollector.php
│   ├── EventDispatcherInterfaceProxy.php # PSR-14 proxy (feeds EventCollector)
│   ├── ServiceCollector.php
│   ├── ExceptionCollector.php
│   ├── HttpClientCollector.php
│   ├── HttpClientInterfaceProxy.php      # PSR-18 proxy (feeds HttpClientCollector)
│   ├── SpanProcessorInterfaceProxy.php   # OpenTelemetry proxy (feeds OpenTelemetryCollector)
│   ├── VarDumperCollector.php
│   ├── TimelineCollector.php
│   ├── CacheCollector.php               # Cache operations: get/set/delete (fed by adapter hooks)
│   ├── CacheOperationRecord.php         # Value object for cache operation
│   ├── CodeCoverageCollector.php        # Per-request PHP line coverage (pcov/xdebug)
│   ├── CodeCoverageHelper.php           # Shared coverage utilities (driver detection, processing)
│   ├── DatabaseCollector.php            # SQL queries + transactions (fed by adapter hooks)
│   ├── QueryRecord.php                  # Value object for DB query
│   ├── ElasticsearchCollector.php       # Elasticsearch requests (fed by adapter hooks)
│   ├── ElasticsearchRequestRecord.php   # Immutable DTO for logRequest() pattern
│   ├── MessageRecord.php               # Value object for mailer message
│   ├── MailerCollector.php              # Email messages (fed by adapter hooks)
│   ├── AssetBundleCollector.php         # Asset bundles (fed by adapter hooks)
│   ├── AuthorizationCollector.php       # Auth: user, token, guards, role hierarchy, access decisions, auth events
│   ├── DeprecationCollector.php         # PHP deprecation notices
│   ├── EnvironmentCollector.php         # PHP environment info (extensions, ini settings)
│   ├── MiddlewareCollector.php          # HTTP middleware stack execution
│   ├── OpenTelemetryCollector.php       # OpenTelemetry spans (fed by OTLP ingestion)
│   ├── OtlpTraceParser.php             # OTLP trace data parser
│   ├── SpanRecord.php                  # Value object for OTel span
│   ├── QueueCollector.php               # Queue messages: dispatched/handled/failed (fed by adapter hooks)
│   ├── RedisCollector.php              # Redis commands (fed by adapter hooks)
│   ├── RedisCommandRecord.php          # Value object for Redis command
│   ├── RouterCollector.php              # Route matching data (fed by adapter hooks)
│   ├── TemplateCollector.php           # Template/view rendering with timing, output, params, duplicate detection
│   ├── TranslatorCollector.php         # Translation lookups + missing detection
│   ├── TranslationRecord.php           # Value object for translation lookup
│   ├── ValidatorCollector.php           # Validation results (fed by adapter hooks)
│   ├── Web/
│   │   ├── RequestCollector.php
│   │   └── WebAppInfoCollector.php
│   ├── Console/
│   │   ├── CommandCollector.php
│   │   └── ConsoleAppInfoCollector.php
│   └── Stream/
│       ├── FilesystemStreamCollector.php
│       ├── FilesystemStreamProxy.php
│       ├── HttpStreamCollector.php
│       ├── HttpStreamProxy.php
│       └── StreamProxyTrait.php         # Shared delegation for stream wrappers
├── Service/                      # Service registry for multi-app inspection
│   ├── ServiceDescriptor.php
│   ├── ServiceRegistryInterface.php
│   └── FileServiceRegistry.php
├── Storage/
│   ├── StorageInterface.php
│   ├── StorageFactory.php               # Resolves driver name ('file' | 'sqlite') or class name
│   ├── FileStorage.php                  # JSON file-based (default)
│   ├── SqliteStorage.php                # SQLite (WAL + prepared statements)
│   ├── BroadcastingStorage.php          # Decorator: broadcasts ENTRY_CREATED via UDP
│   ├── FileStorageGarbageCollector.php  # Automatic cleanup of old entries
│   ├── GarbageCollector.php             # GC interface
│   └── MemoryStorage.php
├── Event/                        # Debugger lifecycle events
│   ├── ProxyMethodCallEvent.php
│   └── MethodCallRecord.php
├── Inspector/                    # Shared serialization helpers for the inspector layer
│   ├── ClosureDescriptor.php             # Converts Closure → {__closure: true, source, file, startLine, endLine}
│   ├── ClosureDescriptorTrait.php        # Thin trait wrapper for adapter ConfigProviders
│   └── Primitives.php                    # `Primitives::dump()` — VarDumper::asPrimitives + deep closure walk
├── Helper/                       # Utilities
│   ├── BacktraceIgnoreMatcher.php
│   └── StreamWrapper/
└── DebugServer/                  # UDP socket server for real-time streaming
    ├── Broadcaster.php
    ├── Connection.php
    ├── LoggerDecorator.php       # PSR-3 logger decorator that broadcasts via UDP
    ├── SocketReader.php
    └── VarDumperHandler.php      # VarDumper handler that broadcasts via UDP
```

## Debugger Lifecycle

```
startup() → [proxies feed collectors during request] → shutdown() → flush to storage
```

1. `startup()`: Generate entry ID, check ignore patterns, call `startup()` on all collectors
2. Collection: Proxies intercept PSR calls and feed data to collectors transparently
3. `shutdown()`: Call `shutdown()` on all collectors, serialize data via Dumper
4. Storage: Write summary, data, and objects as three separate entries

## Adding a New Collector

1. Create a class implementing `CollectorInterface`
2. Implement `startup()`, `shutdown()`, `getCollected()` methods
3. Register the collector in the adapter's configuration

## Closure Serialization

Anywhere the Kernel or API serialises data that may contain PHP `Closure` values — storage
dumps (`Dumper` / `DumpContext`), live inspector responses (`Inspector\Primitives`), adapter
ConfigProviders normalising event listeners — the closure is replaced with a structured
descriptor:

```
{"__closure": true, "source": "static fn(...) => ...", "file": "/path", "startLine": 10, "endLine": 12}
```

Generated by `Inspector\ClosureDescriptor::describe()` (wraps `Yiisoft\VarDumper\ClosureExporter`).
The frontend's `JsonRenderer` detects the marker at any nesting level and renders `source` as a
syntax-highlighted PHP code block. Do **not** stringify closures manually — always route through
`ClosureDescriptor::describe()` or `Primitives::dump()`.

## Proxy System

Kernel provides PSR interface proxies that are colocated with their corresponding collectors
in `src/Collector/`. These proxies wrap PSR interfaces and feed intercepted data to collectors.
The application code is completely unaware of the interception.

**Proxies in Kernel** (framework-independent PSR proxies):
- `LoggerInterfaceProxy` (PSR-3) — feeds `LogCollector`
- `EventDispatcherInterfaceProxy` (PSR-14) — feeds `EventCollector`
- `HttpClientInterfaceProxy` (PSR-18) — feeds `HttpClientCollector`
- `SpanProcessorInterfaceProxy` (OpenTelemetry) — feeds `OpenTelemetryCollector` (optional, requires `open-telemetry/sdk`)

**Stream proxies** (PHP stream wrapper interception):
- `FilesystemStreamProxy` — wraps `file://` stream wrapper, feeds `FilesystemStreamCollector`
- `HttpStreamProxy` — wraps `http://` and `https://` stream wrappers, feeds `HttpStreamCollector`
- `StreamProxyTrait` — shared delegation logic for both stream wrappers

**Proxies moved to Yii adapter** (`libs/Adapter/Yii3/src/Proxy/`):
- `ContainerInterfaceProxy` (PSR-11), `ContainerProxyConfig`, `ProxyLogTrait`
- `ServiceProxy`, `ServiceMethodProxy` (generic service interception)
- `VarDumperHandlerInterfaceProxy`

The `ProxyDecoratedCalls` trait remains in Kernel as shared infrastructure for all proxies.

## Service Registry

Tracks external application instances that register with ADP for multi-app inspector proxying.

| Class | Purpose |
|-------|---------|
| `ServiceDescriptor` | Immutable value object: service name, language, inspector URL, capabilities, timestamps |
| `ServiceRegistryInterface` | `register()`, `deregister()`, `heartbeat()`, `resolve()`, `all()` |
| `FileServiceRegistry` | JSON file-based implementation (`.services.json` in storage dir), uses `LOCK_EX` |

`ServiceDescriptor::isOnline()` returns `true` if `lastSeenAt` is within 60 seconds (default timeout).

`ServiceDescriptor::supports(string $capability)` checks if the service declares a given capability, or `*` for all.

## Storage

### Storage Types

| Type | Class | Description |
|------|-------|-------------|
| `TYPE_SUMMARY` | Summary metadata | Timestamp, URL, status, collector names |
| `TYPE_DATA` | Full data | Complete collector payloads |
| `TYPE_OBJECTS` | Object dumps | Serialized objects for deep inspection |

### Drivers and Decorators

| Implementation | Use case |
|----------------|----------|
| `FileStorage` | Default. One JSON file per entry under the storage path; per-entry GC. |
| `SqliteStorage` | Single `.db` file with `entries` table (WAL journaling, `PRAGMA synchronous=NORMAL`, indexed `created_at`). Prepared statements only; unknown storage types throw `InvalidArgumentException` instead of falling through into SQL. |
| `BroadcastingStorage` | Decorator around any concrete storage. Emits `MESSAGE_TYPE_ENTRY_CREATED` over UDP via `Broadcaster` so SSE listeners and CLI tail react in real time. |
| `MemoryStorage` | Unit-test helper. |
| `StorageFactory` | `create(string $driver, array $config, DebuggerIdGenerator $idGenerator)` — `file` / `sqlite` / any FQCN implementing `StorageInterface`. |

### Write Sources

Storage receives data from two sources:
1. **Debugger flush** — PHP collectors write via `StorageInterface` after request/command completion
2. **Ingestion API** — `IngestionController` writes directly to FileStorage for external (non-PHP) apps

FileStorage uses `LOCK_EX` for atomic writes and `flock` for GC mutual exclusion.
