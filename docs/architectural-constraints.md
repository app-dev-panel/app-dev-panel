# Architectural Constraints

## Dependency Rules

ADP follows strict dependency rules to maintain framework-agnosticism.
The core principle: **common modules must never depend on framework-specific code**.

```
┌────────────────────────────────────────────────────────────────┐
│                    Dependency Direction                        │
│                                                                │
│   Adapter ──▶ API ──▶ Kernel                                   │
│      │                   ▲                                     │
│      │                   │                                     │
│      └───────────────────┘                                     │
│                                                                │
│   Frontend ──▶ API (via HTTP only)                             │
│                                                                │
│   Cli ──▶ API ──▶ Kernel                                        │
└────────────────────────────────────────────────────────────────┘
```

### What is allowed

| Module | Can depend on | Cannot depend on |
|--------|--------------|-----------------|
| **Kernel** | PSR interfaces only | API, Cli, Adapter, any framework |
| **API** | Kernel, PSR interfaces | Adapter, any framework* |
| **Cli** | Kernel, API, Symfony Console | Adapter, any framework |
| **Adapter/Yiisoft** | Kernel, API, Cli, Yii 3 packages | Other adapters |
| **Adapter/Cycle** | API, Cycle ORM packages | Other adapters |
| **Adapter/Symfony** | Kernel, API, Cli, Symfony packages | Other adapters |
| **Adapter/Laravel** | Kernel, API, Cli, Laravel (Illuminate) packages | Other adapters |
| **Adapter/Yii2** | Kernel, API, Cli, Yii 2 packages | Other adapters |
| **Frontend** | Nothing (communicates via HTTP) | Any PHP package |

*API uses `yiisoft/var-dumper` (core infra, framework-agnostic). No framework-specific dependencies remain.

### Why

- **Kernel** is the core engine. If it depends on Yii or Symfony, it cannot be reused with other frameworks.
- **API** provides HTTP endpoints. It should work with any PSR-15 compatible middleware stack.
- **Adapters** are the only modules that may depend on a specific framework. They are installed as packages inside the target application.

## Storage and Serialization

Storage and serialization must remain behind abstractions:

| Concern | Abstraction | Implementations |
|---------|-------------|-----------------|
| Debug data storage | `StorageInterface` | `FileStorage`, `MemoryStorage` |
| Object serialization | `Dumper` class | JSON-based (built-in) |
| Database inspection | `SchemaProviderInterface` | `NullSchemaProvider` (API), `DbSchemaProvider` (Yiisoft), `CycleSchemaProvider` (Cycle), `DoctrineSchemaProvider` (Symfony), `LaravelSchemaProvider` (Laravel), `Yii2DbSchemaProvider` (Yii2) |
| Command execution | `CommandInterface` | `BashCommand`, `PHPUnitCommand`, etc. |

New storage backends (Redis, database, etc.) must implement `StorageInterface` without
modifying existing code. Serialization format changes must not break the API contract.

## Module-Specific Constraints

### Kernel

- **Zero framework dependencies**. Only PSR interfaces and generic PHP libraries.
- All collectors implement `CollectorInterface`. No collector may depend on a specific framework.
- Proxies implement PSR interfaces (`LoggerInterface`, `EventDispatcherInterface`, etc.).
- Storage is pluggable via `StorageInterface`. The Kernel must not assume file-based storage.
- The `Dumper` handles serialization. No framework-specific serializers allowed.

### API

- Depends on Kernel for data access via `CollectorRepositoryInterface` and `StorageInterface`.
- Controllers must not instantiate framework-specific services directly.
- Database inspection is abstracted via `SchemaProviderInterface`.
- Response format (`{id, data, error, success, status}`) is the API contract — do not change it.

### Cli

