---
title: Middleware Collector
---

# Middleware Collector

Captures HTTP middleware stack execution — the before and after processing phases with timing and memory usage.

## What It Captures

| Field | Description |
|-------|-------------|
| `beforeStack` | Middleware invoked before the action handler |
| `actionHandler` | The main action/controller handler |
| `afterStack` | Middleware invoked after the action handler |

Each middleware entry contains:

| Field | Description |
|-------|-------------|
| `name` | Middleware class name |
| `time` | Execution timestamp |
| `memory` | Memory usage at this point |
| `request` | Request state (before stack) |
| `response` | Response state (after stack) |

## Data Schema

```json
{
    "beforeStack": [
        {"name": "App\\Middleware\\AuthMiddleware", "time": 1711878000.100, "memory": 2097152, "request": "..."}
    ],
    "actionHandler": {
        "name": "App\\Controller\\UserController::index",
        "startTime": 1711878000.105,
        "request": "...",
        "response": "...",
        "endTime": 1711878000.120,
        "memory": 4194304
    },
    "afterStack": [
        {"name": "App\\Middleware\\CorsMiddleware", "time": 1711878000.121, "memory": 4194304, "response": "..."}
    ]
}
```

**Summary** (shown in debug entry list):

```json
{
    "middleware": {
        "total": 5
    }
}
```

## Contract

```php
use AppDevPanel\Kernel\Collector\MiddlewareCollector;

$collector->collectBefore(
    name: 'App\\Middleware\\AuthMiddleware',
    time: microtime(true),
    memory: memory_get_usage(),
    request: $request,
);

$collector->collectAfter(
    name: 'App\\Middleware\\CorsMiddleware',
    time: microtime(true),
    memory: memory_get_usage(),
    response: $response,
);
```

::: info
<class>\AppDevPanel\Kernel\Collector\MiddlewareCollector</class> implements <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class> and depends on <class>\AppDevPanel\Kernel\Collector\TimelineCollector</class>.
:::

## How It Works

Framework adapters instrument the middleware pipeline:
- **Yii 3**: <class>\AppDevPanel\Adapter\Yii3\Collector\Middleware\MiddlewareEventListener</class> listens to Yii middleware events
- **Symfony**: Kernel events (`kernel.request`, `kernel.response`, `kernel.controller`)
- **Laravel**: Middleware pipeline hooks

## Debug Panel

- **Middleware stack** — visual before/after pipeline with action handler in the middle
- **Timing** — execution time for each middleware
- **Memory tracking** — memory usage delta across the pipeline
