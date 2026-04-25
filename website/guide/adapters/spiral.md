---
description: "Install and configure ADP for Spiral Framework 3.x. Bootloader registration, PSR-15 middleware pipeline, fixture endpoints."
---

# Spiral Adapter

The Spiral adapter bridges ADP Kernel and API into Spiral Framework 3.14+ via a Bootloader.
Because Spiral is PSR-7/PSR-15 native it's the thinnest of the four full adapters — no
HttpFoundation/Illuminate Request bridges, no compiler passes.

## Installation

```bash
composer require app-dev-panel/adapter-spiral --dev
```

::: info Package
<pkg>app-dev-panel/adapter-spiral</pkg>
:::

## Configuration

Register the bootloader in your application `Kernel`:

```php
final class Kernel extends \Spiral\Framework\Kernel
{
    public function defineBootloaders(): array
    {
        return [
            // ... your app bootloaders ...
            \AppDevPanel\Adapter\Spiral\Bootloader\AppDevPanelBootloader::class,
        ];
    }
}
```

Add the two PSR-15 middlewares to your HTTP pipeline (outermost, before CSRF / sessions):

```php
// app/config/http.php
return [
    'middleware' => [
        \AppDevPanel\Adapter\Spiral\Middleware\AdpApiMiddleware::class,
        \AppDevPanel\Adapter\Spiral\Middleware\DebugMiddleware::class,
        // ... your middlewares ...
    ],
];
```

`AdpApiMiddleware` must precede `DebugMiddleware` so ADP's own `/debug/*` and
`/inspect/api/*` traffic short-circuits before the Debugger starts tracing its
own internal calls.

## Environment Variables

| Variable | Purpose | Default |
|----------|---------|---------|
| `APP_DEV_PANEL_STORAGE_PATH` | File storage directory for debug entries | `sys_get_temp_dir()/app-dev-panel` |
| `APP_DEV_PANEL_STATIC_URL` | Panel SPA asset URL (override the GitHub Pages CDN) | `https://app-dev-panel.github.io/app-dev-panel` |

## Collectors

The bootloader registers all framework-agnostic Kernel collectors plus a few that just
need a `collect()` call from your code:

`LogCollector`, `EventCollector`, `ExceptionCollector`, `HttpClientCollector`,
`VarDumperCollector`, `TimelineCollector`, `RequestCollector`, `WebAppInfoCollector`,
`FilesystemStreamCollector`, `CacheCollector`, `RouterCollector`, `ValidatorCollector`,
`TranslatorCollector`, `TemplateCollector`, `MailerCollector`, `QueueCollector`.

PSR services in your container are auto-decorated by the bootloader's `boot()` step:
`LoggerInterface` → `LoggerInterfaceProxy` (feeds `LogCollector`),
`EventDispatcherInterface` → `EventDispatcherInterfaceProxy` (feeds `EventCollector`),
`ClientInterface` (PSR-18) → `HttpClientInterfaceProxy` (feeds `HttpClientCollector`).

## Architecture

The adapter is intentionally tiny — three classes:

- `AppDevPanelBootloader` — Spiral `Bootloader` that registers ADP services as singletons
  and decorates PSR services in `boot()`.
- `DebugMiddleware` — PSR-15 middleware that wraps `Debugger::startup()` /
  `Debugger::shutdown()` around the rest of the pipeline. On exception it builds a
  synthetic `500` response carrying the `X-Debug-Id` header so the panel still surfaces
  the entry.
- `AdpApiMiddleware` — PSR-15 middleware that intercepts `/debug`, `/debug/api/*`,
  `/inspect/api/*` and forwards them to the framework-agnostic `ApiApplication`.

## Comparison with other adapters

| Aspect | Symfony / Laravel | Spiral |
|--------|-------------------|--------|
| Registration | Bundle / ServiceProvider | Bootloader |
| HTTP types | HttpFoundation → PSR-7 conversion | **PSR-7 native** — no conversion |
| Lifecycle hook | Kernel events (`kernel.request` / `kernel.terminate`) | PSR-15 middleware in the HTTP pipeline |
| ADP routes | Framework routes → catch-all controller | PSR-15 middleware intercepts ADP paths |

## Playground

A reference Spiral playground lives at `playground/spiral-app/`. It runs against PHP's
built-in web server on port `8105`:

```bash
make serve-spiral          # http://127.0.0.1:8105/
make fixtures-spiral       # CLI fixtures
make test-fixtures-spiral  # PHPUnit E2E
```
