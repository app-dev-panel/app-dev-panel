---
title: Filesystem Stream Collector
description: "ADP Filesystem Stream Collector captures file operations: reads, writes, deletes, and directory listings."
---

# Filesystem Stream Collector

Captures filesystem (`file://`) stream operations via a PHP stream wrapper proxy — reads, writes, stats, and directory operations.

![Filesystem Stream Collector panel](/images/collectors/filesystem-stream.png)

## What It Captures

| Field | Description |
|-------|-------------|
| `operation` | Operation type (`open`, `read`, `write`, `stat`, `unlink`, `mkdir`, etc.) |
| `path` | File path |
| `args` | Operation arguments |

## Data Schema

Operations are grouped by type:

```json
{
    "open": [
        {"path": "/app/config/app.php", "args": {"mode": "r"}},
        {"path": "/app/var/cache/data.json", "args": {"mode": "w"}}
    ],
    "stat": [
        {"path": "/app/public/index.php", "args": {}}
    ]
}
```

**Summary** (shown in debug entry list):

```json
{
    "fs_stream": {
        "open": 15,
        "read": 42,
        "stat": 8,
        "write": 3
    }
}
```

## Contract

```php
use AppDevPanel\Kernel\Collector\Stream\FilesystemStreamCollector;

$collector->collect(
    operation: 'open',
    path: '/app/config/app.php',
    args: ['mode' => 'r'],
);
```

::: info
<class>\AppDevPanel\Kernel\Collector\Stream\FilesystemStreamCollector</class> implements <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class>. Supports configurable ignore patterns to exclude paths (e.g., vendor directory).
:::

## How It Works

The collector uses a PHP stream wrapper proxy (<class>\AppDevPanel\Kernel\Collector\Stream\FilesystemStreamProxy</class>) that registers itself for the `file://` protocol. All filesystem operations (`fopen`, `file_get_contents`, `is_file`, `mkdir`, etc.) are intercepted via PHP's stream wrapper mechanism. Paths matching `excludePaths` patterns are ignored.

## Debug Panel

- **Operation groups** — filesystem operations grouped by type
- **File path list** — all accessed paths with operation details
- **Operation counts** — summary of operations per type in sidebar badge (I/O)
