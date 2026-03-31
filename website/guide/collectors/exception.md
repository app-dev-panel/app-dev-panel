---
title: Exception Collector
---

# Exception Collector

Captures uncaught exceptions with full stack traces and exception chains (previous exceptions).

![Exception Collector panel](/images/collectors/exception.png)

## What It Captures

| Field | Description |
|-------|-------------|
| `class` | Exception class name |
| `message` | Exception message |
| `file` | File where the exception was thrown |
| `line` | Line number |
| `code` | Exception code |
| `trace` | Stack trace array |
| `traceAsString` | Stack trace as formatted string |

## Data Schema

Exceptions are serialized as an array (chain from outermost to innermost):

```json
[
    {
        "class": "RuntimeException",
        "message": "Something went wrong",
        "file": "/app/src/Service.php",
        "line": 42,
        "code": 0,
        "trace": [...],
        "traceAsString": "#0 /app/src/Controller.php(15): ..."
    },
    {
        "class": "InvalidArgumentException",
        "message": "Original cause",
        "file": "/app/src/Validator.php",
        "line": 88,
        "code": 0,
        "trace": [...],
        "traceAsString": "..."
    }
]
```

**Summary** (shown in debug entry list):

```json
{
    "exception": {
        "class": "RuntimeException",
        "message": "Something went wrong",
        "file": "/app/src/Service.php",
        "line": 42,
        "code": 0
    }
}
```

## Contract

```php
use AppDevPanel\Kernel\Collector\ExceptionCollector;

$collector->collect(throwable: $exception);
```

::: info
`ExceptionCollector` implements `SummaryCollectorInterface` and depends on `TimelineCollector`.
:::

## How It Works

Framework adapters hook into the error handling pipeline to capture uncaught exceptions. The collector traverses the exception chain via `getPrevious()` and serializes each exception in the chain.

## Debug Panel

- **Exception header** — class name, message, and throw location
- **Chained exceptions** — previous exceptions shown in a collapsible chain
- **Syntax-highlighted source code** — shows the file around the throw line
- **Full stack trace** — expandable with file links for IDE integration
