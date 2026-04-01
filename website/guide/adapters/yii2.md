# Yii 2 Adapter

The Yii 2 adapter bridges ADP Kernel and API into Yii 2.0.50+ via the bootstrap mechanism.

## Installation

```bash
composer require app-dev-panel/adapter-yii2
```

The package auto-registers via `extra.bootstrap` in composer.json. The `Bootstrap` class registers the `debug-panel` module automatically when `YII_DEBUG` is enabled.

## Configuration

Configure the module in your application config:

```php
'modules' => [
    'debug-panel' => [
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
            'view' => true,
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
| Log | `DebugLogTarget` (real-time Yii log target) | Log messages with PSR-3 level mapping |
| Mailer | `BaseMailer::EVENT_AFTER_SEND` | From, to, cc, bcc, subject, body |
| Asset Bundles | `View::EVENT_END_PAGE` | Bundles: class, paths, CSS/JS, dependencies |
| Translator | `I18NProxy` replaces `Yii::$app->i18n` | Translation lookups, missing translations |
| View | `View::EVENT_AFTER_RENDER` | Rendered file, output, parameters |
| Templates | `View::EVENT_BEFORE_RENDER` + `EVENT_AFTER_RENDER` | Render timing per template (supports nesting) |
| Redis | Direct collector calls | Redis commands, timing, errors |
| Elasticsearch | Direct collector calls | ES requests, timing, hits |
| Code Coverage | `pcov` / `xdebug` extension | Per-file line coverage (opt-in) |
| Authorization | `User::EVENT_AFTER_LOGIN/LOGOUT` | Auth events, user identity |
| Router | `UrlRuleProxy` wraps all URL rules | Route matching data, timing |

### View & Template Collectors

The **ViewCollector** hooks into `yii\base\View::EVENT_AFTER_RENDER` to capture every view rendering with its file path, output, and parameters. Detects duplicate renders automatically.

The **TemplateCollector** hooks into both `EVENT_BEFORE_RENDER` and `EVENT_AFTER_RENDER` to measure render timing. Handles nested view rendering correctly (e.g., layout → partial → widget) using a per-file timer stack.

### Code Coverage

Code coverage is **opt-in** (`'code_coverage' => false` by default). Requires the `pcov` or `xdebug` PHP extension. Without either, the collector returns `driver: null`. See [Collectors — Code Coverage](/guide/collectors#code-coverage-collector) for details.

## Translator Integration

The adapter replaces Yii 2's `i18n` application component with `I18NProxy` — an extended `yii\i18n\I18N` that overrides `translate()`. All `Yii::t()` calls are intercepted and logged to `TranslatorCollector` automatically. See [Translator](/guide/translator) for details.

## Database Inspector

`Yii2DbSchemaProvider` provides database schema inspection via `yii\db\Schema`. Falls back to `NullSchemaProvider` when no database component is configured.
