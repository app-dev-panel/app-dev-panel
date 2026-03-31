---
title: Timeline Collector
---

# Timeline Collector

Captures cross-collector performance timeline — a unified view of all events from all collectors in chronological order.

![Timeline Collector panel](/images/collectors/timeline.png)

## What It Captures

| Field | Description |
|-------|-------------|
| `time` | Event timestamp |
| `reference` | Reference ID linking back to the source collector's data entry |
| `collector` | Source collector class name |
| `data` | Additional event data (varies by collector) |

## Data Schema

Timeline events are stored as arrays:

```json
[
    [1711878000.100, 0, "AppDevPanel\\Kernel\\Collector\\Web\\RequestCollector", []],
    [1711878000.105, 0, "AppDevPanel\\Kernel\\Collector\\LogCollector", ["level", "info"]],
    [1711878000.150, 0, "AppDevPanel\\Kernel\\Collector\\EventCollector", []],
    [1711878000.200, 1, "AppDevPanel\\Kernel\\Collector\\LogCollector", ["level", "warning"]]
]
```

**Summary** (shown in debug entry list):

```json
{
    "timeline": {
        "total": 15
    }
}
```

## Contract

```php
use AppDevPanel\Kernel\Collector\TimelineCollector;

// Called by other collectors to register timeline events
$timeline->collect(
    collector: $logCollector,
    reference: 0,           // Index in the source collector's data
    'level', 'info',        // Additional context data
);
```

::: info
`TimelineCollector` implements `SummaryCollectorInterface`. Most other collectors depend on `TimelineCollector` to register their events on the timeline.
:::

## How It Works

The `TimelineCollector` is a central aggregation point. Other collectors (Log, Event, Database, etc.) call `$timeline->collect()` when they record an event, passing themselves as the source. This creates a unified chronological view across all collectors.

## Debug Panel

- **Visual timeline** — horizontal bar chart showing events across time
- **Collector filtering** — toggle visibility of specific collectors via chips
- **Color coding** — each collector type has a distinct color
- **Time scale** — auto-scaling time axis with microsecond precision
- **Event count** — total timeline events in sidebar badge
