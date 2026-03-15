# Kernel Module

Core engine of ADP. Framework-independent. Manages the debugger lifecycle,
data collectors, storage, proxy system, and object serialization.

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
├── Collector/                    # All collector implementations
│   ├── CollectorInterface.php
│   ├── LogCollector.php
│   ├── EventCollector.php
│   ├── ServiceCollector.php
│   ├── RequestCollector.php
│   ├── ExceptionCollector.php
│   ├── HttpClientCollector.php
│   ├── VarDumperCollector.php
│   ├── TimelineCollector.php
│   ├── CommandCollector.php
│   ├── WebAppInfoCollector.php
│   ├── ConsoleAppInfoCollector.php
│   ├── FilesystemStreamCollector.php
│   └── HttpStreamCollector.php
├── Proxy/                        # PSR interface proxies
│   ├── LoggerInterfaceProxy.php
│   ├── EventDispatcherInterfaceProxy.php
│   ├── HttpClientInterfaceProxy.php
│   ├── ContainerInterfaceProxy.php
│   ├── VarDumperHandlerInterfaceProxy.php
│   ├── ServiceProxy.php
│   └── ServiceMethodProxy.php
├── Service/                      # Service registry for multi-app inspection
│   ├── ServiceDescriptor.php
│   ├── ServiceRegistryInterface.php
│   └── FileServiceRegistry.php
├── Storage/
│   ├── StorageInterface.php
│   ├── FileStorage.php
│   └── MemoryStorage.php
├── Event/                        # Debugger lifecycle events
├── Helper/                       # Utilities (Dumper, etc.)
└── DebugServer/                  # UDP socket server for real-time streaming
    └── Connection.php
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

Proxies wrap PSR interfaces (PSR-3 Logger, PSR-14 EventDispatcher, PSR-18 HttpClient, PSR-11 Container)
and feed intercepted data to collectors. The application code is completely unaware of the interception.

`ServiceProxy` / `ServiceMethodProxy` provide generic interception for any service method.

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
