# Laravel ADP Install Report

Practical installation walk-through of `app-dev-panel/adapter-laravel` on a fresh
Laravel 13.6.0 application, following the docs at `website/guide/adapters/laravel.md`
and `website/guide/getting-started.md`. The adapter is installed **from Packagist**
(not from the local monorepo).

## Environment

| Item | Value |
| --- | --- |
| Host OS | Linux 4.4.0 |
| PHP | 8.4.19 |
| Composer | 2.8.12 |
| Laravel | 13.6.0 (fresh `laravel/laravel` skeleton) |
| ADP adapter | `app-dev-panel/adapter-laravel` **v0.2** (Packagist) |
| Kernel/API/Cli/Mcp | v0.2 (pulled transitively) |

## What I did (exactly as the docs say)

```bash
# 1. Fresh Laravel
composer create-project laravel/laravel laravel-adp-test

# 2. Install ADP adapter from Packagist
cd laravel-adp-test
composer require app-dev-panel/adapter-laravel         # -> pulls v0.2.0

# 3. Publish config (as per website/guide/adapters/laravel.md)
php artisan vendor:publish \
  --provider="AppDevPanel\Adapter\Laravel\AppDevPanelServiceProvider"

# 4. Run the app
PHP_CLI_SERVER_WORKERS=4 php -S 127.0.0.1:8555 -t public
```

Auto-discovery worked, no manual service-provider registration was needed.
`APP_DEBUG=true` on a fresh Laravel install is enough to enable ADP
(`APP_DEV_PANEL_ENABLED` falls back to `APP_DEBUG`).

## Verification

- `GET /` → Laravel welcome page, **toolbar injected** (bottom-right).
- `GET /debug` → ADP SPA loads, sidebar (Home/Debug/Inspector/LLM/Open API/Frames),
  Debug+Inspector both show "Connected", debug entries list populated.
- `GET /debug/api` → JSON debug index with 28+ entries; all default collectors
  registered (Timeline, Environment, Log, Event, Service, HttpClient, VarDumper,
  Deprecation, Database, Cache, Mailer, Queue, OpenTelemetry, Assets, Template,
  Redis, Elasticsearch, Request, WebAppInfo, …).
- `storage/debug/2026-04-24/…` files are written per request.

## Problems encountered

### 1. `frontend:update download` breaks on v0.2 release assets — BUG

The docs say nothing about downloading the frontend, and the service provider's
auto-detection logic expects `public/vendor/app-dev-panel/bundle.js` to exist.
The only way to install it locally is the Artisan command shipped by the
adapter:

```bash
php artisan frontend:update download --path=public/vendor/app-dev-panel
```

On v0.2 this fails with:

```
[ERROR] No "frontend-dist.zip" asset found in latest release "v0.2".
Available assets:
  - panel-dist.tar.gz
  - toolbar-dist.tar.gz
```

The command hard-codes `frontend-dist.zip` but the GitHub release was split
into two tarballs (`panel-dist.tar.gz` + `toolbar-dist.tar.gz`). See
`libs/Cli/src/Command/FrontendUpdateCommand.php` — this is **broken** in
the published package.

**Workaround:** download the tarballs manually:

```bash
mkdir -p public/vendor/app-dev-panel/toolbar
curl -sSL https://github.com/app-dev-panel/app-dev-panel/releases/download/v0.2/panel-dist.tar.gz \
  | tar -xz -C public/vendor/app-dev-panel --strip-components=1
curl -sSL https://github.com/app-dev-panel/app-dev-panel/releases/download/v0.2/toolbar-dist.tar.gz \
  | tar -xz -C public/vendor/app-dev-panel/toolbar --strip-components=1
```

Once this is done the service provider's auto-detection kicks in and the
HTML switches from CDN URLs (`https://app-dev-panel.github.io/app-dev-panel/…`)
to local `/vendor/app-dev-panel/bundle.js` + `/vendor/app-dev-panel/toolbar/bundle.js`.

### 2. Docs don't mention the frontend distribution at all

`website/guide/adapters/laravel.md` stops at "publish the config" and never
tells the user where the panel JS/CSS come from. What actually happens out of
the box is that the SPA HTML references:

