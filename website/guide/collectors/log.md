---
title: Log Collector
---

# Log Collector

Captures PSR-3 log messages recorded during a request or console command — level, message, context, and source location.

![Log Collector panel](/images/collectors/log.png)

## What It Captures

| Field | Description |
|-------|-------------|
| `time` | Timestamp when the log entry was recorded |
| `level` | PSR-3 log level (`debug`, `info`, `warning`, `error`, etc.) |
| `message` | Log message (string or stringable) |
| `context` | Contextual data array passed with the log call |
| `line` | Source file and line where the log call originated |

## Data Schema

```json
[
    {
        "time": 1711878000.123,
        "level": "info",
        "message": "User logged in",
        "context": {"userId": 42},
        "line": "/app/src/AuthService.php:87"
    }
]
```

**Summary** (shown in debug entry list):

```json
{
    "logger": {
        "total": 5
    }
}
```

## Contract

```php
use AppDevPanel\Kernel\Collector\LogCollector;

$collector->collect(
    level: 'info',
    message: 'User logged in',
    context: ['userId' => 42],
    line: '/app/src/AuthService.php:87',
);
```

::: info
<class>\AppDevPanel\Kernel\Collector\LogCollector</class> implements <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class> and depends on <class>\AppDevPanel\Kernel\Collector\TimelineCollector</class> for cross-collector timeline integration.
:::

## How It Works

The collector is fed by <class>\AppDevPanel\Kernel\Collector\LoggerInterfaceProxy</class> — a PSR-3 `LoggerInterface` decorator. When the proxy is registered as the application's logger, every `$logger->info(...)`, `$logger->error(...)`, etc. call is automatically intercepted and forwarded to the collector.

No manual wiring is needed if you use an adapter (Symfony, Laravel, Yii) — the proxy is registered automatically.

## Debug Panel

- **Filterable log list** — search by message text or log level
- **Color-coded levels** — each PSR-3 level has a distinct color badge
- **Expandable entries** — click to view full context data and source location
- **Entry count** — total log entries shown in sidebar badge
