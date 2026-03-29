# Architecture

ADP follows a strict layered architecture where each layer has clear responsibilities and dependencies flow in one direction.

## Layers

### 1. Kernel

The core engine. Framework-independent. Manages:

- **Debugger** — Lifecycle management (start, collect, flush)
- **Collectors** — Gather runtime data via `CollectorInterface`
- **Storage** — Persist debug data (JSON files by default)
- **Proxies** — Intercept PSR interfaces transparently

```php
// Example: LogCollector implementing CollectorInterface
final class LogCollector implements CollectorInterface
{
    public function collect(mixed $data): void
    {
        // Store log entries
    }

    public function getData(): array
    {
        // Return collected data
    }
}
```

### 2. API

HTTP layer built on PSR-7/15. Provides:

- **REST endpoints** — Fetch debug entries, collectors data
- **SSE** — Real-time notifications for new entries
- **Inspector** — Runtime inspection endpoints
- **MCP** — AI assistant integration

### 3. Adapters

Framework bridges. Each adapter:

- Registers proxy services in the framework's DI container
- Wires framework-specific events to collectors
- Configures middleware pipeline

### 4. Frontend

React 18 SPA with:

- Material-UI 5 design system
- Redux Toolkit for state management
- Module system (Debug, Inspector, LLM, MCP, OpenAPI, Frames)

## Dependency Graph

```
Frontend → API → Kernel ← Adapter ← Target App
```

- Frontend depends only on API (via HTTP)
- API depends only on Kernel
- Adapter depends on Kernel and the target framework
- Kernel depends on nothing (PSR interfaces only)

## Data Flow

1. Target app runs with an Adapter installed
2. Adapter registers proxies that intercept PSR interfaces
3. Proxies feed data to Collectors
4. On request completion, Debugger flushes collector data to Storage
5. API serves stored data; SSE notifies the frontend
6. Frontend renders the data

## Module System

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
