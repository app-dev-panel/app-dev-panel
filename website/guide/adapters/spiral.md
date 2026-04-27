---
description: "Install and configure ADP for Spiral Framework 3.x. Bootloader registration, PSR-15 middleware pipeline, fixture endpoints."
---

# Spiral Adapter

The Spiral adapter bridges ADP Kernel and API into Spiral Framework 3.14+ via two
Bootloaders. Because Spiral is PSR-7/PSR-15 native — and exposes
`Container::bindInjector()` plus `InterceptorInterface` as first-class extension points —
the adapter is also the most idiomatic of the full adapters: no HttpFoundation /
Illuminate Request bridges, no compiler passes, no imperative service rebinding in
`boot()`.

## Installation

```bash
composer require app-dev-panel/adapter-spiral --dev
```

::: info Package
<pkg>app-dev-panel/adapter-spiral</pkg>
:::

## Setup

Register both bootloaders in your application `Kernel`:

```php
final class Kernel extends \Spiral\Framework\Kernel
{
    public function defineBootloaders(): array
    {
        return [
            // ... your app bootloaders ...
            \AppDevPanel\Adapter\Spiral\Bootloader\AppDevPanelBootloader::class,
            \AppDevPanel\Adapter\Spiral\Bootloader\AdpInterceptorBootloader::class,
        ];
    }
}
```

`AdpInterceptorBootloader` declares `AppDevPanelBootloader` as a dependency and registers
the console / queue interceptors with the host bootloaders only when `spiral/console` /
`spiral/queue` are installed — registering it with both packages absent is a no-op.

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

## Configuration

`AdpConfig` is a `Spiral\Core\InjectableConfig`. Defaults match the env-var-only setup
shipped historically — override them by adding `app/config/app-dev-panel.php`:

```php
return [
    'enabled' => true,
    'storage' => [
        'path' => directory('runtime') . 'debug',
        'history_size' => 50,
    ],
    'panel' => [
        'static_url' => null,
        'base_path' => '/debug',
    ],
    'ignored_requests' => ['/health', '/_status/*'],
    'ignored_commands' => ['cache:*', 'list', 'help'],
    'collectors' => [
        'mailer' => false,
        // ... per-collector toggles ...
    ],
];
```

Each `AdpConfig::*()` accessor falls back to an `APP_DEV_PANEL_*` environment variable
when its value is left at the default `null`, so apps without an
`app/config/app-dev-panel.php` keep working:

| Variable | Maps to | Default |
|----------|---------|---------|
| `APP_DEV_PANEL_STORAGE_PATH` | `storage.path` | `sys_get_temp_dir()/app-dev-panel` |
| `APP_DEV_PANEL_STATIC_URL` | `panel.static_url` | Panel SPA GitHub Pages CDN |
| `APP_DEV_PANEL_ROOT_PATH` | path resolver root | playground entry point sets this |
| `APP_DEV_PANEL_RUNTIME_PATH` | runtime directory hint | derived from root + `runtime/` |

## Collectors

The bootloader registers every framework-agnostic Kernel collector:

`LogCollector`, `EventCollector`, `ExceptionCollector`, `HttpClientCollector`,
`VarDumperCollector`, `TimelineCollector`, `RequestCollector`, `WebAppInfoCollector`,
`FilesystemStreamCollector`, `CacheCollector`, `RouterCollector`, `ValidatorCollector`,
`TranslatorCollector`, `TemplateCollector`, `MailerCollector`, `QueueCollector`,
`CommandCollector`, `ConsoleAppInfoCollector`.

When the user app's container exposes the matching interface, the **container injectors**
described below transparently wrap the binding so the collector is fed automatically — no
imperative `collect()` calls required.

## Architecture

The adapter is built on Spiral 3's two first-class extension points instead of
imperative `boot()` rebinding:

- **`Container::bindInjector(string $type, InjectorInterface $injector)`** — the canonical
  mechanism to wrap any binding of `$type` with a decorator. Used to auto-decorate every
  PSR / Spiral service the Kernel can collect from.
- **`InterceptorInterface`** — Spiral's per-domain middleware (console commands, queue
  consumers, route handlers). Used to manage the `Debugger::startup()` / `Debugger::shutdown()`
  lifecycle outside the HTTP pipeline.

The moving parts:

- `AppDevPanelBootloader` — registers ADP services as singletons, installs the eight
  container injectors, and binds the inspector providers under the duck-typed aliases
  the inspector controllers expect (`'config'`, `'router'`, `'urlMatcher'`).
- `AdpInterceptorBootloader` — depends on `AppDevPanelBootloader` and wires the three
  interceptors into their host registries (`ConsoleBootloader::addInterceptor()`,
  `QueueRegistry::addConsumeInterceptor()`).
- `DebugMiddleware` — PSR-15 middleware that wraps `Debugger::startup()` /
  `Debugger::shutdown()` around the rest of the pipeline. On exception it builds a
  synthetic `500` response carrying the `X-Debug-Id` header so the panel still surfaces
  the entry.
