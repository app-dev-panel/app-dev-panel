---
description: "Install and configure ADP for Yii 2.0.50+. Bootstrap integration, collector wiring, and debug module setup."
---

# Yii 2 Adapter

The Yii 2 adapter bridges ADP Kernel and API into Yii 2.0.50+ via the bootstrap mechanism.

## Installation

```bash
composer require app-dev-panel/adapter-yii2
```

::: info Package
<pkg>app-dev-panel/adapter-yii2</pkg>
:::

The package auto-registers via `extra.bootstrap` in composer.json (handled by `yiisoft/yii2-composer`, already present in every official Yii 2 template). The <class>AppDevPanel\Adapter\Yii2\Bootstrap</class> class registers the `app-dev-panel` module automatically when `YII_DEBUG` is enabled.

### Install-time requirements

The adapter's bootstrap surfaces install-time problems through `Yii::warning(...)` under the `app-dev-panel` log category. If the panel is not reachable after `composer require`, check your log for these entries first:

| Requirement | Why | Fix |
|-------------|-----|-----|
| `UrlManager::$enablePrettyUrl = true` | ADP adds URL rules for `/debug/*`. Without pretty URLs Yii parses the path through the `r` query param and falls through to `site/index`. | Enable pretty URLs in `components.urlManager`. The stock `yii2-app-basic` template ships this block commented out. |
| No `debug` module registered by `yiisoft/yii2-debug` | yii2-debug claims the same `/debug/*` routes and wins the rule race — you get its toolbar instead of the ADP panel. | Remove `debug` from `bootstrap` and `modules`, or rename ADP's prefix (see `$routePrefix` below). |
| `yiisoft/yii2-composer` is present | Executes the `extra.bootstrap` entry that wires <class>AppDevPanel\Adapter\Yii2\Bootstrap</class>. Shipped with every official Yii 2 template. | Add it to `require` if you stripped it from a customised template. |

## Configuration

Configure the module in your application config:

```php
'modules' => [
    'app-dev-panel' => [
        'class' => \AppDevPanel\Adapter\Yii2\Module::class,
        'routePrefix' => 'debug',          // URL prefix for panel + API (default 'debug')
        'inspectorRoutePrefix' => 'inspect',
        'storagePath' => '@runtime/debug',
        'historySize' => 50,
        'collectors' => [
            'request' => true,
            'exception' => true,
            'log' => true,
            'event' => true,
            'db' => true,
            'mailer' => true,
            'assets' => true,
            'redis' => true,
            'elasticsearch' => true,
            'template' => true,
            'code_coverage' => false, // opt-in, requires pcov or xdebug
            // ... all collectors enabled by default
        ],
        'ignoredRequests' => ['/debug/api/**', '/inspect/api/**'],
        'ignoredCommands' => ['help', 'list', 'cache/*', 'asset/*'],
        'allowedIps' => ['127.0.0.1', '::1'],
    ],
],
```

## Collectors

Supports all Kernel collectors plus Yii 2-specific data capture:

| Collector | Mechanism | Data |
|-----------|-----------|------|
| Database | `DbProfilingTarget` (Yii logger) | SQL queries, timing, row count |
| Log | <class>AppDevPanel\Adapter\Yii2\Collector\DebugLogTarget</class> (real-time Yii log target) | Log messages with PSR-3 level mapping |
| Mailer | `BaseMailer::EVENT_AFTER_SEND` | From, to, cc, bcc, subject, body |
| Asset Bundles | `View::EVENT_END_PAGE` | Bundles: class, paths, CSS/JS, dependencies |
| Translator | <class>AppDevPanel\Adapter\Yii2\Proxy\I18NProxy</class> replaces `Yii::$app->i18n` | Translation lookups, missing translations |
| Template | `View::EVENT_BEFORE_RENDER` + `EVENT_AFTER_RENDER` | Render timing, output, parameters (supports nesting) |
| Redis | Direct collector calls | Redis commands, timing, errors |
| Elasticsearch | Direct collector calls | ES requests, timing, hits |
| Code Coverage | `pcov` / `xdebug` extension | Per-file line coverage (opt-in) |
| Authorization | `User::EVENT_AFTER_LOGIN/LOGOUT` | Auth events, user identity |
| Router | <class>AppDevPanel\Adapter\Yii2\Proxy\UrlRuleProxy</class> wraps all URL rules | Route matching data, timing |

### Template Collector

The **TemplateCollector** hooks into `View::EVENT_BEFORE_RENDER` and `EVENT_AFTER_RENDER` to capture every view rendering with its file path, output, parameters, and timing. Handles nested view rendering correctly (e.g., layout → partial → widget) using a per-file timer stack. Detects duplicate renders automatically.

### Code Coverage

Code coverage is **opt-in** (`'code_coverage' => false` by default). Requires the `pcov` or `xdebug` PHP extension. Without either, the collector returns `driver: null`. See [Collectors — Code Coverage](/guide/collectors#code-coverage-collector) for details.

## Translator Integration

The adapter replaces Yii 2's `i18n` application component with <class>AppDevPanel\Adapter\Yii2\Proxy\I18NProxy</class> — an extended `yii\i18n\I18N` that overrides `translate()`. All `Yii::t()` calls are intercepted and logged to <class>AppDevPanel\Kernel\Collector\TranslatorCollector</class> automatically. See [Translator](/guide/translator) for details.

## Database Inspector

`Yii2DbSchemaProvider` provides database schema inspection via `yii\db\Schema`. Falls back to <class>AppDevPanel\Adapter\Yii2\Inspector\NullSchemaProvider</class> when no database component is configured.
