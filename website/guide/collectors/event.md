---
title: Event Collector
description: "ADP Event Collector records PSR-14 event dispatches with listener details, timing, and propagation status."
---

# Event Collector

Captures PSR-14 dispatched events with timing, listener metadata, and source location.

![Event Collector panel](/images/collectors/event.png)

## What It Captures

| Field | Description |
|-------|-------------|
| `name` | Event class name |
| `event` | Serialized event object |
| `file` | Source file of the dispatch call |
| `line` | Source line of the dispatch call |
| `time` | Timestamp when the event was dispatched |

## Data Schema

```json
[
    {
        "name": "App\\Event\\UserRegistered",
        "event": "object@App\\Event\\UserRegistered#12",
        "file": "/app/src/UserService.php",
        "line": "42",
        "time": 1711878000.456
    }
]
```

**Summary** (shown in debug entry list):

```json
{
    "event": {
        "total": 8
    }
}
```

## Contract

```php
use AppDevPanel\Kernel\Collector\EventCollector;

$collector->collect(
    event: $event,     // The dispatched event object
    line: '/app/src/UserService.php:42',
);
```

::: info
<class>\AppDevPanel\Kernel\Collector\EventCollector</class> implements <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class> and depends on <class>\AppDevPanel\Kernel\Collector\TimelineCollector</class> for cross-collector timeline integration.
:::

## How It Works

The collector is fed by <class>\AppDevPanel\Kernel\Collector\EventDispatcherInterfaceProxy</class> — a PSR-14 <class>Psr\EventDispatcher\EventDispatcherInterface</class> decorator. Every `$dispatcher->dispatch($event)` call is intercepted, timed, and forwarded to the collector.

Framework adapters register the proxy automatically.

## Debug Panel

- **Event type badges** — color-coded by event category (request, response, controller, etc.)
- **Chronological list** — events shown in dispatch order with timestamps
- **Expandable details** — click to view the full event object and listener chain
- **Event count** — total events shown in sidebar badge
