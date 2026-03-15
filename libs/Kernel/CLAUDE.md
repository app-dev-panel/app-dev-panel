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
| `CollectorTrait` | Default `startup()`/`shutdown()` implementation |
| `SummaryCollectorInterface` | Collectors that contribute to entry summary |

## Directory Structure

```
src/
├── Debugger.php                  # Main debugger class
├── DebuggerIdGenerator.php       # ID generation
├── Collector/                    # All collector implementations
│   ├── CollectorInterface.php
│   ├── CollectorTrait.php
│   ├── SummaryCollectorInterface.php
│   ├── LogCollector.php
│   ├── EventCollector.php
│   ├── ServiceCollector.php
│   ├── ExceptionCollector.php
│   ├── HttpClientCollector.php
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
│       └── HttpStreamCollector.php
├── Proxy/                        # PSR interface proxies
│   ├── LoggerInterfaceProxy.php
│   ├── EventDispatcherInterfaceProxy.php
│   ├── HttpClientInterfaceProxy.php
│   ├── ContainerInterfaceProxy.php
│   ├── VarDumperHandlerInterfaceProxy.php
│   ├── ServiceProxy.php
│   └── ServiceMethodProxy.php
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

## External Collectors

Some collectors live in framework-specific packages (not in ADP Kernel):

| Collector | Package | Purpose |
|-----------|---------|---------|
| `DatabaseCollector` | `yiisoft/db` | SQL query interception |
| `MiddlewareCollector` | `yiisoft/yii-debug` | PSR-15 middleware stack |
| `MailerCollector` | `yiisoft/mailer` | Email interception |

The frontend already has panels for these. For framework-agnostic ADP, these need Kernel-native implementations.

## Proxy System

Proxies wrap PSR interfaces (PSR-3 Logger, PSR-14 EventDispatcher, PSR-18 HttpClient, PSR-11 Container)
and feed intercepted data to collectors. The application code is completely unaware of the interception.

`ServiceProxy` / `ServiceMethodProxy` provide generic interception for any service method.

## Storage Types

| Type | Class | Description |
|------|-------|-------------|
| `TYPE_SUMMARY` | Summary metadata | Timestamp, URL, status, collector names |
| `TYPE_DATA` | Full data | Complete collector payloads |
| `TYPE_OBJECTS` | Object dumps | Serialized objects for deep inspection |
