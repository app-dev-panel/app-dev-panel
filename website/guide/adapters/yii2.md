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

The package auto-registers via `extra.bootstrap` in composer.json. The <class>AppDevPanel\Adapter\Yii2\Bootstrap</class> class registers the `app-dev-panel` module automatically when `YII_DEBUG` is enabled.

## Configuration

Configure the module in your application config:

```php
'modules' => [
    'app-dev-panel' => [
        'class' => \AppDevPanel\Adapter\Yii2\Module::class,
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

## Frontend Assets

`composer require app-dev-panel/adapter-yii2` transitively pulls <pkg>app-dev-panel/frontend-assets</pkg>. <class>AppDevPanel\Adapter\Yii2\Module</class> resolves `panelStaticUrl` automatically:

1. If `FrontendAssets::exists()` — symlink `vendor/app-dev-panel/frontend-assets/dist/` to `@webroot/app-dev-panel`, set `panelStaticUrl = '/app-dev-panel'`.
2. Otherwise fall back to `libs/Adapter/Yii2/resources/dist` (monorepo dev).
3. Otherwise, CDN fallback (`https://app-dev-panel.github.io/app-dev-panel`).

Override via the module's `panelStaticUrl` / `toolbarStaticUrl` config keys. Update with `composer update app-dev-panel/frontend-assets`.
