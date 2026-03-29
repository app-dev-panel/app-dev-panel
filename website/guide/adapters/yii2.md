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
            // ... all collectors
        ],
        'ignoredRequests' => ['/debug/api/**', '/inspect/api/**'],
        'ignoredCommands' => ['help', 'list', 'cache/*', 'asset/*'],
        'allowedIps' => ['127.0.0.1', '::1'],
    ],
],
```

## Collectors

Supports all Kernel collectors plus Yii 2-specific data capture: database queries via `DbProfilingTarget` (Yii logger), real-time log capture via `DebugLogTarget`, mailer events, and asset bundle profiling.

## Database Inspector

`Yii2DbSchemaProvider` provides database schema inspection via `yii\db\Schema`. Falls back to `NullSchemaProvider` when no database component is configured.
