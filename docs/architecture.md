# Architecture

## High-Level Overview

ADP is a monorepo composed of five independent libraries and a reference application.
The architecture is designed around separation of concerns: the **Kernel** knows nothing about HTTP or frameworks,
the **API** knows nothing about specific frameworks, and **Adapters** bridge the gap.

```
┌─────────────────────────────────────────────────────────────┐
│                        Frontend                             │
│  ┌─────────────────┐  ┌──────────────┐  ┌───────────────┐  │
│  │  app-dev-panel   │  │ yii-dev-     │  │ app-dev-panel │  │
│  │  (Main SPA)      │  │ toolbar      │  │ -sdk          │  │
│  └────────┬─────────┘  └──────┬───────┘  └───────────────┘  │
│           │ HTTP               │ HTTP                        │
└───────────┼────────────────────┼─────────────────────────────┘
            │                    │
┌───────────┼────────────────────┼─────────────────────────────┐
│           ▼                    ▼           API Layer          │
│  ┌─────────────────────────────────────────────────────────┐ │
│  │                    API Library                           │ │
│  │  ┌──────────────┐  ┌───────────────┐  ┌──────────────┐ │ │
│  │  │ Debug API     │  │ Inspector API │  │ Ingestion    │ │ │
│  │  │ /debug/api/*  │  │ /inspect/api/*│  │ /ingest/*    │ │ │
│  │  └──────┬────────┘  └───────┬───────┘  └──────┬───────┘ │ │
│  └─────────┼───────────────────┼─────────────────┼─────────┘ │
└────────────┼───────────────────┼─────────────────┼───────────┘
             │                   │                 │
┌────────────┼───────────────────┼─────────────────┼───────────┐
│            ▼                   ▼                 ▼           │
│  ┌─────────────────────────────────────────────────────────┐ │
│  │                    Kernel Library                        │ │
│  │                                                         │ │
│  │  ┌──────────┐  ┌────────────┐  ┌─────────────────────┐ │ │
│  │  │ Debugger │  │ Collectors │  │ Storage             │ │ │
│  │  │          │──▶│ (12+ types)│──▶│ (File / Memory)    │ │ │
│  │  └──────────┘  └────────────┘  └─────────────────────┘ │ │
│  │                                                         │ │
│  │  ┌──────────────────┐  ┌──────────────────────────┐     │ │
│  │  │ Proxy System     │  │ Service Registry         │     │ │
│  │  │ Logger, Event,   │  │ Multi-app tracking       │     │ │
│  │  │ HTTP, Container  │  │ (.services.json)         │     │ │
│  │  └──────────────────┘  └──────────────────────────┘     │ │
│  │                                                         │ │
│  │  ┌──────────────────────────────────────────────────┐   │ │
│  │  │ Debug Server (UDP socket)                        │   │ │
│  │  │ Connection, SocketReader, Broadcaster            │   │ │
│  │  └──────────────────────────────────────────────────┘   │ │
│  └─────────────────────────────────────────────────────────┘ │
│                           Kernel Layer                       │
└──────────────────────────────┬───────────────────────────────┘
                               │
┌──────────────────────────────┼───────────────────────────────┐
│                              ▼          Adapter Layer         │
│  ┌─────────────────────────────────────────────────────────┐ │
│  │                 Adapter (e.g., Yiisoft)                  │ │
│  │                                                         │ │
│  │  - Registers proxies via DI                             │ │
│  │  - Wires event listeners to collector lifecycle         │ │
│  │  - Provides framework-specific config plugin            │ │
│  │  - Guards against circular DI resolution                │ │
│  └─────────────────────────────────────────────────────────┘ │
└──────────────────────────────┬───────────────────────────────┘
                               │
                    ┌──────────┴──────────┐
                    │   Target Application │
                    │   (User's PHP App)   │
                    └─────────────────────┘
```

## Layer Responsibilities

### Kernel (`libs/Kernel/`)

The core engine. Zero framework dependencies.

- **Debugger**: Manages lifecycle (startup -> collect -> shutdown -> flush). Uses `DebuggerIdGenerator` for unique entry IDs. Supports `WildcardPattern` for ignoring specific requests/commands. Optional PSR-3 logging.
- **Collectors**: Gather specific types of data (logs, events, requests, HTTP calls, exceptions, services, VarDumper, timeline, streams). All implement `CollectorInterface`; summary-capable ones implement `SummaryCollectorInterface`.
- **Proxies**: Intercept PSR interface calls transparently via Decorator pattern. `LoggerInterfaceProxy`, `EventDispatcherInterfaceProxy`, `HttpClientInterfaceProxy`, `ContainerInterfaceProxy`, `VarDumperHandlerInterfaceProxy`. Generic `ServiceProxy` wraps arbitrary services.
- **Storage**: `StorageInterface` with `FileStorage` (JSON on disk with GC) and `MemoryStorage` (tests). Three file types per entry: `summary.json`, `data.json`, `objects.json`.
- **Dumper**: Serialize PHP objects with depth control (default 30), circular reference handling, and deduplication. Produces `asJson()` for data and `asJsonObjectsMap()` for inspectable objects.
- **Service Registry**: `FileServiceRegistry` tracks external app registrations in `.services.json`. `ServiceDescriptor` value objects with capability checking and online status (60s heartbeat timeout).
- **Debug Server**: UDP socket server for real-time VarDumper/Logger output. Split into `Connection` (lifecycle), `SocketReader` (generator-based read), `Broadcaster` (fsockopen-based send).

