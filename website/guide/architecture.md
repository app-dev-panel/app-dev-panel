---
title: Architecture
---

# Architecture

ADP follows a strict layered architecture where each layer has clear responsibilities and dependencies flow in one direction.

## Layers

### 1. Kernel

The core engine. **Framework-independent** — depends only on PSR interfaces and generic PHP libraries. Manages:

- **Debugger** — Lifecycle management (start, collect, flush)
- **Collectors** — Gather runtime data via <class>AppDevPanel\Kernel\Collector\CollectorInterface</class>
- **Storage** — Persist debug data (JSON files by default) via <class>AppDevPanel\Kernel\Storage\StorageInterface</class>
- **Proxies** — Intercept PSR interfaces transparently

### 2. API

HTTP layer built on PSR-7/15. Provides:

- **REST endpoints** — Fetch debug entries, collector data
- **SSE** — Real-time notifications for new entries
- **Inspector** — Runtime inspection endpoints (config, routes, database schema, etc.)
- **MCP** — AI assistant integration via Model Context Protocol
- **Ingestion** — Accept debug data from external (non-PHP) applications

### 3. Adapters

Framework bridges. Each adapter:

- Registers proxy services in the framework's DI container
- Maps framework lifecycle events to <class>AppDevPanel\Kernel\Debugger</class>`::startup()` / `::shutdown()`
- Configures collectors and storage with framework-appropriate settings
- Registers API routes (`/debug/api/*`, `/inspect/api/*`)
- Serves the debug panel frontend at `/debug`
- Implements framework-specific inspector providers (config, routes, database schema)

### 4. Frontend

React 19 SPA with:

- Material-UI 5 design system
- Redux Toolkit for state management
- Module system (Debug, Inspector, LLM, MCP, OpenAPI, Frames)

## Dependency Graph

```
┌────────────────────────────────────────────────────────┐
│                  Dependency Direction                    │
│                                                         │
│   Adapter ──▶ API ──▶ Kernel                            │
│      │                   ▲                              │
│      └───────────────────┘                              │
│                                                         │
│   Cli ──▶ API ──▶ Kernel                                │
│                                                         │
│   Frontend ──▶ API (via HTTP only)                      │
└────────────────────────────────────────────────────────┘
```

- **Kernel** depends on nothing (PSR interfaces only)
- **API** depends only on Kernel
- **Cli** depends on Kernel and API
- **Adapter** depends on Kernel, API, and the target framework
- **Frontend** communicates via HTTP — no PHP dependencies

## Dependency Rules

The core principle: **common modules must never depend on framework-specific code**.

| Module | Can depend on | Cannot depend on |
|--------|--------------|-----------------|
| **Kernel** | PSR interfaces only | API, Cli, Adapter, any framework |
| **API** | Kernel, PSR interfaces | Adapter, any framework |
| **Cli** | Kernel, API, Symfony Console | Adapter, any framework |
| **Adapter** | Kernel, API, Cli, framework packages | Other adapters |
| **Frontend** | Nothing (HTTP only) | Any PHP package |

::: warning
Adapters must not depend on other adapters. Each adapter is an independent bridge between the Kernel and a specific framework.
:::

## Abstractions

Storage and serialization remain behind interfaces to ensure pluggability:

| Concern | Abstraction | Implementations |
|---------|-------------|-----------------|
| Debug data storage | <class>AppDevPanel\Kernel\Storage\StorageInterface</class> | <class>AppDevPanel\Kernel\Storage\FileStorage</class>, <class>AppDevPanel\Kernel\Storage\MemoryStorage</class> |
| Object serialization | <class>AppDevPanel\Kernel\Dumper</class> | JSON-based (built-in) |
| Database inspection | <class>AppDevPanel\Api\Inspector\Database\SchemaProviderInterface</class> | Per-adapter: <class>AppDevPanel\Adapter\Yiisoft\Inspector\DbSchemaProvider</class>, <class>AppDevPanel\Adapter\Symfony\Inspector\DoctrineSchemaProvider</class>, <class>AppDevPanel\Adapter\Laravel\Inspector\LaravelSchemaProvider</class>, <class>AppDevPanel\Adapter\Yii2\Inspector\NullSchemaProvider</class>, <class>AppDevPanel\Adapter\Cycle\Inspector\CycleSchemaProvider</class> |

