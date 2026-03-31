---
title: View Collector
---

# View Collector

Captures view/template rendering with captured output, parameters, and duplicate detection.

## What It Captures

| Field | Description |
|-------|-------------|
| `file` | View file path |
| `output` | Rendered output (HTML) |
| `parameters` | Parameters passed to the view |

## Data Schema

```json
{
    "renders": [
        {
            "file": "/app/views/user/profile.php",
            "output": "<div class=\"profile\">...</div>",
            "parameters": {"user": "object@App\\Entity\\User#42"}
        }
    ],
    "duplicates": {
        "groups": [],
        "totalDuplicatedCount": 0
    }
}
```

**Summary** (shown in debug entry list):

```json
{
    "view": {
        "total": 3,
        "duplicateGroups": 0,
        "totalDuplicatedCount": 0
    }
}
```

## Contract

```php
use AppDevPanel\Kernel\Collector\ViewCollector;

$collector->collectRender(
    file: '/app/views/user/profile.php',
    output: '<div class="profile">...</div>',
    parameters: ['user' => $userObject],
);
```

::: info
`ViewCollector` implements `SummaryCollectorInterface`, depends on `TimelineCollector`, and uses `DuplicateDetectionTrait`.
:::

## How It Works

Framework adapters hook into the view rendering system:
- **Yii 3**: `ViewEventListener` listens to view render events
- **Yii 2**: View component events

## Debug Panel

- **View list** — all rendered views with file paths
- **Output preview** — rendered HTML output
- **Parameters** — expandable view parameters
- **Duplicate detection** — highlights repeated renders of the same view