### API (`libs/API/`)

HTTP interface to the collected data. Depends only on Kernel.

- **Debug Controllers**: Serve collected debug data (list, view, dump, object, event-stream)
- **Inspector Controllers**: Introspect live application state (routes, config, DB, git, files, translations, commands, composer, cache, opcache, phpinfo, classes, object)
- **Ingestion Controllers**: Accept debug data from external apps (single, batch, log shorthand)
- **Service Controllers**: Manage multi-app registrations (register, heartbeat, list, deregister)
- **Middleware**: IpFilter -> CorsAllowAll -> TokenAuthMiddleware -> FormatDataResponseAsJson -> ResponseDataWrapper -> (InspectorProxyMiddleware for /inspect/api)
- **SSE**: `ServerSentEventsStream` polls storage hash every 1s, emits `debug-updated` on change
- **Inspector Proxy**: `InspectorProxyMiddleware` routes `?service=<name>` requests to external services with capability checking

### Adapter (`libs/Adapter/`)

Bridges Kernel into a specific framework. Each adapter:

1. Registers proxy services in the framework's DI container
2. Maps framework lifecycle events to Debugger startup/shutdown
3. Configures which collectors are active for web vs. console contexts
4. Provides a config plugin for zero-config installation
5. Guards against circular DI resolution (static `$resolving` flag in `DebugServiceProvider`)

### CLI (`libs/Cli/`)

Console commands for debug server operations:

- `dev` (`DebugServerCommand`): Runs UDP socket server, reads messages via `SocketReader`, displays VarDumper/Logger output. Configurable address/port via DI.
- `dev:broadcast` (`DebugServerBroadcastCommand`): Sends test messages via `Broadcaster`.
- Both use `#[AsCommand]` attribute and optional PSR-3 logging.

### Frontend (`libs/app-dev-panel/`)

React SPA that consumes the API.

- **Modules**: Debug, Inspector, Gii, OpenAPI, Frames -- each implements `ModuleInterface` with routes, reducers, middlewares
- **SDK**: Shared library with API clients (RTK Query), components, helpers, Redux slices
- **Toolbar**: Lightweight embeddable widget for in-page debugging
- **Code splitting**: Inspector pages use `React.lazy()` + `Suspense`
- **Error boundaries**: `RouteErrorBoundary` on all routes via `useRouteError()`
- **SSE**: `ServerSentEventsObserver` with exponential backoff (1s-30s)

## Proxy System Detail

The proxy system uses the Decorator pattern to wrap PSR interfaces:

| Proxy | PSR Interface | Collector | Data Captured |
|-------|--------------|-----------|---------------|
| `LoggerInterfaceProxy` | `Psr\Log\LoggerInterface` | `LogCollector` | level, message, context, file:line, time |
| `EventDispatcherInterfaceProxy` | `Psr\EventDispatcher\EventDispatcherInterface` | `EventCollector` | event object, file:line, time |
| `HttpClientInterfaceProxy` | `Psr\Http\Client\ClientInterface` | `HttpClientCollector` | request, response, timing, file:line |
| `ContainerInterfaceProxy` | `Psr\Container\ContainerInterface` | `ServiceCollector` | service ID, method, args, result, timing |
| `VarDumperHandlerInterfaceProxy` | VarDumper handler | `VarDumperCollector` | variable dump, file:line |
| `ServiceProxy` | Any service | `ServiceCollector` | method, args, result, timing |

All proxies extract call stack via `debug_backtrace()` for file:line attribution.

## Middleware Chain

All API requests pass through this pipeline:

```
Request
  |
1. CorsAllowAll             -- Permissive CORS headers
  |
2. IpFilter                 -- Validates IP against allowedIPs (default: 127.0.0.1, ::1)
  |
3. TokenAuthMiddleware       -- Validates X-Debug-Token header (optional, pass-through if empty)
  |
4. FormatDataResponseAsJson  -- Converts DataResponse to JSON
  |
5. ResponseDataWrapper       -- Wraps in {id, data, error, success, status}
  |
6. [InspectorProxyMiddleware -- only /inspect/api, routes ?service=<name> to external apps]
  |
Controller
```

## Design Principles

1. **PSR-first**: All interception happens at PSR interface boundaries, making the system framework-agnostic
2. **Collector pattern**: Each data type has its own collector; new types are added without modifying existing code
3. **Proxy transparency**: Proxies implement the same interface as the original service; the application is unaware
4. **Storage abstraction**: `StorageInterface` allows swapping file-based storage for database, Redis, etc.
5. **Multi-app support**: Service Registry + Inspector Proxy enable debugging multiple apps from one UI
6. **Language-agnostic ingestion**: Any language can send debug data via the Ingestion API
