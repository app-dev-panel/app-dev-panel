---
title: ConsoleAppInfo Collector
---

# ConsoleAppInfo Collector

Collects console application performance metrics — processing time, memory usage, and adapter name. The console equivalent of [WebAppInfo Collector](/guide/collectors/web-app-info).

## What It Captures

| Field | Description |
|-------|-------------|
| `applicationProcessingTime` | Total application processing time |
| `requestProcessingTime` | Command execution time |
| `applicationEmit` | Output emit time |
| `preloadTime` | Bootstrap/preload time |
| `memoryPeakUsage` | Peak memory usage in bytes |
| `memoryUsage` | Current memory usage in bytes |
| `adapter` | Framework adapter name |

## Data Schema

```json
{
    "applicationProcessingTime": 1.250,
    "requestProcessingTime": 1.200,
    "applicationEmit": 0.001,
    "preloadTime": 0.049,
    "memoryPeakUsage": 16777216,
    "memoryUsage": 12582912,
    "adapter": "symfony"
}
```

**Summary** (shown in debug entry list):

```json
{
    "console": {
        "adapter": "symfony",
        "request": {
            "startTime": 1711878000.100,
            "processingTime": 1.200
        },
        "memory": {
            "peakUsage": 16777216
        }
    }
}
```

## Contract

```php
use AppDevPanel\Kernel\Collector\Console\ConsoleAppInfoCollector;

$collector->markApplicationStarted();
// ... command execution ...
$collector->markApplicationFinished();
```

::: info
`ConsoleAppInfoCollector` implements `SummaryCollectorInterface` and depends on `TimelineCollector`. Located in the `Console` sub-namespace.
:::

## How It Works

Framework adapters call the `mark*()` methods at key points in the console command lifecycle. Memory metrics are captured via `memory_get_peak_usage()` and `memory_get_usage()`.

## Debug Panel

Console entry metadata (processing time, memory) is displayed in the debug entry header, similar to web entries.
