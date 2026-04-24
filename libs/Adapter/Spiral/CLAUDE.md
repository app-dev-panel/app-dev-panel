# Spiral Adapter

Bridges ADP Kernel and API into Spiral Framework applications via the standard Bootloader system.
PSR-7/PSR-15 native — unlike Symfony/Laravel, no Request/Response conversion is needed because
Spiral uses PSR types end-to-end.

## Package

- Composer: `app-dev-panel/adapter-spiral`
- Namespace: `AppDevPanel\Adapter\Spiral\`
- PHP: 8.4+
- Spiral: 3.14+
- Dependencies: `app-dev-panel/kernel`, `app-dev-panel/api`, `app-dev-panel/cli`, `nyholm/psr7`, `spiral/boot`, `spiral/core`, `spiral/http`, `spiral/router`

## Directory Structure

```
src/
├── Bootloader/
│   └── AppDevPanelBootloader.php   # Registers all ADP services, decorates PSR services
├── Middleware/
│   ├── DebugMiddleware.php         # PSR-15: Debugger::startup() / shutdown() lifecycle
│   └── AdpApiMiddleware.php        # PSR-15: routes /debug|/debug/api|/inspect/api to ApiApplication
└── Controller/
    └── AdpApiController.php        # Alternative PSR-15 handler (for apps that prefer a controller)
```

## Usage

In a Spiral app (e.g., `spiral/app` skeleton), register the bootloader in your Kernel:

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

Then attach both middlewares to the HTTP pipeline (outermost, before CSRF/session):

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

## Environment Variables

| Variable | Purpose | Default |
|----------|---------|---------|
| `APP_DEV_PANEL_STORAGE_PATH` | File storage directory for debug entries | `sys_get_temp_dir()/app-dev-panel` |
| `APP_DEV_PANEL_STATIC_URL` | Panel SPA asset URL (override the GitHub CDN) | `https://app-dev-panel.github.io/app-dev-panel` |

## How It Works

1. **Bootloader** (`AppDevPanelBootloader`) registers all services as Spiral singletons:
   - Kernel infrastructure (`Debugger`, `StorageInterface`, `DebuggerIdGenerator`)
   - All enabled collectors (LogCollector, EventCollector, ExceptionCollector, HttpClientCollector,
     RequestCollector, WebAppInfoCollector, VarDumperCollector, TimelineCollector)
   - API services (`ApiApplication`, `CollectorRepository`, `ResponseDataWrapper`, `PanelController`)
   - PSR-17 factories (nyholm/psr7 implementations)
2. **boot()** decorates PSR services in the Spiral container:
   - `LoggerInterface` → `LoggerInterfaceProxy` (Kernel) so logs feed `LogCollector`
   - `EventDispatcherInterface` → `EventDispatcherInterfaceProxy` so events feed `EventCollector`
   - `ClientInterface` (PSR-18) → `HttpClientInterfaceProxy` so outbound HTTP feeds `HttpClientCollector`
3. **DebugMiddleware** maps the Spiral PSR-15 pipeline to the `Debugger` lifecycle:
   - Before next handler: `Debugger::startup()`, `WebAppInfoCollector` + `RequestCollector` start
   - Registers `Symfony\VarDumper::setHandler()` on first request so `dump()` feeds `VarDumperCollector`
   - Happy path: `RequestCollector::collectResponse()`, set `X-Debug-Id` header, `Debugger::shutdown()`
   - Exception path: `ExceptionCollector::collect()`, build 500 response carrying `X-Debug-Id`, shutdown, return
4. **AdpApiMiddleware** intercepts `/debug`, `/debug/api/*`, `/inspect/api/*` and delegates to
   `ApiApplication`. Everything else falls through to the next handler.

## Fixtures / Playground

The Spiral playground at `playground/spiral-app/` implements the `/test/fixtures/*` endpoint
contract defined by `libs/Testing/src/Fixture/FixtureRegistry.php`. Current coverage: **11 core
fixtures passing** — logs, logs-context, logs-heavy, events, var-dumper, timeline, request,
web-app-info, exception (runtime + chained), multi, http-client. Collectors that require framework
integrations the playground doesn't ship (database, cache, mailer, queue, validator, router, etc.)
are expected to fail until the corresponding Spiral bridges are added.

### Commands

```bash
make serve-spiral          # Start Spiral playground on port 8105
make fixtures-spiral       # Run CLI fixtures against it
make test-fixtures-spiral  # Run PHPUnit E2E fixtures
```

## Architecture Comparison

| Aspect | Symfony / Laravel | Spiral |
|--------|-------------------|--------|
| Registration | Bundle / ServiceProvider | Bootloader |
| DI wiring | Compiler pass / `boot()` closures | `defineSingletons()` + `boot()` |
| HTTP types | HttpFoundation → PSR-7 bridge (`nyholm/psr7-server`) | **PSR-7 native** — no conversion |
| Lifecycle hooks | Kernel events (`kernel.request`, `kernel.terminate`) | PSR-15 middleware in the HTTP pipeline |
| Routes | Framework routes → catch-all controller | PSR-15 middleware intercepts ADP paths |

Because Spiral is PSR-7/PSR-15 native, the adapter is the thinnest of the four — most of the work
is done in a single middleware that wraps `Debugger::startup() / shutdown()` around the pipeline.
