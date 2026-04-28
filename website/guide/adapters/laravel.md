---
description: "Install and configure ADP for Laravel 11.x/12.x/13.x. Service provider auto-discovery and collector setup."
---

# Laravel Adapter

The Laravel adapter bridges ADP Kernel and API into Laravel 11.x / 12.x / 13.x via a service provider with auto-discovery.

## Installation

```bash
composer require app-dev-panel/adapter-laravel
```

::: info Package
<pkg>app-dev-panel/adapter-laravel</pkg>
:::

The package is auto-discovered via `extra.laravel.providers` in composer.json — no manual registration needed.

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=app-dev-panel-config
```

This creates `config/app-dev-panel.php`:

```php
return [
    'enabled' => env('APP_DEV_PANEL_ENABLED', env('APP_DEBUG', true)),
    'storage' => [
        'path' => storage_path('debug'),
        'history_size' => 50,
    ],
    'collectors' => [
        'request' => true,
        'exception' => true,
        'log' => true,
        'event' => true,
        'database' => true,
        'cache' => true,
        'mailer' => true,
        'queue' => true,
        'assets' => true,
        'template' => true,
        'opentelemetry' => true,
        'code_coverage' => false,  // opt-in; requires pcov or xdebug
        // ... all collectors enabled by default
    ],
    'ignored_requests' => ['/debug/api/**', '/inspect/api/**'],
    'ignored_commands' => ['completion', 'help', 'list', 'debug:*', 'cache:*'],
    'api' => [
        'enabled' => true,
        'allowed_ips' => ['127.0.0.1', '::1'],
        'auth_token' => env('APP_DEV_PANEL_TOKEN', ''),
    ],
];
```

## Collectors

Supports all Kernel collectors plus Laravel-specific data capture via event listeners: Eloquent queries, cache operations, mail, queue jobs, HTTP client requests, translator lookups, and [Redis commands](/guide/collectors/redis) (via `Redis::listen()`).

Additionally:

- **Blade templates** — <class>AppDevPanel\Adapter\Laravel\Collector\TemplateCollectorCompilerEngine</class> wraps the Blade `CompilerEngine` to capture render timing and nesting depth automatically.
- **Asset bundles** — <class>AppDevPanel\Adapter\Laravel\EventListener\ViteAssetListener</class> collects rendered Vite assets (`preloadedAssets()`) after each response.
- **OpenTelemetry** — <class>AppDevPanel\Kernel\Collector\SpanProcessorInterfaceProxy</class> decorates `SpanProcessorInterface` via `$app->extend()` when `open-telemetry/sdk` is installed.

## Translator Integration

The adapter automatically decorates Laravel's `Translator` service with <class>AppDevPanel\Adapter\Laravel\Proxy\LaravelTranslatorProxy</class> via `$app->extend('translator')`. All `__('key')`, `trans()`, and `Lang::get()` calls are intercepted. Laravel's dot-notation keys (`group.key`) are parsed into category and message. See [Translator](/guide/translator) for details.

## Database Inspector

<class>AppDevPanel\Adapter\Laravel\Inspector\LaravelSchemaProvider</class> provides database schema inspection via `Illuminate\Database\Connection`. Falls back to <class>AppDevPanel\Adapter\Laravel\Inspector\NullSchemaProvider</class> when no database is configured.

## Frontend Assets

`composer require app-dev-panel/adapter-laravel` transitively pulls <pkg>app-dev-panel/frontend-assets</pkg>, which ships the prebuilt panel SPA and toolbar widget. <class>AppDevPanel\Adapter\Laravel\AppDevPanelServiceProvider</class> resolves `panel.static_url` in this order:

1. **Published copy** — `public/vendor/app-dev-panel/bundle.js` exists (after `php artisan vendor:publish --tag=app-dev-panel-assets`). Web server serves it directly.
2. **Composer-installed bundle** — `vendor/app-dev-panel/frontend-assets/dist/` exists. Adapter resolves the URL to `/vendor/app-dev-panel`.
3. **CDN fallback** — `https://app-dev-panel.github.io/app-dev-panel`. Used when neither of the above is present.

Override via `config/app-dev-panel.php`:

```php
'panel' => [
    'static_url' => '',                        // '' = auto-detect (recommended)
    // 'static_url' => 'http://localhost:3000', // Vite dev server with HMR
    // 'static_url' => 'https://my-cdn/adp',    // your own CDN
],
'toolbar' => [
    'enabled' => true,
    'static_url' => '',                        // '' = derive from panel.static_url + '/toolbar'
],
```

The toolbar bundle lives at `{panel.static_url}/toolbar/bundle.js` — keep both URLs co-located unless you mirror them separately.

### Updating the bundle

```bash
composer update app-dev-panel/frontend-assets
```

For PHAR / non-Composer installs, the `frontend:update` artisan command fetches the latest release tarballs from GitHub:

```bash
php artisan frontend:update download --path=public/vendor/app-dev-panel
```

## Manual API exploration

The debug API root is at `/debug/api`, **not** `/debug/api/debug`:

```bash
curl http://127.0.0.1:8000/debug/api                 # list recent debug entries
curl http://127.0.0.1:8000/debug/api/summary/{id}    # single entry summary
curl http://127.0.0.1:8000/debug/api/view/{id}       # full entry data
curl http://127.0.0.1:8000/debug/api/event-stream    # SSE stream of new entries
```
