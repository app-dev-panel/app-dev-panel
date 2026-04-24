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

By default, the panel loads assets from GitHub Pages (next to the live demo so both tracks master):

```
https://app-dev-panel.github.io/app-dev-panel/demo/bundle.js
https://app-dev-panel.github.io/app-dev-panel/demo/bundle.css
https://app-dev-panel.github.io/app-dev-panel/demo/toolbar/bundle.js
https://app-dev-panel.github.io/app-dev-panel/demo/toolbar/bundle.css
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

Alternatively the same archive is published as `frontend-dist.zip` for the built-in updater (see [Updating the frontend](#updating-the-frontend) below).

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
When `static_url` is left empty (the default), each adapter resolves the panel/toolbar bundles in this order:

1. **`app-dev-panel/frontend-assets`** — the canonical Composer package, installed transitively by every adapter, populated at release time by `.github/workflows/split.yml` (panel SPA + toolbar bundle). This is what runs in production installs.
2. **Adapter-local `resources/dist/`** — fallback for monorepo development after `make build-panel`.
3. **GitHub Pages CDN** — last-resort fallback.

| Adapter | Source location | Served as |
|---------|-----------------|-----------|
| Symfony | `Resources/public/bundle.js` (legacy local only — `assets:install`) | `/bundles/appdevpanel` |
| Laravel | `FrontendAssets::path()` or `resources/dist/` → `vendor:publish --tag=app-dev-panel-assets` | `/vendor/app-dev-panel` |
| Yii 3 | `FrontendAssets::path()` or `resources/dist/` → symlinked to `@public/app-dev-panel/` | `/app-dev-panel` |
| Yii 2 | `FrontendAssets::path()` or `resources/dist/` → symlinked to `@webroot/app-dev-panel/` | `/app-dev-panel` |

To force the CDN fallback, delete the auto-published symlink/dir (e.g. `public/app-dev-panel`) and either uninstall `app-dev-panel/frontend-assets` or set `static_url` explicitly.
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
| `static_url` | `https://app-dev-panel.github.io/app-dev-panel/demo` | Base URL for panel static assets (bundle.js, bundle.css) |
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

## Frontend as a Composer Package

Every framework adapter requires `app-dev-panel/frontend-assets`, a Composer package that ships the prebuilt panel SPA. When you install an adapter, Composer pulls `vendor/app-dev-panel/frontend-assets/dist/` automatically — no extra download step.

| What the package provides | Location after install |
|---------------------------|------------------------|
| Prebuilt `dist/` (`index.html`, JS, CSS, assets) | `vendor/app-dev-panel/frontend-assets/dist/` |
| `FrontendAssets::path()` helper | `AppDevPanel\FrontendAssets\FrontendAssets` |

### Standalone server — `adp serve`

The `adp serve` command starts PHP's built-in server with the ADP API on `/debug/api/*` and `/inspect/api/*`, and serves the panel SPA on every other path. When `--frontend-path` is omitted, the command calls `FrontendAssets::path()` and uses the Composer-installed bundle — so the full panel is available at `http://127.0.0.1:8888/` out of the box:

```bash
php vendor/bin/adp serve --host=127.0.0.1 --port=8888 --storage-path=./var/adp
```

To serve a different bundle (e.g. a custom build or a local dev copy):

```bash
php vendor/bin/adp serve --frontend-path=/path/to/my/dist
```

### Updating the frontend

Two supported update channels:

1. **Composer (recommended)** — bump the tagged version from the [`frontend-assets`](https://github.com/app-dev-panel/frontend-assets) split repository:

   ```bash
   composer update app-dev-panel/frontend-assets
   ```

2. **Direct download (for PHAR installs)** — the `frontend:update` CLI command fetches `frontend-dist.zip` from the [latest GitHub Release](https://github.com/app-dev-panel/app-dev-panel/releases) and extracts it in place:

   ```bash
   php vendor/bin/adp frontend:update check
   php vendor/bin/adp frontend:update download --path=/path/to/dist
   ```

   The command writes a `.adp-version` file alongside `index.html` so future `check` calls can tell whether an update is available.

### How the package is built

The monorepo does **not** track `libs/FrontendAssets/dist/` — it is generated on every push by `.github/workflows/split.yml`:

1. `npm ci && npm run build -w packages/sdk && npm run build -w packages/panel` (inside `libs/frontend/`).
2. The Vite output is copied into `libs/FrontendAssets/dist/`.
3. A throwaway local commit adds the `dist/` files, then `splitsh-lite` extracts `libs/FrontendAssets/` (source + dist) as a subtree.
4. The subtree is force-pushed to [`app-dev-panel/frontend-assets`](https://github.com/app-dev-panel/frontend-assets) and tagged with the release version when the trigger is a `v*` tag.

Consumers see the split repository — their `composer require` never reaches into the monorepo.
