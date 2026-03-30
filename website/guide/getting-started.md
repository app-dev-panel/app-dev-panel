---
title: Getting Started
---

# Getting Started

ADP (Application Development Panel) is a framework-agnostic debugging panel for PHP applications. It collects runtime data and provides a web UI to inspect and debug your application.

[![php](https://img.shields.io/packagist/dependency-v/app-dev-panel/kernel/php?style=flat-square)](https://packagist.org/packages/app-dev-panel/kernel) [![packagist](https://img.shields.io/packagist/v/app-dev-panel/kernel?style=flat-square)](https://packagist.org/packages/app-dev-panel/kernel) [![license](https://img.shields.io/github/license/app-dev-panel/app-dev-panel?style=flat-square)](https://github.com/app-dev-panel/app-dev-panel/blob/master/LICENSE) [![downloads](https://img.shields.io/packagist/dt/app-dev-panel/kernel?style=flat-square)](https://packagist.org/packages/app-dev-panel/kernel) [![github stars](https://img.shields.io/github/stars/app-dev-panel/app-dev-panel?style=flat-square)](https://github.com/app-dev-panel/app-dev-panel)

## Prerequisites

- PHP 8.4 or higher
- Composer

## Installation

### 1. Install the adapter for your framework

:::tabs key:framework
== Symfony
```bash
composer require app-dev-panel/adapter-symfony
```
== Yii 2
```bash
composer require app-dev-panel/adapter-yii2
```
== Yii 3
```bash
composer require app-dev-panel/adapter-yiisoft
```
== Laravel
```bash
composer require app-dev-panel/adapter-laravel
```
:::

Each adapter pulls in `app-dev-panel/kernel` and `app-dev-panel/api` as dependencies automatically.

### 2. Configure your application

:::tabs key:framework
== Symfony
```php
// config/bundles.php
return [
    // ...
    AppDevPanel\Adapter\Symfony\AppDevPanelBundle::class => ['dev' => true, 'test' => true],
];
```
== Yii 2
```php
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
== Yii 3
```php
// No configuration needed — auto-registered via yiisoft/config plugin
```
== Laravel
```php
// Auto-registered via package discovery
// Optionally publish config:
// php artisan vendor:publish --tag=app-dev-panel-config
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

:::tabs key:framework
== Symfony
```bash
cd playground/symfony-basic-app && PHP_CLI_SERVER_WORKERS=3 php -S 127.0.0.1:8102 -t public
```
== Yii 2
```bash
cd playground/yii2-basic-app && PHP_CLI_SERVER_WORKERS=3 php -S 127.0.0.1:8103 -t public
```
== Yii 3
```bash
cd playground/yiisoft-app && ./yii serve --port=8101
```
== Laravel
```bash
cd playground/laravel-app && PHP_CLI_SERVER_WORKERS=3 php -S 127.0.0.1:8104 -t public
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
