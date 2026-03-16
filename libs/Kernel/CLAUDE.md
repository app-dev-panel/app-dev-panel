# Kernel Module

Core engine of ADP. Framework-independent. Manages the debugger lifecycle,
data collectors, storage, proxy system, and object serialization.

## Package

- Composer: `app-dev-panel/kernel`
- Namespace: `AppDevPanel\Kernel\`
- PHP: 8.2+

## Dependencies

| Package | Purpose |
|---------|---------|
| `guzzlehttp/psr7` | PSR-7 message utilities (serialization in `RequestCollector`) |
| `psr/container` | PSR-11 `ContainerInterface` for `ContainerInterfaceProxy` |
| `psr/event-dispatcher` | PSR-14 `EventDispatcherInterface` for `EventDispatcherInterfaceProxy` |
| `psr/http-client` | PSR-18 `ClientInterface` for `HttpClientInterfaceProxy` |
| `psr/http-message` | PSR-7 message interfaces |
| `psr/log` | PSR-3 `LoggerInterface` for `LoggerInterfaceProxy` |
| `symfony/console` | Console event types in `CommandCollector`, `BufferedOutput` |
| `symfony/var-dumper` | Not used at runtime (legacy dep, pending removal) |

No yiisoft/* dependencies.

## Key Classes

| Class | Purpose |
|-------|---------|
| `Debugger` | Central orchestrator: startup -> collect -> shutdown -> flush |
| `DebuggerIdGenerator` | Generates unique IDs for debug entries |
| `Dumper` | Serializes PHP values to JSON with depth control and circular ref detection |
| `DumpHandlerInterface` | Framework-agnostic interface for variable dump output |
| `FlattenException` | Serializable exception representation |
| `ProxyDecoratedCalls` | Trait for decorated proxy call tracking |
| `StorageInterface` | Abstraction for persisting debug data |
| `FileStorage` | JSON file-based storage with garbage collection |
| `MemoryStorage` | In-memory storage for testing |
| `CollectorInterface` | Interface all collectors implement |
| `CollectorTrait` | Default `startup()`/`shutdown()`/`isActive()` implementation |
| `SummaryCollectorInterface` | Collectors that contribute to entry summary |

## Directory Structure

```
src/
├── Debugger.php
├── DebuggerIdGenerator.php
├── Dumper.php
├── DumpHandlerInterface.php
├── FlattenException.php
├── ProxyDecoratedCalls.php
├── Proxy/
│   ├── AbstractObjectProxy.php         # Base proxy class, intercepts __call/__get/__set
│   ├── ProxyFactory.php                # Generates dynamic proxy classes implementing target interfaces
│   └── ErrorHandlingTrait.php          # Error tracking for proxies (getCurrentError/repeatError)
├── Collector/
│   ├── CollectorInterface.php
│   ├── CollectorTrait.php
│   ├── SummaryCollectorInterface.php
│   ├── ProxyLogTrait.php
│   ├── ContainerProxyConfig.php
│   ├── LogCollector.php
│   ├── EventCollector.php
│   ├── ServiceCollector.php
│   ├── ExceptionCollector.php
│   ├── HttpClientCollector.php
│   ├── VarDumperCollector.php
│   ├── TimelineCollector.php
│   ├── LoggerInterfaceProxy.php
│   ├── EventDispatcherInterfaceProxy.php
│   ├── HttpClientInterfaceProxy.php
│   ├── ContainerInterfaceProxy.php     # Uses ProxyFactory and ErrorHandlingTrait
│   ├── VarDumperHandlerInterfaceProxy.php  # Wraps DumpHandlerInterface
│   ├── ServiceProxy.php                # Extends AbstractObjectProxy, uses ErrorHandlingTrait
│   ├── ServiceMethodProxy.php
│   ├── Web/
│   │   ├── RequestCollector.php        # collectRequest()/collectResponse() + legacy collect()
│   │   └── WebAppInfoCollector.php     # collectTiming() with EVENT_* constants
│   ├── Console/
│   │   ├── CommandCollector.php        # Symfony Console events, BufferedOutput
│   │   └── ConsoleAppInfoCollector.php # collectTiming() with EVENT_* constants
│   └── Stream/
│       ├── FilesystemStreamCollector.php
│       ├── FilesystemStreamProxy.php
│       ├── HttpStreamCollector.php
│       └── HttpStreamProxy.php
├── Storage/
│   ├── StorageInterface.php
│   ├── FileStorage.php
│   └── MemoryStorage.php
├── Event/
│   └── ProxyMethodCallEvent.php
├── Helper/
│   └── BacktraceIgnoreMatcher.php      # preg_match loop, auto-wraps patterns in # delimiters
└── DebugServer/
    ├── Connection.php                  # UDP socket for real-time streaming
    ├── VarDumperHandler.php            # Implements DumpHandlerInterface, sends via UDP
    └── LoggerDecorator.php             # PSR-3 decorator, forwards to UDP + original logger
