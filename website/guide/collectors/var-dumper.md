---
title: VarDumper Collector
---

# VarDumper Collector

Captures manual variable dumps (`dump()` / `dd()` calls) with source file and line information.

![VarDumper Collector panel](/images/collectors/var-dumper.png)

## What It Captures

| Field | Description |
|-------|-------------|
| `variable` | The dumped variable value |
| `line` | Source file and line of the dump call |

## Data Schema

```json
[
    {
        "variable": {"key": "value", "nested": [1, 2, 3]},
        "line": "/app/src/Controller.php:42"
    }
]
```

**Summary** (shown in debug entry list):

```json
{
    "var-dumper": {
        "total": 2
    }
}
```

## Contract

```php
use AppDevPanel\Kernel\Collector\VarDumperCollector;

$collector->collect(
    variable: ['key' => 'value'],
    line: '/app/src/Controller.php:42',
);
```

::: info
<class>\AppDevPanel\Kernel\Collector\VarDumperCollector</class> implements <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class> and depends on <class>\AppDevPanel\Kernel\Collector\TimelineCollector</class>.
:::

## How It Works

Framework adapters hook into the `dump()` / `dd()` function to intercept variable dumps. Instead of outputting to the browser, the dumped values are captured and sent to the collector with source location.

## Debug Panel

- **Variable list** — all dumped variables with source location
- **Deep inspection** — expandable variable viewer with nested object/array support
- **File links** — clickable source file paths for IDE integration
