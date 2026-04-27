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

The config plugin system wires DI, event listeners, and collectors automatically. One piece the plugin
cannot set for you is the middleware stack — Yii 3's `config/web/di/application.php` is owned by the
application, so the three ADP middleware have to be added there by hand (see [Middleware](#middleware) below).

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

Three middleware have to be added to `config/web/di/application.php`. **Order matters** — the
adapter assumes this stack:

```
ToolbarMiddleware → ErrorCatcher → YiiApiMiddleware → SessionMiddleware → CsrfTokenMiddleware → RequestCatcherMiddleware → Router
```

| Middleware | Purpose |
|-----------|---------|
| <class>AppDevPanel\Adapter\Yii3\Api\ToolbarMiddleware</class> | Injects the ADP debug toolbar into HTML responses, just before `</body>`. Must be outermost so it can rewrite the body produced by any downstream middleware (including error pages). |
| <class>AppDevPanel\Adapter\Yii3\Api\YiiApiMiddleware</class> | Intercepts `/debug/*` (panel SPA + `/debug/static/*` assets + `/debug/api/*`) and `/inspect/api/*` and delegates them to the ADP API application. Must be before `Router` so the user's routes don't shadow ADP's, and after `ErrorCatcher` so exceptions bubble up through Yii's error pipeline. |

Copy-paste-ready `config/web/di/application.php` for the stock `yiisoft/app` template:

```php
<?php

declare(strict_types=1);

use App\Web\NotFound\NotFoundHandler;
use AppDevPanel\Adapter\Yii3\Api\ToolbarMiddleware;
use AppDevPanel\Adapter\Yii3\Api\YiiApiMiddleware;
use Yiisoft\Csrf\CsrfTokenMiddleware;
use Yiisoft\Definitions\DynamicReference;
use Yiisoft\Definitions\Reference;
use Yiisoft\ErrorHandler\Middleware\ErrorCatcher;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;
use Yiisoft\RequestProvider\RequestCatcherMiddleware;
use Yiisoft\Router\Middleware\Router;
use Yiisoft\Session\SessionMiddleware;
use Yiisoft\Yii\Http\Application;

return [
    Application::class => [
        '__construct()' => [
            'dispatcher' => DynamicReference::to([
                'class' => MiddlewareDispatcher::class,
                'withMiddlewares()' => [
                    [
                        ToolbarMiddleware::class,
                        ErrorCatcher::class,
                        YiiApiMiddleware::class,
                        SessionMiddleware::class,
                        CsrfTokenMiddleware::class,
                        RequestCatcherMiddleware::class,
                        Router::class,
                    ],
                ],
            ]),
            'fallbackHandler' => Reference::to(NotFoundHandler::class),
        ],
    ],
];
```

::: warning Middleware Order
`YiiApiMiddleware` **must** be placed before the `Router` middleware but after `ErrorCatcher`. If
placed after the router, ADP routes will not be intercepted. If placed before error handling,
exceptions in ADP itself won't be caught gracefully. `ToolbarMiddleware` **must** be outermost —
it rewrites the HTML body returned by everything below it.
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
