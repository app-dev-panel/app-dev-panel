# Kernel Module

Core engine of ADP. Framework-independent. Manages the debugger lifecycle,
data collectors, storage, PSR proxy system, and object serialization.

## Dependencies

Kernel depends on PSR interfaces and these framework-independent helpers:

- `yiisoft/strings` — String manipulation utilities
- `yiisoft/json` — JSON encode/decode with error handling
- `yiisoft/var-dumper` — Variable dumping/serialization
- `symfony/console` — Console event types for `CommandCollector`
- `symfony/var-dumper` — Variable dumper integration
- `guzzlehttp/psr7` — PSR-7 HTTP message implementation

Note: `yiisoft/proxy` was removed from Kernel. Container proxying (`ContainerInterfaceProxy`,
`ServiceProxy`, `ServiceMethodProxy`, `ContainerProxyConfig`, `ProxyLogTrait`) and
framework-specific helpers (`VarDumperHandlerInterfaceProxy`, `VarDumperHandler`, `LoggerDecorator`)
now live in the Yii adapter (`libs/Adapter/Yiisoft`).

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
| `MemoryStorage` | In-memory storage for testing |
| `CollectorInterface` | Interface all collectors must implement |
| `ServiceRegistryInterface` | Registry for external app service descriptors |
| `FileServiceRegistry` | JSON file-based service registry |
| `ServiceDescriptor` | Value object: service identity, URL, capabilities, heartbeat |

## Directory Structure

```
src/
├── Debugger.php                  # Main debugger class
├── DebuggerIdGenerator.php       # ID generation
├── Dumper.php                    # Object serialization with depth/circular-ref control
├── FlattenException.php          # Serializable exception representation
├── ProxyDecoratedCalls.php       # Trait for proxy delegation (__call, __get, __set)
├── StartupContext.php            # Debugger startup context
├── Collector/                    # Collectors and colocated PSR proxies
│   ├── CollectorInterface.php
│   ├── CollectorTrait.php
│   ├── SummaryCollectorInterface.php
│   ├── LogCollector.php
│   ├── LoggerInterfaceProxy.php          # PSR-3 proxy (feeds LogCollector)
│   ├── EventCollector.php
│   ├── EventDispatcherInterfaceProxy.php # PSR-14 proxy (feeds EventCollector)
│   ├── ServiceCollector.php
│   ├── ExceptionCollector.php
│   ├── HttpClientCollector.php
│   ├── HttpClientInterfaceProxy.php      # PSR-18 proxy (feeds HttpClientCollector)
│   ├── VarDumperCollector.php
│   ├── TimelineCollector.php
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
│       └── HttpStreamProxy.php
├── Service/                      # Service registry for multi-app inspection
│   ├── ServiceDescriptor.php
│   ├── ServiceRegistryInterface.php
│   └── FileServiceRegistry.php
├── Storage/
│   ├── StorageInterface.php
│   ├── FileStorage.php
│   └── MemoryStorage.php
├── Event/                        # Debugger lifecycle events
│   └── ProxyMethodCallEvent.php
├── Helper/                       # Utilities
│   ├── BacktraceIgnoreMatcher.php
│   └── StreamWrapper/
└── DebugServer/                  # UDP socket server for real-time streaming
    ├── Broadcaster.php
    ├── Connection.php
    └── SocketReader.php
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

## Proxy System

Kernel provides PSR interface proxies that are colocated with their corresponding collectors
in `src/Collector/`. These proxies wrap PSR interfaces and feed intercepted data to collectors.
The application code is completely unaware of the interception.

**Proxies in Kernel** (framework-independent PSR proxies):
- `LoggerInterfaceProxy` (PSR-3) — feeds `LogCollector`
- `EventDispatcherInterfaceProxy` (PSR-14) — feeds `EventCollector`
- `HttpClientInterfaceProxy` (PSR-18) — feeds `HttpClientCollector`

**Proxies moved to Yii adapter** (`libs/Adapter/Yiisoft/src/Proxy/`):
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

### Write Sources

Storage receives data from two sources:
1. **Debugger flush** — PHP collectors write via `StorageInterface` after request/command completion
2. **Ingestion API** — `IngestionController` writes directly to FileStorage for external (non-PHP) apps

FileStorage uses `LOCK_EX` for atomic writes and `flock` for GC mutual exclusion.