- Depends on Kernel for `Debugger`, `StorageInterface`, and `Connection`.
- Depends on API for bootstrapping the debug server (serves API endpoints).
- Uses Symfony Console for command infrastructure (acceptable — it's a standalone library).
- Must not depend on any adapter.

### Adapter/Yiisoft

- **May depend on Yii 3 packages** — this is by design. Adapters bridge the Kernel into a framework.
- Must not contain business logic. All logic lives in Kernel; the adapter only wires things together.
- Configuration, DI registration, and event mapping are the adapter's responsibilities.
- Must not depend on other adapters.

### Frontend

- Communicates with the backend exclusively via HTTP (REST + SSE).
- No direct PHP dependencies. The frontend is a standalone React application.
- API client abstraction (`createBaseQuery`) allows changing the backend URL dynamically.
- Must not assume any framework-specific API behavior beyond what's documented.

## Adding a New Adapter

When creating an adapter for a new framework (e.g., Laravel, Slim, Laminas):

1. Create `libs/Adapter/<FrameworkName>/`
2. The adapter **must** depend on `app-dev-panel/kernel`
3. The adapter **may** depend on `app-dev-panel/api` (for route registration and inspector endpoints)
4. The adapter **may** depend on `app-dev-panel/cli` (for CLI commands: `debug:reset`, `debug:server`, etc.)
5. The adapter **may** depend on the target framework's packages
6. The adapter **must not** depend on other adapters
7. The adapter **must not** modify Kernel or API code — only wire into them via configuration

### Adapter Responsibilities

| Responsibility | Description |
|----------------|-------------|
| **Lifecycle mapping** | Map framework events (request start/end, console start/end) → `Debugger::startup()` / `Debugger::shutdown()` |
| **Proxy wiring** | Register Kernel proxies (`LoggerInterfaceProxy`, `EventDispatcherInterfaceProxy`, `HttpClientInterfaceProxy`) as service decorators in the framework's DI |
| **Framework-specific proxies** | Create proxies for framework-specific APIs not covered by PSR (e.g., `SymfonyEventDispatcherProxy`, Yii2 `DbProfilingTarget`) |
| **Collector configuration** | Configure which collectors are active, pass framework-specific settings |
| **Storage setup** | Wire `StorageInterface` implementation (typically `FileStorage`) with framework-appropriate paths |
| **Route registration** | Register API routes for `/debug/api/*` and `/inspect/api/*` |
| **Inspector providers** | Implement `SchemaProviderInterface`, `ConfigProviderInterface`, route adapters for framework-specific introspection |

### Reference Implementations

| Adapter | Framework | Pattern | Key Classes |
|---------|-----------|---------|-------------|
| `libs/Adapter/Symfony/` | Symfony 6.4–8.x | Bundle + Extension + CompilerPass | `AppDevPanelBundle`, `AppDevPanelExtension`, `CollectorProxyCompilerPass` |
| `libs/Adapter/Laravel/` | Laravel 11.x–12.x | ServiceProvider (register + boot) | `AppDevPanelServiceProvider`, event listeners, `DebugMiddleware` |
| `libs/Adapter/Yiisoft/` | Yii 3 | Config plugin + ServiceProvider | `DebugServiceProvider`, config files (`di.php`, `events-web.php`) |
| `libs/Adapter/Yii2/` | Yii 2 | Module + BootstrapInterface | `Module`, `Bootstrap`, URL rules |
| `libs/Adapter/Cycle/` | Cycle ORM | Standalone (API only) | `CycleSchemaProvider` — lightweight, no Kernel dependency |

### Minimal New Adapter Checklist

1. Create `composer.json` with `app-dev-panel/kernel` + `app-dev-panel/api` dependencies
2. Implement lifecycle event mapping → `Debugger::startup()` / `Debugger::shutdown()`
3. Register Kernel PSR proxies as service decorators (logger, event dispatcher, HTTP client)
4. Wire `FileStorage` with a framework-appropriate path
5. Register API controller routes (`/debug/api/*`, `/inspect/api/*`)
6. Register all inspector controllers in DI (they are not auto-discovered)
7. Create `CLAUDE.md` documenting integration flow, configuration, and proxy mapping
8. Create a playground in `playground/<framework>-app/` (see `docs/playgrounds.md`)

## Adding New Storage

1. Implement `StorageInterface` from the Kernel
2. Register the implementation in the adapter's DI configuration
3. The Kernel, API, and Cli modules must work without modification
4. Handle all three data types: `TYPE_SUMMARY`, `TYPE_DATA`, `TYPE_OBJECTS`