```

## Debugger Lifecycle

```
startupWeb(request) / startupConsole(command) → [proxies feed collectors] → shutdown() → flush to storage
```

1. `startupWeb(?ServerRequestInterface)`: Check `X-Debug-Ignore` header and `ignoredRequests` patterns (fnmatch), generate entry ID, call `startup()` on all collectors
2. `startupConsole(?string)`: Check `ignoredCommands` patterns (fnmatch) and `YII_DEBUG_IGNORE` env var
3. `startup(object)`: Legacy event-based entry point, introspects event object for request/command
4. Collection: Proxies intercept PSR calls and feed data to collectors
5. `shutdown()`: Flush storage (unless skipped), call `shutdown()` on all collectors
6. Storage: Write summary, data, and objects as three separate JSON files

Ignore patterns use `fnmatch()` syntax. Configure via `withIgnoredRequests()` / `withIgnoredCommands()`.

## Proxy System

Three base components in `Proxy/`:

- `AbstractObjectProxy` -- Base class. Wraps an object instance. Delegates `__call`, `__get`, `__set`, `__isset`. Subclasses override `afterCall()` to record call data.
- `ProxyFactory` -- Generates dynamic proxy classes at runtime via `eval()`. The generated class extends the proxy class AND implements the target interface. Method stubs delegate to `__call()`. Caches generated classes in memory.
- `ErrorHandlingTrait` -- Tracks current error (`getCurrentError()`, `repeatError()`, `resetCurrentError()`).

PSR proxies in `Collector/` implement their interface directly (e.g., `LoggerInterfaceProxy implements LoggerInterface`). `ServiceProxy` and `ContainerInterfaceProxy` use `ProxyFactory` to dynamically implement arbitrary service interfaces.

## Collector API Patterns

Several collectors expose both direct methods and legacy event-based `collect(object)`:

| Collector | Direct method | Legacy `collect(object)` |
|-----------|---------------|--------------------------|
| `RequestCollector` | `collectRequest(ServerRequestInterface)`, `collectResponse(ResponseInterface)` | Introspects `getRequest()`/`getResponse()` on event |
| `WebAppInfoCollector` | `collectTiming(string $eventType)` | Maps class name suffix to EVENT_* constant |
| `ConsoleAppInfoCollector` | `collectTiming(string $eventType)` | Maps Symfony Console events + class name suffix |
| `ExceptionCollector` | `collectException(Throwable)` | Checks `getThrowable()`, `getError()`, or direct `Throwable` |
| `EventCollector` | `collect(object $event, string $line)` | N/A (always direct) |

Adapters should prefer the direct methods. The legacy `collect(object)` methods exist for backward compatibility.

`EventCollector` supports configurable `earlyAcceptedEvents` via `withEarlyAcceptedEvents(array)` -- events collected before the collector is active.

`WebAppInfoCollector` constants: `EVENT_APPLICATION_STARTUP`, `EVENT_BEFORE_REQUEST`, `EVENT_AFTER_REQUEST`, `EVENT_AFTER_EMIT`.

`ConsoleAppInfoCollector` constants: `EVENT_APPLICATION_STARTUP`, `EVENT_APPLICATION_SHUTDOWN`.

## Adding a New Collector

1. Create a class implementing `CollectorInterface` (use `CollectorTrait` for defaults)
2. Implement `getCollected(): array`
3. Optionally implement `SummaryCollectorInterface` for summary data
4. Register the collector in the adapter's configuration

## External Collectors

Some collectors live in framework-specific packages (not in ADP Kernel):

| Collector | Package | Purpose |
|-----------|---------|---------|
| `DatabaseCollector` | `yiisoft/db` | SQL query interception |
| `MiddlewareCollector` | `yiisoft/yii-debug` | PSR-15 middleware stack |
| `MailerCollector` | `yiisoft/mailer` | Email interception |

The frontend has panels for these. For framework-agnostic ADP, these need Kernel-native implementations.

## Storage

| Type | Constant | Content |
|------|----------|---------|
| Summary | `TYPE_SUMMARY` | ID, collector names, summary from `SummaryCollectorInterface` |
| Data | `TYPE_DATA` | Full collector payloads via `Dumper::asJson(30)` |
| Objects | `TYPE_OBJECTS` | Object map via `Dumper::asJsonObjectsMap(30)` |

`StorageInterface` methods: `addCollector()`, `getData()`, `read(type, ?id)`, `flush()`, `clear()`.

`FileStorage` writes to `{path}/{YYYY-MM-DD}/{entry-id}/{type}.json`. Uses `json_decode` for reading, `Dumper` for writing. Garbage collection removes oldest entries exceeding `historySize` (default 50).
