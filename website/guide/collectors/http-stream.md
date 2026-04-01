---
title: HTTP Stream Collector
---

# HTTP Stream Collector

Captures HTTP/HTTPS stream wrapper operations — requests made via `file_get_contents('http://...')`, `fopen('https://...')`, and similar PHP stream functions.

![HTTP Stream Collector panel](/images/collectors/http-stream.png)

## What It Captures

| Field | Description |
|-------|-------------|
| `operation` | Stream operation type (`open`, `read`, `stat`, etc.) |
| `uri` | HTTP/HTTPS URL accessed |
| `args` | Operation arguments |

## Data Schema

Operations are grouped by type:

```json
{
    "open": [
        {"uri": "https://api.example.com/data", "args": {"mode": "r"}}
    ]
}
```

**Summary** (shown in debug entry list):

```json
{
    "http_stream": {
        "open": 2,
        "read": 2
    }
}
```

## Contract

```php
use AppDevPanel\Kernel\Collector\Stream\HttpStreamCollector;

$collector->collect(
    operation: 'open',
    path: 'https://api.example.com/data',
    args: ['mode' => 'r'],
);
```

::: info
<class>\AppDevPanel\Kernel\Collector\Stream\HttpStreamCollector</class> implements <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class>. Supports configurable ignore patterns.
:::

## How It Works

The collector uses a PHP stream wrapper proxy (<class>\AppDevPanel\Kernel\Collector\Stream\HttpStreamProxy</class>) that registers itself for the `http://` and `https://` protocols. Stream operations via native PHP functions are intercepted. Paths matching `excludePaths` patterns are ignored.

::: warning
This collector only captures HTTP requests made via PHP stream functions (`file_get_contents`, `fopen`). For PSR-18 HTTP client calls, use the [HTTP Client Collector](/guide/collectors/http-client).
:::

## Debug Panel

- **Operation list** — HTTP stream operations with URLs
- **Combined with Filesystem** — displayed together with <class>\AppDevPanel\Kernel\Collector\Stream\FilesystemStreamCollector</class> under the "I/O" sidebar item
