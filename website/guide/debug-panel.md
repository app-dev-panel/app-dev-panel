---
title: Debug Panel
description: "The ADP debug panel is a React SPA for inspecting logs, queries, events, and other debug data from your PHP app."
---

# Debug Panel

The ADP debug panel is a React SPA that provides a web UI for inspecting debug data collected from your application. When you install an adapter, the panel is automatically available at `/debug` on your application.

::: tip Live Demo
Try the panel without installing anything: [Live Demo](https://app-dev-panel.github.io/app-dev-panel/demo/). Enter your application's URL in the backend field to connect.
:::

## How It Works

Each adapter registers a `/debug` route that serves a minimal HTML page. This page:

1. Loads `bundle.js` and `bundle.css` from a static assets source (GitHub Pages by default)
2. Injects runtime configuration — backend URL (auto-detected from the current request), router basename, etc.
3. Mounts the React SPA which communicates with the `/debug/api/*` and `/inspect/api/*` endpoints

```
Browser → GET /debug → Adapter serves HTML → Loads bundle.js from CDN
                                            → React SPA mounts
                                            → Fetches data from /debug/api/*
```

No separate frontend server is needed. The panel works out of the box after installing an adapter.

## Accessing the Panel

After installing an adapter, open your application in the browser and navigate to:

```
http://your-app/debug
```

The panel supports client-side routing, so all sub-paths (e.g., `/debug/logs`, `/debug/inspector/routes`) are handled by the SPA.

::: tip PHP Built-in Server
When using PHP's built-in server, set `PHP_CLI_SERVER_WORKERS=3` or higher. ADP makes concurrent requests (SSE + data fetching); single-worker mode causes timeouts.

```bash
PHP_CLI_SERVER_WORKERS=3 php -S 127.0.0.1:8080 -t public
```
:::

## Static Assets Source

By default, the panel loads assets from GitHub Pages:

```
https://app-dev-panel.github.io/app-dev-panel/bundle.js
https://app-dev-panel.github.io/app-dev-panel/bundle.css
```

You can change the static URL to load assets from a different source:

### Option 1: GitHub Pages (Default)

No configuration needed. Assets are automatically served from the latest release on GitHub Pages.

### Option 2: Local Assets from Release

Download `panel-dist.tar.gz` from a [GitHub Release](https://github.com/app-dev-panel/app-dev-panel/releases), extract it to a public directory, and configure the static URL:

```bash
# Download and extract
curl -L https://github.com/app-dev-panel/app-dev-panel/releases/latest/download/panel-dist.tar.gz | tar xz -C public/adp-panel
```

Then configure the adapter to use the local path:

:::tabs key:framework
== Symfony
```yaml
# config/packages/app_dev_panel.yaml
app_dev_panel:
    panel:
        static_url: '/adp-panel'
```
== Laravel
```php
// config/app-dev-panel.php
'panel' => [
    'static_url' => '/adp-panel',
],
```
== Yii 3
```php
// config/params.php
'app-dev-panel/yii3' => [
    'panel' => [
        'staticUrl' => '/adp-panel',
    ],
],
```
== Yii 2
```php
// config/web.php
'modules' => [
    'app-dev-panel' => [
        'class' => \AppDevPanel\Adapter\Yii2\Module::class,
        'panelStaticUrl' => '/adp-panel',
    ],
],
```
:::

### Option 3: Build from Source

If you're developing ADP itself or want to use a custom build, you can build the frontend from source and copy the assets into the adapter packages:

```bash
make build-panel
```

This command:
1. Builds the panel and toolbar packages via Vite
2. Copies `bundle.js`, `bundle.css`, and assets into each adapter's asset directory:
   - `libs/Adapter/Symfony/Resources/public/`
   - `libs/Adapter/Laravel/resources/dist/`
   - `libs/Adapter/Yii3/resources/dist/`
   - `libs/Adapter/Yii2/resources/dist/`

To also publish the assets into playground applications:

```bash
make build-install-panel    # Build + publish in one step
```

::: tip Auto-Detection
When `static_url` is left empty (the default), each adapter automatically checks whether built assets exist in its package directory. If `bundle.js` is found locally, the adapter serves assets from the local path instead of GitHub Pages — **no configuration needed**.

| Adapter | Local assets path | Served as |
|---------|-------------------|-----------|
| Symfony | `Resources/public/bundle.js` | `/bundles/appdevpanel` |
| Laravel | `resources/dist/bundle.js` → published to `public/vendor/app-dev-panel/` | `/vendor/app-dev-panel` |
| Yii 3 | `resources/dist/bundle.js` → symlinked to `@public/app-dev-panel/` | `/app-dev-panel` |
| Yii 2 | `resources/dist/bundle.js` → symlinked to `@webroot/app-dev-panel/` | `/app-dev-panel` |

To revert to GitHub Pages, remove the built assets from the adapter directory.
:::

### Option 4: Vite Dev Server

During frontend development, you can point the panel to the local Vite dev server:

```bash
cd libs/frontend
npm start    # Starts Vite on http://localhost:3000
```

Then configure:

:::tabs key:framework
== Symfony
```yaml
app_dev_panel:
    panel:
        static_url: 'http://localhost:3000'
```
== Laravel
```php
'panel' => [
    'static_url' => 'http://localhost:3000',
],
```
== Yii 3
```php
'app-dev-panel/yii3' => [
    'panel' => [
        'staticUrl' => 'http://localhost:3000',
    ],
],
```
== Yii 2
```php
'modules' => [
    'app-dev-panel' => [
        'class' => \AppDevPanel\Adapter\Yii2\Module::class,
        'panelStaticUrl' => 'http://localhost:3000',
    ],
],
```
:::

## Panel Modules

The panel SPA includes the following modules:

| Module | Path | Description |
|--------|------|-------------|
| Debug | `/debug` | View collected debug entries — logs, database queries, events, exceptions, timeline, HTTP requests, cache, mail, etc. |
| Inspector | `/debug/inspector/*` | Live application state — routes, config, database schema, git, cache, files, translations, Composer packages |
| LLM | `/debug/llm` | AI-powered chat and analysis of debug data. Supports OpenRouter, Anthropic, OpenAI, and ACP (local agents like Claude Code) |
| MCP | `/debug/mcp` | MCP (Model Context Protocol) server configuration |
| OpenAPI | `/debug/openapi` | Swagger UI for the ADP REST API |

## Configuration Reference

| Parameter | Default | Description |
|-----------|---------|-------------|
| `static_url` | `https://app-dev-panel.github.io/app-dev-panel` | Base URL for panel static assets (bundle.js, bundle.css) |
| `viewer_base_path` | `/debug` | Route prefix where the panel is mounted |

## Architecture

The panel rendering is handled at the API layer (<class>AppDevPanel\Api\Panel\PanelController</class>), which is framework-agnostic. Each adapter simply routes `/debug` and `/debug/*` to the same <class>AppDevPanel\Api\ApiApplication</class> that handles API requests.

```
GET /debug/logs/detail
    → Adapter catches /debug/* (not /debug/api/*)
    → ApiApplication routes to PanelController
    → PanelController renders HTML with:
        - <link> to bundle.css
        - <script> injecting window['AppDevPanelWidget'] config
        - <script> loading bundle.js
    → Browser loads React SPA
    → SPA uses client-side routing for /debug/logs/detail
```

Panel routes skip the JSON response wrapper and token auth middleware — they only pass through CORS and IP filter.
