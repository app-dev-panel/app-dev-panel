# Spiral Adapter

Bridges ADP Kernel and API into Spiral Framework applications via the standard Bootloader system.
PSR-7/PSR-15 native — unlike Symfony/Laravel, no Request/Response conversion is needed because
Spiral uses PSR types end-to-end. The adapter leans on Spiral's two extension points —
`Container::bindInjector()` for transparent service decoration and `InterceptorInterface` for
console/queue/route lifecycle hooks — instead of imperative bootloader rebinding.

## Package

- Composer: `app-dev-panel/adapter-spiral`
- Namespace: `AppDevPanel\Adapter\Spiral\`
- PHP: 8.4+
- Spiral: 3.14+
- Required: `app-dev-panel/kernel`, `app-dev-panel/api`, `app-dev-panel/cli`, `nyholm/psr7`, `spiral/boot`, `spiral/core`, `spiral/http`, `spiral/router`
- Optional (auto-detected via `interface_exists`): `spiral/console`, `spiral/queue`, `spiral/interceptors`, `spiral/auth`, `spiral/events`, `spiral/mailer`, `spiral/translator`, `spiral/views`

## Directory Structure

```
src/
├── Bootloader/
│   ├── AppDevPanelBootloader.php   # Singletons + container injectors + inspector providers
│   └── AdpInterceptorBootloader.php # Console/Queue interceptor wiring (depends on AppDevPanelBootloader)
├── Config/
│   └── AdpConfig.php               # Spiral InjectableConfig — typed defaults + env-var fallback
├── Container/
│   ├── InjectorTrait.php           # setUnderlying() / resolveUnderlying() shared logic
│   ├── LoggerProxyInjector.php     # PSR-3   → LoggerInterfaceProxy           → LogCollector
│   ├── EventDispatcherProxyInjector.php # PSR-14 → EventDispatcherInterfaceProxy → EventCollector
│   ├── HttpClientProxyInjector.php # PSR-18  → HttpClientInterfaceProxy        → HttpClientCollector
│   ├── CacheProxyInjector.php      # PSR-16  → Psr16CacheProxy                 → CacheCollector
│   ├── MailerProxyInjector.php     # spiral/mailer    → TracingMailer        → MailerCollector
│   ├── QueueProxyInjector.php      # spiral/queue     → TracingQueue (push)  → QueueCollector
│   ├── TranslatorProxyInjector.php # spiral/translator→ TracingTranslator    → TranslatorCollector
│   └── ViewsProxyInjector.php      # spiral/views     → TracingViews         → TemplateCollector
├── Inspector/
│   ├── SpiralConfigProvider.php            # 'config' → bindings / params / bootloaders / events
│   ├── SpiralRouteCollectionAdapter.php    # 'router' alias for /inspect/api/routes
│   ├── SpiralRouteAdapter.php              # per-route __debugInfo() shape
│   ├── SpiralUrlMatcherAdapter.php         # 'urlMatcher' alias for /inspect/api/route/check
│   ├── SpiralMatchResult.php               # match() return type with isSuccess()/route()->middlewares
│   ├── SpiralAuthorizationConfigProvider.php # spiral/auth introspection
│   └── SpiralEventListenerProvider.php     # spiral/events listener registry reflection reader
├── Interceptor/
│   ├── DebugConsoleInterceptor.php # spiral/console — Debugger lifecycle around each command
│   ├── DebugQueueInterceptor.php   # spiral/queue (consume side) — one debug entry per job
│   └── DebugRouteInterceptor.php   # opt-in per route via Route::withInterceptors()
├── Mailer/TracingMailer.php
├── Queue/TracingQueue.php
├── Translator/TracingTranslator.php
├── View/TracingViews.php
├── Middleware/
│   ├── DebugMiddleware.php         # PSR-15: Debugger::startup() / shutdown() around the pipeline
│   └── AdpApiMiddleware.php        # PSR-15: routes /debug|/debug/api|/inspect/api to ApiApplication
└── Controller/
    └── AdpApiController.php        # Alternative PSR-15 handler for apps that prefer a controller
