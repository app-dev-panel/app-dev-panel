# Yii 3 (Yiisoft) Adapter

The Yii 3 adapter is the reference ADP adapter. It bridges ADP Kernel and API into Yii 3 via config plugins.

## Installation

```bash
composer require app-dev-panel/adapter-yiisoft
```

::: info Package
<pkg>app-dev-panel/adapter-yiisoft</pkg>
:::

The package auto-registers via Yii 3's config plugin system — no manual wiring needed.

## Configuration

All settings are managed in `config/params.php`:

```php
'app-dev-panel/yiisoft' => [
    'enabled' => true,
    'collectors' => [...],
    'trackedServices' => [...],
    'ignoredRequests' => [],
    'ignoredCommands' => [],
    'dumper' => [
        'excludedClasses' => [],
    ],
    'logLevel' => [
        'AppDevPanel\\' => 0,
    ],
    'storage' => [
        'path' => '@runtime/debug',
        'historySize' => 50,
        'exclude' => [],
    ],
],
```

## Middleware

Add the following middleware to your web application stack (order matters):

```
DebugHeaders → ErrorCatcher → YiiApiMiddleware → ... → Router
```

- <class>AppDevPanel\Api\Debug\Middleware\DebugHeaders</class> — must be outermost to attach `X-Debug-Id` even on error responses
- <class>AppDevPanel\Adapter\Yiisoft\Api\YiiApiMiddleware</class> — intercepts `/debug/api/*` requests before the router

## Collectors

Includes Yii-specific collectors for database queries, mailer, queue, router, validator, translator, and views — in addition to all Kernel collectors (logs, events, exceptions, HTTP client, etc.).

## Translator Integration

When `yiisoft/translator` is installed, the adapter registers <class>AppDevPanel\Adapter\Yiisoft\Collector\Translator\TranslatorInterfaceProxy</class> in `trackedServices`. All `translate()` calls on `Yiisoft\Translator\TranslatorInterface` are intercepted automatically. See [Translator](/guide/translator) for details.

## Database Inspector

Database schema inspection is provided via `Yiisoft\Db` through <class>AppDevPanel\Adapter\Yiisoft\Inspector\DbSchemaProvider</class>.
