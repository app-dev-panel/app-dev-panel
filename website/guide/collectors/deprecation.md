---
title: Deprecation Collector
---

# Deprecation Collector

Captures PHP deprecation notices with message, source location, category, and stack trace.

![Deprecation Collector panel](/images/collectors/deprecation.png)

## What It Captures

| Field | Description |
|-------|-------------|
| `time` | Timestamp of the deprecation |
| `message` | Deprecation message |
| `file` | File where the deprecation was triggered |
| `line` | Line number |
| `category` | Deprecation category |
| `trace` | Stack trace |

## Data Schema

```json
[
    {
        "time": 1711878000.123,
        "message": "Method getData() is deprecated, use getResult() instead",
        "file": "/app/src/Legacy/Service.php",
        "line": 55,
        "category": "user",
        "trace": [...]
    }
]
```

**Summary** (shown in debug entry list):

```json
{
    "deprecation": {
        "total": 2
    }
}
```

## Contract

The collector registers a custom PHP error handler that intercepts `E_DEPRECATED` and `E_USER_DEPRECATED` notices. No explicit `collect()` method is needed — deprecations are captured automatically.

::: info
<class>\AppDevPanel\Kernel\Collector\DeprecationCollector</class> implements <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class> and depends on <class>\AppDevPanel\Kernel\Collector\TimelineCollector</class>.
:::

## How It Works

On `startup()`, the collector registers a PHP error handler via `set_error_handler()`. All deprecation warnings (`E_DEPRECATED`, `E_USER_DEPRECATED`) are captured with full stack traces. The original error handler is restored on `shutdown()`.

## Debug Panel

- **Deprecation list** — all deprecation notices with message and source location
- **Stack traces** — expandable trace for each deprecation
- **File links** — clickable source paths for IDE integration
