---
title: Template Collector
---

# Template Collector

Captures template rendering operations with timing — works with any template engine (Twig, Blade, PHP templates, etc.).

## What It Captures

| Field | Description |
|-------|-------------|
| `template` | Template file path or name |
| `renderTime` | Rendering duration in seconds |

## Data Schema

```json
{
    "renders": [
        {"template": "home/index.html.twig", "renderTime": 0.0045},
        {"template": "layout/base.html.twig", "renderTime": 0.0012}
    ],
    "totalTime": 0.0057,
    "renderCount": 2
}
```

**Summary** (shown in debug entry list):

```json
{
    "template": {
        "renderCount": 2,
        "totalTime": 0.0057
    }
}
```

## Contract

```php
use AppDevPanel\Kernel\Collector\TemplateCollector;

$collector->logRender(
    template: 'home/index.html.twig',
    renderTime: 0.0045,
);
```

::: info
<class>\AppDevPanel\Kernel\Collector\TemplateCollector</class> implements <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class> and depends on <class>\AppDevPanel\Kernel\Collector\TimelineCollector</class>.
:::

## How It Works

Framework adapters hook into the template engine to measure render time. The collector is engine-agnostic — it only records template name and render duration.

## Debug Panel

- **Template list** — all rendered templates with render time
- **Total time** — aggregate rendering time
- **Render count** — total templates rendered
