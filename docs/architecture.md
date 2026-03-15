# Architecture

## High-Level Overview

ADP is a monorepo composed of five independent libraries and a reference application.
The architecture is designed around separation of concerns: the **Kernel** knows nothing about HTTP or frameworks,
the **API** knows nothing about specific frameworks, and **Adapters** bridge the gap.

```
┌─────────────────────────────────────────────────────────────┐
│                        Frontend                             │
│  ┌─────────────────┐  ┌──────────────┐  ┌───────────────┐  │
│  │  yii-dev-panel   │  │ yii-dev-     │  │ yii-dev-panel │  │
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
│  │  │ Debug API     │  │ Inspector API │  │ SSE Stream   │ │ │
│  │  │ /debug/api/*  │  │ /inspect/api/*│  │ /event-stream│ │ │
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
│  │  ┌──────────────────────────────────────────────────┐   │ │
│  │  │ Proxy System                                     │   │ │
│  │  │ LoggerProxy, EventProxy, HttpClientProxy, etc.   │   │ │
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

- **Debugger**: Manages lifecycle (startup → collect → shutdown → flush)
- **Collectors**: Gather specific types of data (logs, events, requests, etc.)
- **Proxies**: Intercept PSR interface calls transparently
- **Storage**: Persist collected data (FileStorage writes JSON to disk)
- **Dumper**: Serialize PHP objects with depth control and deduplication

### API (`libs/API/`)

HTTP interface to the collected data. Depends only on Kernel.

- **Debug Controllers**: Serve collected debug data (list, view, dump, object)
- **Inspector Controllers**: Introspect live application state (routes, config, DB, git, etc.)
- **Middleware**: IP filtering, CORS, response wrapping, debug headers
- **SSE**: Server-Sent Events for real-time updates when new debug data arrives
- **Repository**: Abstraction over Storage for reading debug entries

### Adapter (`libs/Adapter/`)

Bridges Kernel into a specific framework. Each adapter:

1. Registers proxy services in the framework's DI container
2. Maps framework lifecycle events to Debugger startup/shutdown
3. Configures which collectors are active for web vs. console contexts
4. Provides a config plugin for zero-config installation

### Frontend (`libs/yii-dev-panel/`)

React SPA that consumes the API.

- **Modules**: Debug, Inspector, Gii, OpenAPI — each self-contained with routes, pages, components
- **SDK**: Shared library with API clients, components, helpers, Redux slices
- **Toolbar**: Lightweight embeddable widget for in-page debugging

## Design Principles

1. **PSR-first**: All interception happens at PSR interface boundaries, making the system framework-agnostic
2. **Collector pattern**: Each data type has its own collector; new types are added without modifying existing code
3. **Proxy transparency**: Proxies implement the same interface as the original service; the application is unaware
4. **Storage abstraction**: StorageInterface allows swapping file-based storage for database, Redis, etc.
5. **Module federation**: Frontend supports dynamic loading of remote panels via Webpack Module Federation
