---
title: Getting Started
---

# Getting Started

ADP (Application Development Panel) is a framework-agnostic debugging panel for PHP applications. It collects runtime data and provides a web UI to inspect and debug your application.

## Prerequisites

- PHP 8.4 or higher
- Composer

## Installation

### 1. Install the adapter for your framework

::: code-group

```bash [Yii 3]
composer require app-dev-panel/adapter-yiisoft
```

```bash [Symfony]
composer require app-dev-panel/adapter-symfony
```

```bash [Laravel]
composer require app-dev-panel/adapter-laravel
```

```bash [Yii 2]
composer require app-dev-panel/adapter-yii2
```

```bash [Cycle ORM]
composer require app-dev-panel/adapter-cycle
```

:::

Each adapter pulls in `app-dev-panel/kernel` and `app-dev-panel/api` as dependencies automatically.

### 2. Configure your application

::: code-group

```php [Yii 3]
// No configuration needed — auto-registered via yiisoft/config plugin
```

```php [Symfony]
// config/bundles.php
return [
    // ...
    AppDevPanel\Adapter\Symfony\AppDevPanelBundle::class => ['dev' => true, 'test' => true],
];
```

```php [Laravel]
// Auto-registered via package discovery
// Optionally publish config:
// php artisan vendor:publish --tag=app-dev-panel-config
```

```php [Yii 2]
// config/web.php
return [
    'bootstrap' => ['debug-panel'],
    'modules' => [
        'debug-panel' => [
            'class' => \AppDevPanel\Adapter\Yii2\Module::class,
        ],
    ],
];
```

:::

### 3. Start debugging

Run your application and access the debug API at `http://your-app/debug/api/`. The ADP panel shows debug data collected from your application in real-time.

::: tip PHP Built-in Server
When using PHP's built-in server, always set `PHP_CLI_SERVER_WORKERS=3` or higher. ADP makes concurrent requests (SSE + data fetching); single-worker mode causes timeouts.

```bash
PHP_CLI_SERVER_WORKERS=3 php -S 127.0.0.1:8080 -t public
```
:::

## Try the Demo

ADP ships with [playground applications](/guide/playgrounds) for each supported framework:

```bash
git clone https://github.com/app-dev-panel/app-dev-panel.git
cd app-dev-panel
make install              # Install all dependencies
```

Start a playground server:

::: code-group

```bash [Yii 3]
cd playground/yiisoft-app && ./yii serve --port=8101
```

```bash [Symfony]
cd playground/symfony-basic-app && PHP_CLI_SERVER_WORKERS=3 php -S 127.0.0.1:8102 -t public
```

```bash [Laravel]
cd playground/laravel-app && PHP_CLI_SERVER_WORKERS=3 php -S 127.0.0.1:8104 -t public
```

```bash [Yii 2]
cd playground/yii2-basic-app && PHP_CLI_SERVER_WORKERS=3 php -S 127.0.0.1:8103 -t public
```

:::

## Architecture Overview

ADP follows a layered architecture:

```
┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│   Frontend   │────▶│     API      │────▶│    Kernel     │
│  (React SPA) │ HTTP│  (REST+SSE)  │     │ (Collectors)  │
└──────────────┘     └──────────────┘     └───────┬───────┘
                                                  │
                                          ┌───────┴───────┐
                                          │    Adapter     │
                                          └───────┬───────┘
                                                  │
                                          ┌───────┴───────┐
                                          │  Target App   │
                                          └───────────────┘
```

1. **Kernel** — Core engine managing debugger lifecycle, collectors, and storage
2. **API** — HTTP layer exposing debug data via REST + SSE
3. **Adapter** — Framework bridge wiring collectors into your application
4. **Frontend** — React SPA consuming the API

## What's Next?

- [What is ADP?](/guide/what-is-adp) — Learn about the project philosophy
- [Architecture](/guide/architecture) — Deep dive into the system design
- [Collectors](/guide/collectors) — Understand how data is collected
- [Data Flow](/guide/data-flow) — Follow data from your app to the panel
- [Feature Matrix](/guide/feature-matrix) — See what's supported per framework
- [Playgrounds](/guide/playgrounds) — Try the demo applications