```

## Usage

In a Spiral app, register both bootloaders in your Kernel:

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

`AdpInterceptorBootloader` declares `AppDevPanelBootloader` as a dependency and registers the
console/queue interceptors with the host bootloaders only when `spiral/console` /
`spiral/queue` are installed — registering it with both packages absent is a no-op.

Attach the two HTTP middlewares (outermost, before CSRF/session):

```yaml
# app/config/http.php
'middleware' => [
    \AppDevPanel\Adapter\Spiral\Middleware\AdpApiMiddleware::class,
    \AppDevPanel\Adapter\Spiral\Middleware\DebugMiddleware::class,
    // ... your middlewares ...
],
```

`AdpApiMiddleware` must precede `DebugMiddleware` so ADP's own `/debug/*` and `/inspect/api/*`
traffic short-circuits before the Debugger starts tracing its own internal calls.

## Configuration

`AdpConfig` is a `Spiral\Core\InjectableConfig`. The defaults match the env-var-only setup
shipped historically; override them with `app/config/app-dev-panel.php`:

```php
return [
    'enabled' => true,
    'storage' => ['path' => directory('runtime') . 'debug', 'history_size' => 50],
    'panel' => ['static_url' => null, 'base_path' => '/debug'],
    'ignored_requests' => ['/debug/api/**', '/debug', '/inspect/api/**'],
    'ignored_commands' => ['help', 'list', 'completion'],
    'collectors' => ['mailer' => false /* …per-collector toggles */],
];
```

Each `AdpConfig::*()` accessor falls back to an `APP_DEV_PANEL_*` env var when its value is
left at the default `null`, so apps without an `app/config/app-dev-panel.php` keep working:

| Variable | Maps to | Default |
|---|---|---|
| `APP_DEV_PANEL_STORAGE_PATH` | `storage.path`     | `sys_get_temp_dir()/app-dev-panel` |
| `APP_DEV_PANEL_STATIC_URL`   | `panel.static_url` | `PanelConfig::DEFAULT_STATIC_URL` |
| `APP_DEV_PANEL_ROOT_PATH`    | path resolver root | playground entry point sets this |
| `APP_DEV_PANEL_RUNTIME_PATH` | runtime dir hint   | derived from root + `runtime/` |

## How It Works

### 1. Container injectors (`Container/*ProxyInjector`)

Spiral 3 exposes `bindInjector(string $type, class-string<InjectorInterface>)` — the canonical
mechanism to wrap any binding of `$type` with a decorator. The bootloader installs eight
injectors via a single helper `installInjector()`:

```
$container->get($iface)                     // resolve original (Monolog/Guzzle/…)
   → injector::setUnderlying($original)     // capture eagerly (bindInjector overwrites the slot)
   → $binder->removeBinding($iface)         // free the slot
   → $binder->bindInjector($iface, Class::class)
```

Subsequent `$container->get($iface)` calls land in the injector's `createInjection()`, which
returns the matching Kernel/adapter proxy wrapping the captured underlying. `InjectorTrait`
also exposes a lazy `resolveUnderlying()` fallback used when an app rebinds the interface
AFTER the bootloader runs.

Optional Spiral packages are guarded with `interface_exists` — the cache/mailer/queue/
translator/views injectors are no-ops when the corresponding `spiral/*` package isn't
installed.

### 2. Inspector providers (`Inspector/*`)

Bound under the duck-typed container aliases the inspector controllers expect:

| Endpoint | Container alias | Provider | Source |
|---|---|---|---|
| `/inspect/api/config?group=di` | `'config'` | `SpiralConfigProvider::getServices()` | `Container::getBindings()` |
| `/inspect/api/config?group=params` | `'config'` | `SpiralConfigProvider::getParams()` | `EnvironmentInterface` + `DirectoriesInterface` |
| `/inspect/api/config?group=bundles` | `'config'` | `SpiralConfigProvider::getBootloaders()` | `BootloadManager\InitializerInterface` |
| `/inspect/api/events` | `'config'` (events group) | `SpiralEventListenerProvider` | `Spiral\Events\ListenerRegistryInterface` (reflection) |
| `/inspect/api/routes` | `'router'` | `SpiralRouteCollectionAdapter` | `RouterInterface::getRoutes()` |
| `/inspect/api/route/check` | `'urlMatcher'` | `SpiralUrlMatcherAdapter` | `RouteInterface::match()` per route |
| `/inspect/api/authorization` | `AuthorizationConfigProviderInterface` | `SpiralAuthorizationConfigProvider` | `Spiral\Auth\TokenStorageInterface` + `ActorProviderInterface` |

`/routes`, `/events`, `/authorization` return empty arrays — never 501 — when their backing
Spiral package is absent.

### 3. Interceptors (`Interceptor/*`)

Three Spiral `InterceptorInterface` implementations registered by `AdpInterceptorBootloader`:

| Domain | Interceptor | Hookpoint |
|---|---|---|
| Console commands | `DebugConsoleInterceptor` | `ConsoleBootloader::addInterceptor()` |
| Queue jobs (consume) | `DebugQueueInterceptor` | `QueueRegistry::addConsumeInterceptor()` |
| Per-route HTTP | `DebugRouteInterceptor` | `Route::withInterceptors()` (opt-in) |

Each interceptor manages its own `Debugger::startup()/shutdown()`, so console commands and
queue handlers produce standalone debug entries without writing PSR-15 middleware. The
console interceptor additionally feeds `CommandCollector`, `ConsoleAppInfoCollector`, and
on throw `ExceptionCollector`.

### 4. Bootloader singletons

`AppDevPanelBootloader::SINGLETONS` registers all Kernel infrastructure (`Debugger`,
`StorageInterface`, `DebuggerIdGenerator`), every collector, API services (`ApiApplication`,
`CollectorRepository`, `ResponseDataWrapper`, `PanelController`), PSR-17 factories
(nyholm/psr7), and the eight injectors. `boot()` calls `installInjector()` once per
interface, wires the inspector aliases, and (when `spiral/router` is installed and bound)
exposes the `'router'` / `'urlMatcher'` adapters.

### 5. DebugMiddleware

Maps the PSR-15 pipeline to the Debugger lifecycle:

- Before next handler: `Debugger::startup()`, `WebAppInfoCollector` + `RequestCollector` start.
- Registers `Symfony\VarDumper::setHandler()` on first request so `dump()` feeds `VarDumperCollector`.
- Happy path: `RequestCollector::collectResponse()`, set `X-Debug-Id` header, `Debugger::shutdown()`.
- Exception path: `ExceptionCollector::collect()`, build 500 response carrying `X-Debug-Id`, shutdown, return.

## Tests

```
tests/
├── Unit/
│   ├── Bootloader/AdpInterceptorBootloaderTest.php
│   ├── Config/AdpConfigTest.php
│   ├── Container/{Cache,EventDispatcher,HttpClient,Logger,Mailer,Queue,Translator,Views}ProxyInjectorTest.php
│   ├── Container/{ContainerStubsBootstrap,container-stubs}.php
│   ├── Inspector/{SpiralConfig,SpiralRouteCollection,SpiralUrlMatcher,SpiralAuthorizationConfig,SpiralEventListener}ProviderTest.php
│   ├── Inspector/{SpiralStubsBootstrap,spiral-stubs}.php
│   └── Interceptor/{DebugConsole,DebugQueue,DebugRoute}InterceptorTest.php
│   └── Interceptor/{InterceptorStubsBootstrap,interceptor-stubs}.php
└── Integration/
    └── RoadRunnerLifecycleTest.php   # debugger survives N PSR-15 invocations on a reused container
```

The `*StubsBootstrap` + `*-stubs.php` files declare minimal interface/class shims for the
Spiral packages NOT brought into the root `vendor/` (only `spiral/core` is). When the real
package is installed the `interface_exists`/`class_exists` guards in the stub files
short-circuit and the autoloader-loaded definitions take precedence.

## Fixtures / Playground

`playground/spiral-app/` runs on port 8105. Current coverage: 23 of 29 fixtures passing —
all the foundational ones (logs, events, http-client, exception, request, web-app-info,
var-dumper, timeline, router, multi, plus the database fixture via cycle/database +
`CycleSchemaProvider`). The remaining 6 (`security`, `redis`, `opentelemetry`,
`elasticsearch`, `coverage`, `assets`) need framework integrations the playground doesn't
ship.

```bash
make serve-spiral          # Start Spiral playground on port 8105
make fixtures-spiral       # Run CLI fixtures against it
make test-fixtures-spiral  # Run PHPUnit E2E fixtures
```

## Architecture Comparison

| Aspect | Symfony / Laravel | Spiral |
|--------|-------------------|--------|
| Registration | Bundle / ServiceProvider | Bootloader |
| DI wiring | Compiler pass / `boot()` closures | `defineSingletons()` + `bindInjector` |
| Service decoration | Compiler pass rebinds id → decorator | `Container::bindInjector(iface, InjectorClass)` |
| Cross-cutting hooks | Kernel events (`kernel.request`, `console.command`) | `InterceptorInterface` per domain |
| HTTP types | HttpFoundation → PSR-7 bridge | **PSR-7 native** |
| Lifecycle hooks | Kernel events (`kernel.request`, `kernel.terminate`) | PSR-15 middleware in the HTTP pipeline |
| Routes | Framework routes → catch-all controller | PSR-15 middleware intercepts ADP paths |

Because Spiral exposes `bindInjector` + `InterceptorInterface` as first-class extension
points, the adapter is the most idiomatic of the four — the bootloader doesn't rebind
services imperatively; it tells the container how to wrap them, and the host framework's
own dispatcher does the rest.
