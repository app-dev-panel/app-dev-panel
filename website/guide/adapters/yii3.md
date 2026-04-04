---
description: "Install and configure ADP for Yii 3. Config plugin integration, collector wiring, and middleware setup."
---

# Yii 3 Adapter

The Yii 3 adapter is the reference ADP adapter. It bridges ADP Kernel and API into Yii 3 via config plugins.

## Installation

```bash
composer require app-dev-panel/adapter-yii3
```

::: info Package
<pkg>app-dev-panel/adapter-yiisoft</pkg>
:::

The package auto-registers via Yii 3's config plugin system — no manual wiring needed.

## Configuration

All settings are managed in `config/params.php`:

```php
'app-dev-panel/yii3' => [
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

Add the following middleware to your web application stack. **Order matters** — incorrect ordering will cause the panel to malfunction:

```
ErrorCatcher → YiiApiMiddleware → ... → Router
```

- <class>AppDevPanel\Adapter\Yii3\Api\YiiApiMiddleware</class> — must be before `Router` to intercept `/debug/api/*` and `/inspect/api/*` requests

::: warning Middleware Order is Critical
`YiiApiMiddleware` **must** be placed before the `Router` middleware but after `ErrorCatcher`. If placed after the router, ADP API routes will not be intercepted. If placed before error handling, exceptions in ADP itself won't be caught gracefully.
:::

## Collectors

Includes Yii-specific collectors for database queries, mailer, queue, router, validator, translator, and views — in addition to all Kernel collectors (logs, events, exceptions, HTTP client, etc.).

Additionally:

- **Asset bundles** — <class>AppDevPanel\Adapter\Yii3\Collector\Asset\AssetLoaderInterfaceProxy</class> wraps `AssetLoaderInterface` to capture loaded bundles with CSS/JS files, dependencies, and options.
- **Code coverage** — <class>AppDevPanel\Kernel\Collector\CodeCoverageCollector</class> is registered and captures line coverage per request (requires pcov or xdebug).

## Translator Integration

When `yiisoft/translator` is installed, the adapter registers <class>AppDevPanel\Adapter\Yii3\Collector\Translator\TranslatorInterfaceProxy</class> in `trackedServices`. All `translate()` calls on `Yiisoft\Translator\TranslatorInterface` are intercepted automatically. See [Translator](/guide/translator) for details.

## Database Inspector

Database schema inspection is provided via `Yiisoft\Db` through <class>AppDevPanel\Adapter\Yii3\Inspector\DbSchemaProvider</class>.
