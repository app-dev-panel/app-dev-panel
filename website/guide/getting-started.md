---
title: Getting Started
---

# Getting Started

ADP (Application Development Panel) is a framework-agnostic debugging panel for PHP applications. It collects runtime data and provides a web UI to inspect and debug your application.

<div class="badges">
  <a href="https://packagist.org/packages/app-dev-panel/kernel"><img src="https://img.shields.io/packagist/dependency-v/app-dev-panel/kernel/php?style=flat-square" alt="php"></a>
  <a href="https://packagist.org/packages/app-dev-panel/kernel"><img src="https://img.shields.io/packagist/v/app-dev-panel/kernel?style=flat-square" alt="packagist"></a>
  <a href="https://github.com/app-dev-panel/app-dev-panel/blob/master/LICENSE"><img src="https://img.shields.io/github/license/app-dev-panel/app-dev-panel?style=flat-square" alt="license"></a>
  <a href="https://packagist.org/packages/app-dev-panel/kernel"><img src="https://img.shields.io/packagist/dt/app-dev-panel/kernel?style=flat-square" alt="downloads"></a>
  <a href="https://github.com/app-dev-panel/app-dev-panel"><img src="https://img.shields.io/github/stars/app-dev-panel/app-dev-panel?style=flat-square" alt="github stars"></a>
</div>

<style>
.badges {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}
</style>

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

Each adapter pulls in <pkg>app-dev-panel/kernel</pkg> and <pkg>app-dev-panel/api</pkg> as dependencies automatically.

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

Run your application and open `http://your-app/debug` in your browser. The ADP [debug panel](/guide/debug-panel) shows debug data collected from your application in real-time.

::: tip PHP Built-in Server
When using PHP's built-in server, always set `PHP_CLI_SERVER_WORKERS=3` or higher. ADP makes concurrent requests (SSE + data fetching); single-worker mode causes timeouts.

```bash
PHP_CLI_SERVER_WORKERS=3 php -S 127.0.0.1:8080 -t public
```
:::

## Try the Demo

You can try the panel UI right away with the [Live Demo](https://app-dev-panel.github.io/app-dev-panel/demo/) — no installation required. Enter your application's backend URL to connect.

ADP also ships with [playground applications](/guide/playgrounds) for each supported framework:

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
- [Debug Panel](/guide/debug-panel) — Configure the embedded debug panel UI
- [Architecture](/guide/architecture) — Deep dive into the system design
- [Collectors](/guide/collectors) — Understand how data is collected
- [Data Flow](/guide/data-flow) — Follow data from your app to the panel
- [Feature Matrix](/guide/feature-matrix) — See what's supported per framework
- [Playgrounds](/guide/playgrounds) — Try the demo applications