```html
<script type="module" src="https://app-dev-panel.github.io/app-dev-panel/bundle.js"></script>
<link rel="stylesheet"  href="https://app-dev-panel.github.io/app-dev-panel/bundle.css" />
```

This is fine as long as the user has outbound HTTPS to GitHub Pages. It fails
silently in environments where:

- outbound HTTPS is blocked (corporate proxy, offline dev),
- the TLS CA bundle is outdated (this test environment hit
  `net::ERR_CERT_AUTHORITY_INVALID`),
- the page is viewed over `http://` from an IP that browsers now class as
  non-secure context for mixed content.

When that happens the toolbar markup is injected but the JS never executes —
so the toolbar is invisible and the user has no idea why. Docs should spell
out: *"for offline/air-gapped installs, run `php artisan frontend:update
download …`"* — and that command needs to be fixed first (see #1).

### 3. `vendor:publish --provider=…` does publish the config, but the docs
imply a `--tag=app-dev-panel-config` shortcut in getting-started.md

`website/guide/getting-started.md:87` has:

```php
// php artisan vendor:publish --tag=app-dev-panel-config
```

This tag **does work** (the service provider registers it at `boot()`), but
the two docs pages suggest two different incantations. Minor — not a bug,
just confusing for first-time users.

### 4. Packagist release is empty `resources/dist/`

The Packagist tarball for `app-dev-panel/adapter-laravel` v0.2 ships an empty
`resources/dist/` with just `.gitignore` + `.gitkeep`. That's why the Laravel
`boot()` asset-publishing block is a no-op:

```php
// AppDevPanelServiceProvider.php
$assetSource = __DIR__ . '/../resources/dist';
if (is_dir($assetSource) && file_exists($assetSource . '/bundle.js')) {
    $this->publishes([...], ['app-dev-panel-assets', 'laravel-assets']);
}
```

The condition is false on a Packagist install, so `php artisan vendor:publish
--tag=app-dev-panel-assets` does nothing and the user has no in-package fallback
when the CDN is unreachable. Combined with #1 this means a fresh install is
effectively CDN-only.

### 5. `/debug/api/debug` returns 404 — confusing default for exploring the API

`curl http://127.0.0.1:8555/debug/api/debug` → `{"error":"Not found.","success":false}`.
The actual index endpoint is `/debug/api` (no `/debug` suffix) —
see `libs/API/src/ApiRoutes.php:43`:

```
GET /debug/api                        -> DebugController::index
GET /debug/api/summary/{id}           -> DebugController::summary
GET /debug/api/view/{id}              -> DebugController::view
```

It's an easy stumbling block when poking at the API manually. Docs could
include a quick curl example.

## Files produced by this test

Screenshots are gitignored repo-wide (`screenshots/` rule in `.gitignore`),
so they are not committed. They live in `/tmp/adp-shots/` on the test box:

| Path | What |
| --- | --- |
| `/tmp/adp-shots/01-homepage-with-toolbar.png` | Laravel welcome page + ADP toolbar (bottom-right) |
| `/tmp/adp-shots/02-debug-panel.png` | `/debug` SPA — Home dashboard, debug/inspector connected, entries list |
| `/tmp/adp-shots/03-toolbar-closeup.png` | Zoomed toolbar widget (🐤 200 72ms) |
| `/tmp/adp-shots/06-debug-menu-open.png` | `/debug` SPA with the top entry-picker dropdown open (30 entries) |

The test project itself lives at `/home/user/test-installation/laravel-adp-test/`
(outside this repo — not committed).

## Summary

Happy-path install works: one `composer require` + one `vendor:publish` and
the adapter is live with auto-discovery. The toolbar is injected, the debug
panel renders, the collectors fire and write to `storage/debug/…`.

The rough edges are all around **frontend asset delivery**:
- no docs about where the JS comes from,
- CDN is the only default,
- the Artisan command that's supposed to localise the assets is broken in v0.2
  because the release was renamed from `frontend-dist.zip` to
  `panel-dist.tar.gz` + `toolbar-dist.tar.gz`,
- the package ships an empty `resources/dist/`.

Fixing `FrontendUpdateCommand` to understand the new release layout (or
shipping the compiled panel/toolbar inside the Packagist tarball) plus a short
"offline / air-gapped install" section in the Laravel adapter docs would clear
the whole class of issues.
