---
title: AssetBundle Collector
---

# AssetBundle Collector

Captures registered frontend asset bundles — CSS files, JavaScript files, dependencies, and configuration.

## What It Captures

| Field | Description |
|-------|-------------|
| `class` | Asset bundle class name |
| `sourcePath` | Source path for published assets |
| `basePath` | Published base path |
| `baseUrl` | Published base URL |
| `css` | CSS file list |
| `js` | JavaScript file list |
| `depends` | Bundle dependencies |
| `options` | Bundle options |

## Data Schema

```json
{
    "bundles": {
        "AppAsset": {
            "class": "App\\Assets\\AppAsset",
            "sourcePath": "/app/assets",
            "basePath": "/public/assets/abc123",
            "baseUrl": "/assets/abc123",
            "css": ["css/app.css"],
            "js": ["js/app.js"],
            "depends": ["yii\\web\\JqueryAsset"],
            "options": {}
        }
    },
    "bundleCount": 3
}
```

**Summary** (shown in debug entry list):

```json
{
    "assets": {
        "bundleCount": 3
    }
}
```

## Contract

```php
use AppDevPanel\Kernel\Collector\AssetBundleCollector;

$collector->collectBundle(name: 'AppAsset', bundle: [
    'class' => 'App\\Assets\\AppAsset',
    'css' => ['css/app.css'],
    'js' => ['js/app.js'],
    'depends' => ['yii\\web\\JqueryAsset'],
]);

// Or collect all bundles at once
$collector->collectBundles(bundles: $allBundles);
```

::: info
`AssetBundleCollector` implements `SummaryCollectorInterface` and depends on `TimelineCollector`. Primarily used with Yii frameworks.
:::

## Debug Panel

- **Bundle list** — all registered asset bundles with file counts
- **Asset files** — CSS and JS files per bundle
- **Dependency tree** — bundle dependency graph
