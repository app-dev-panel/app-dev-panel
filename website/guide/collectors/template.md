---
title: Template Collector
---

# Template Collector

Captures template/view rendering with optional timing, output capture, parameters, and duplicate detection. Works with any template engine (Twig, Blade, PHP templates, etc.).

## What It Captures

| Field | Description |
|-------|-------------|
| `template` | Template file path or name |
| `renderTime` | Rendering duration in seconds (optional, 0 if not measured) |
| `output` | Rendered output HTML (optional, empty if not captured) |
| `parameters` | Parameters passed to the template (optional) |

## Data Schema

```json
{
    "renders": [
        {
            "template": "home/index.html.twig",
            "renderTime": 0.0045,
            "output": "",
            "parameters": []
        },
        {
            "template": "/app/views/user/profile.php",
            "renderTime": 0,
            "output": "<div class=\"profile\">...</div>",
            "parameters": {"user": "object@App\\Entity\\User#42"}
        }
    ],
    "totalTime": 0.0045,
    "renderCount": 2,
    "duplicates": {
        "groups": [],
        "totalDuplicatedCount": 0
    }
}
```

**Summary** (shown in debug entry list):

```json
{
    "template": {
        "renderCount": 2,
        "totalTime": 0.0045,
        "duplicateGroups": 0,
        "totalDuplicatedCount": 0
    }
}
```

## Contract

Two entry points depending on available data:

```php
use AppDevPanel\Kernel\Collector\TemplateCollector;

// Template engines with timing (Twig, Blade)
$collector->logRender(
    template: 'home/index.html.twig',
    renderTime: 0.0045,
);

// View systems with output capture (Yii views, PHP templates)
$collector->collectRender(
    template: '/app/views/user/profile.php',
    output: '<div class="profile">...</div>',
    parameters: ['user' => $userObject],
    renderTime: 0.003, // optional
);
```

::: info
<class>\AppDevPanel\Kernel\Collector\TemplateCollector</class> implements <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class>, depends on <class>\AppDevPanel\Kernel\Collector\TimelineCollector</class>, and uses <class>\AppDevPanel\Kernel\Collector\DuplicateDetectionTrait</class>.
:::

## How It Works

Framework adapters hook into the template/view rendering system:
- **Symfony**: Twig profiler extension measures render time, calls `logRender()`
- **Yii 3**: <class>\AppDevPanel\Adapter\Yii3\Collector\View\ViewEventListener</class> listens to view render events, calls `collectRender()`
- **Yii 2**: `View::EVENT_BEFORE_RENDER` + `EVENT_AFTER_RENDER` with per-file timer stack — captures timing, output, and parameters in one call

## Debug Panel

- **Summary cards** — total templates rendered, aggregate render time (when timing available)
- **Template list** — all rendered templates with file paths and render time
- **Output preview** — rendered HTML output (expandable, when captured)
- **Parameters** — expandable view parameters (when captured)
- **Duplicate detection** — highlights repeated renders of the same template (N+1 issues)
- **Flat / Grouped views** — toggle between all renders and grouped duplicates
