# Laravel Adapter

The Laravel adapter bridges ADP Kernel and API into Laravel 11.x / 12.x via a service provider with auto-discovery.

## Installation

```bash
composer require app-dev-panel/adapter-laravel
```

The package is auto-discovered via `extra.laravel.providers` in composer.json — no manual registration needed.

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="AppDevPanel\Adapter\Laravel\AppDevPanelServiceProvider"
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

Supports all Kernel collectors plus Laravel-specific data capture via event listeners: Eloquent queries, cache operations, mail, queue jobs, and HTTP client requests.

## Database Inspector

`LaravelSchemaProvider` provides database schema inspection via `Illuminate\Database\Connection`. Falls back to `NullSchemaProvider` when no database is configured.