- `AdpApiMiddleware` — PSR-15 middleware that intercepts `/debug`, `/debug/api/*`,
  `/inspect/api/*` and forwards them to the framework-agnostic `ApiApplication`.

## Container Injectors

Spiral 3's container exposes `bindInjector(string $type, InjectorInterface $injector)` —
the canonical mechanism to wrap any binding of `$type` with a decorator. The Spiral
adapter ships eight injectors that hook into ADP collectors automatically when their
interface is present in the user app's container:

| Interface | Injector | Wraps with | Feeds collector |
|-----------|----------|------------|-----------------|
| `Psr\Log\LoggerInterface` | `LoggerProxyInjector` | `LoggerInterfaceProxy` (Kernel) | `LogCollector` |
| `Psr\EventDispatcher\EventDispatcherInterface` | `EventDispatcherProxyInjector` | `EventDispatcherInterfaceProxy` (Kernel) | `EventCollector` |
| `Psr\Http\Client\ClientInterface` | `HttpClientProxyInjector` | `HttpClientInterfaceProxy` (Kernel) | `HttpClientCollector` |
| `Psr\SimpleCache\CacheInterface` | `CacheProxyInjector` | `Psr16CacheProxy` | `CacheCollector` |
| `Spiral\Mailer\MailerInterface` | `MailerProxyInjector` | `TracingMailer` | `MailerCollector` |
| `Spiral\Queue\QueueInterface` | `QueueProxyInjector` | `TracingQueue` (push side) | `QueueCollector` |
| `Spiral\Translator\TranslatorInterface` | `TranslatorProxyInjector` | `TracingTranslator` | `TranslatorCollector` |
| `Spiral\Views\ViewsInterface` | `ViewsProxyInjector` | `TracingViews` | `TemplateCollector` |

Each injector resolves the original binding eagerly (so `bindInjector` doesn't lose
the underlying service), removes the slot, then re-binds the injector class. Subsequent
`$container->get($iface)` calls land in `createInjection()`, which returns the matching
proxy wrapping the captured underlying. A lazy `resolveUnderlying()` fallback covers
the case where the app rebinds the interface after the bootloader runs.

All optional Spiral packages (`spiral/mailer`, `spiral/queue`, `spiral/translator`,
`spiral/views`) and PSR-16 (`psr/simple-cache`) injectors are gated by `interface_exists`
so the bootloader is safe with any subset of those packages installed.

## Interceptors

`AdpInterceptorBootloader` registers three Spiral `InterceptorInterface` implementations
with their host bootloaders:

| Domain | Interceptor | Registry |
|--------|-------------|----------|
| Console commands | `DebugConsoleInterceptor` | `Spiral\Console\Bootloader\ConsoleBootloader::addInterceptor()` |
| Queue jobs (consume side) | `DebugQueueInterceptor` | `Spiral\Queue\QueueRegistry::addConsumeInterceptor()` |
| Per-route HTTP handlers | `DebugRouteInterceptor` | `RouteInterface::withInterceptors()` (per-route opt-in) |

Each interceptor manages the `Debugger::startup()` / `Debugger::shutdown()` lifecycle
for its domain — so console commands and queue handlers get their own debug entries
without writing PSR-15 middleware. The console interceptor additionally feeds
`CommandCollector`, `ConsoleAppInfoCollector`, and on throw `ExceptionCollector`.

## Inspector providers

Five Spiral-aware providers are bound under the duck-typed container aliases the inspector
controllers expect (`'config'`, `'router'`, `'urlMatcher'`,
`AuthorizationConfigProviderInterface`) plus an internal `SpiralEventListenerProvider`.
Each unlocks a previously 501'd endpoint:

| Endpoint | Provider | Source |
|----------|----------|--------|
| `/inspect/api/config?group=di` | `SpiralConfigProvider::getServices()` | `Spiral\Core\Container::getBindings()` |
| `/inspect/api/config?group=params` | `SpiralConfigProvider::getParams()` | `EnvironmentInterface` + `DirectoriesInterface` |
| `/inspect/api/config?group=bundles` | `SpiralConfigProvider::getBootloaders()` | `BootloadManager\InitializerInterface` |
| `/inspect/api/events` | `SpiralEventListenerProvider` | `Spiral\Events\ListenerRegistryInterface` |
| `/inspect/api/routes` | `SpiralRouteCollectionAdapter` | `Spiral\Router\RouterInterface::getRoutes()` |
| `/inspect/api/route/check` | `SpiralUrlMatcherAdapter` | `Router::matchRoute()` |
| `/inspect/api/authorization` | `SpiralAuthorizationConfigProvider` | `Spiral\Auth\TokenStorageInterface` + `ActorProviderInterface` |

`/routes`, `/events`, and `/authorization` only return data when their backing Spiral
component is actually installed in the user app; otherwise the providers gracefully
return empty arrays — never 501.

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
