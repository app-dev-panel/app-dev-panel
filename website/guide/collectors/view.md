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
<class>\AppDevPanel\Kernel\Collector\ViewCollector</class> implements <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class>, depends on <class>\AppDevPanel\Kernel\Collector\TimelineCollector</class>, and uses <class>\AppDevPanel\Kernel\Collector\DuplicateDetectionTrait</class>.
:::

## How It Works

Framework adapters hook into the view rendering system:
- **Yii 3**: <class>\AppDevPanel\Adapter\Yiisoft\Collector\View\ViewEventListener</class> listens to view render events
- **Yii 2**: View component events

## Debug Panel

- **View list** — all rendered views with file paths
- **Output preview** — rendered HTML output
- **Parameters** — expandable view parameters
- **Duplicate detection** — highlights repeated renders of the same view