## Data Flow

1. Target app runs with an Adapter installed
2. Adapter registers proxies that intercept PSR interfaces
3. Proxies feed data to Collectors
4. On request completion, Debugger flushes collector data to Storage
5. API serves stored data; SSE notifies the frontend
6. Frontend renders the data

See [Data Flow](/guide/data-flow) for the full lifecycle details.

## Frontend Module System

The frontend uses a module system where each module implements `ModuleInterface`:

```typescript
interface ModuleInterface {
    routes: RouteObject[];
    reducers: Record<string, Reducer>;
    middlewares: Middleware[];
    standalone: boolean;
}
```

Current modules: Debug, Inspector, LLM, MCP, OpenAPI, Frames.

## Creating a New Adapter

When creating an adapter for a new framework:

1. Create `libs/Adapter/<FrameworkName>/`
2. The adapter **must** depend on [`app-dev-panel/kernel`](https://packagist.org/packages/app-dev-panel/kernel)
3. The adapter **may** depend on [`app-dev-panel/api`](https://packagist.org/packages/app-dev-panel/api) (for route and inspector registration)
4. The adapter **may** depend on [`app-dev-panel/cli`](https://packagist.org/packages/app-dev-panel/cli) (for CLI commands)
5. The adapter **must not** depend on other adapters
6. The adapter **must not** modify Kernel or API code — only wire into them via configuration

### Adapter Responsibilities

| Responsibility | Description |
|----------------|-------------|
| Lifecycle mapping | Map framework events → <class>AppDevPanel\Kernel\Debugger</class>`::startup()` / `::shutdown()` |
| Proxy wiring | Register Kernel PSR proxies as service decorators in the framework's DI |
| Framework-specific proxies | Create proxies for non-PSR APIs (e.g., <class>AppDevPanel\Adapter\Symfony\Proxy\SymfonyEventDispatcherProxy</class>) |
| Collector configuration | Configure active collectors and pass framework-specific settings |
| Storage setup | Wire <class>AppDevPanel\Kernel\Storage\StorageInterface</class> with framework-appropriate paths |
| Route registration | Register API routes for `/debug/api/*`, `/inspect/api/*` and serve the frontend at `/debug` |
| Inspector providers | Implement <class>AppDevPanel\Api\Inspector\Database\SchemaProviderInterface</class>, <class>AppDevPanel\Api\Inspector\Elasticsearch\ElasticsearchProviderInterface</class>, etc. |

### Reference Implementations

| Adapter | Framework | Pattern |
|---------|-----------|---------|
| Symfony | Symfony 6.4–8.x | Bundle + Extension + CompilerPass |
| Yii2 | Yii 2 | Module + BootstrapInterface |
| Yiisoft | Yii 3 | Config plugin + ServiceProvider |
| Laravel | Laravel 11.x–12.x | ServiceProvider (register + boot) |

### Minimal Checklist

1. `composer.json` with [`app-dev-panel/kernel`](https://packagist.org/packages/app-dev-panel/kernel) + [`app-dev-panel/api`](https://packagist.org/packages/app-dev-panel/api) dependencies
2. Lifecycle event mapping → <class>AppDevPanel\Kernel\Debugger</class>`::startup()` / `::shutdown()`
3. Register Kernel PSR proxies as service decorators (logger, events, HTTP client)
4. Wire <class>AppDevPanel\Kernel\Storage\FileStorage</class> with a framework-appropriate path
5. Register API controller routes
6. Create a [playground](/guide/playgrounds) for testing and demo
