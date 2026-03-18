# Symfony Adapter

Bridges ADP Kernel and API into Symfony. Second adapter after Yii 3; reference implementation for creating new adapters.

## Package

- Composer: `app-dev-panel/adapter-symfony`
- Namespace: `AppDevPanel\Adapter\Symfony\`
- PHP: 8.4+
- Symfony: 6.4+ / 7.x / 8.x
- Dependencies: `app-dev-panel/kernel`, `app-dev-panel/api`, `app-dev-panel/cli`, `nyholm/psr7`, `nyholm/psr7-server`, `guzzlehttp/guzzle`, `guzzlehttp/psr7`, Symfony components (`config`, `dependency-injection`, `event-dispatcher`, `http-kernel`, `console`, `var-dumper`)
- Optional: `doctrine/dbal` (database inspector), `twig/twig`, `symfony/security-bundle`, `symfony/mailer`, `symfony/messenger`

## Directory Structure

```
src/
├── AppDevPanelBundle.php                           # Symfony Bundle entry point, registers CompilerPass
├── DependencyInjection/
│   ├── AppDevPanelExtension.php                    # Loads config, registers services, collectors, API
│   ├── CollectorProxyCompilerPass.php              # Decorates PSR services, builds Debugger, collects params
│   └── Configuration.php                           # Bundle config tree (app_dev_panel.yaml)
├── EventSubscriber/
│   ├── HttpSubscriber.php                          # kernel.request/response/exception/terminate → Debugger
│   ├── ConsoleSubscriber.php                       # console.command/error/terminate → Debugger
│   └── CorsSubscriber.php                          # CORS headers for debug API responses
├── Proxy/
│   └── SymfonyEventDispatcherProxy.php             # Wraps event_dispatcher, implements Component interface
├── Collector/
│   ├── DoctrineCollector.php                       # SQL queries, timing, params (requires doctrine/dbal)
│   ├── TwigCollector.php                           # Template renders, timing (requires twig/twig)
│   ├── SecurityCollector.php                       # User, roles, firewall
│   ├── CacheCollector.php                          # Cache hits/misses, operations
│   ├── MailerCollector.php                         # Sent emails
│   ├── MessengerCollector.php                      # Message bus operations
│   ├── SymfonyRequestCollector.php                 # Legacy: Symfony HttpFoundation collector (unused)
│   └── SymfonyExceptionCollector.php               # Legacy: Symfony exception collector (unused)
├── Inspector/
│   ├── SymfonyConfigProvider.php                   # 'config' alias: params, events, services, bundles
│   ├── DoctrineSchemaProvider.php                  # Database schema via DBAL (when doctrine available)
│   ├── NullSchemaProvider.php                      # Fallback when no database configured
│   ├── SymfonyRouteCollectionAdapter.php           # Route inspection adapter
│   ├── SymfonyRouteAdapter.php                     # Single route adapter
│   ├── SymfonyUrlMatcherAdapter.php                # URL matching adapter
│   └── SymfonyMatchResult.php                      # Match result DTO
└── Controller/
    └── AdpApiController.php                        # Symfony controller bridging to ADP ApiApplication
tests/
├── Integration/
│   ├── BundleBootstrapTest.php                     # Full container compilation + lifecycle
│   ├── LoggerProxyIntegrationTest.php              # Logger/event proxy wiring in compiled container
│   └── ConsoleProcessIntegrationTest.php           # Runs bin/console, verifies storage output
└── Unit/
    ├── DependencyInjection/                        # Extension, CompilerPass, Configuration tests
    ├── EventSubscriber/                            # HttpSubscriber, ConsoleSubscriber tests
    ├── Collector/                                  # Symfony-specific collector tests
    ├── Inspector/                                  # ConfigProvider, SchemaProvider, Route tests
    └── Proxy/                                      # SymfonyEventDispatcherProxy tests
docs/
├── integration-flow.md                             # Boot sequence and lifecycle mapping
├── collectors.md                                   # All collectors with data schemas
└── configuration.md                                # Configuration reference
```

## How It Works

### 1. Bundle Registration (`config/bundles.php`)

```php
AppDevPanel\Adapter\Symfony\AppDevPanelBundle::class => ['dev' => true, 'test' => true],
```

### 2. Configuration (`config/packages/app_dev_panel.yaml`)

`Configuration` defines the config tree. `AppDevPanelExtension` processes it and registers all services.

### 3. DI Registration (`AppDevPanelExtension`)

Registers in order:
- Core services: `DebuggerIdGenerator`, `StorageInterface` (FileStorage), `TimelineCollector`
- All enabled collectors (tagged `app_dev_panel.collector`)
- Event subscribers: `HttpSubscriber`, `ConsoleSubscriber`, `CorsSubscriber`
- API services: middleware stack, controllers, inspector endpoints
- Inspector: `SymfonyConfigProvider` as `config` alias, `DoctrineSchemaProvider` or `NullSchemaProvider`
- Bridge: `AdpApiController` maps Symfony routing to `ApiApplication`

### 4. Compiler Pass (`CollectorProxyCompilerPass`)

Runs after all extensions. Responsibilities:
- Collects all `app_dev_panel.collector` tagged services
- Builds `Debugger` with all collector references + `StorageInterface`
- Decorates `logger` or `LoggerInterface` → `LoggerInterfaceProxy` (checks `logger` first, then FQCN)
- Decorates `event_dispatcher` → `SymfonyEventDispatcherProxy` (implements `Symfony\Component\EventDispatcher\EventDispatcherInterface`)
- Decorates `ClientInterface` (PSR-18) → `HttpClientInterfaceProxy`
- Collects all container parameters (excluding `app_dev_panel.*`) → passes to `InspectController` and `SymfonyConfigProvider`

### 5. Event Wiring

**Web events (`HttpSubscriber`):**

| Symfony Event | Priority | ADP Action |
|---------------|----------|------------|
| `kernel.request` | 1024 | Convert Symfony Request → PSR-7, `Debugger::startup()`, `RequestCollector`, `WebAppInfoCollector` |
| `kernel.response` | -1024 | Convert Symfony Response → PSR-7, `RequestCollector` captures response, adds `X-Debug-Id` header |
| `kernel.exception` | 0 | `ExceptionCollector` captures throwable |
| `kernel.terminate` | -2048 | `Debugger::shutdown()` flushes data to storage |

**Console events (`ConsoleSubscriber`):**

| Symfony Event | Priority | ADP Action |
|---------------|----------|------------|
| `console.command` | 1024 | `Debugger::startup()`, `CommandCollector`, `ConsoleAppInfoCollector` |
| `console.error` | 0 | `ExceptionCollector`, `CommandCollector` |
| `console.terminate` | -2048 | `CommandCollector`, `Debugger::shutdown()` |

### 6. Proxy Wiring Details

**Logger**: Uses Symfony `setDecoratedService()`. Checks for `logger` service ID first (Symfony canonical), falls back to `Psr\Log\LoggerInterface` FQCN. Wraps with Kernel's `LoggerInterfaceProxy`.

**Event dispatcher**: Decorates `event_dispatcher` service with `SymfonyEventDispatcherProxy`. Must implement `Symfony\Component\EventDispatcher\EventDispatcherInterface` (not PSR-14) because:
- Symfony's `dispatch()` has a second `?string $eventName` parameter
- `SymfonyConfigProvider` checks `instanceof EventDispatcherInterface` to call `getListeners()`
- Symfony container passes lazy subscriber arrays `[$service, 'method']` to `addListener()` — requires `callable|array` signature

**HTTP client**: Standard PSR-18 decoration via `HttpClientInterfaceProxy`.

### 7. Inspector Integration

`SymfonyConfigProvider` is registered as the `config` service alias:

| Group | Method | Source |
|-------|--------|--------|
| `params` / `parameters` | Returns `$containerParameters` | Collected by compiler pass |
| `events` / `events-web` | Calls `$dispatcher->getListeners()` | Introspects `event_dispatcher` |
| `di` / `services` | Calls `$container->getServiceIds()` | Lists all DI services |
| `bundles` | Returns `$bundleConfig` | Bundle configuration |

`InspectController` receives container parameters as 3rd constructor argument for the `/inspect/api/params` endpoint.

`DoctrineSchemaProvider` implements `SchemaProviderInterface` for `/inspect/api/table` endpoints when `doctrine.dbal.default_connection` is available. Falls back to `NullSchemaProvider`.

## Collectors

### Kernel Collectors (Reused via Proxies)

| Collector | Proxy | Data |
|-----------|-------|------|
| `RequestCollector` | — (fed by `HttpSubscriber`) | PSR-7 request/response |
| `LogCollector` | `LoggerInterfaceProxy` | PSR-3 log entries |
| `EventCollector` | `SymfonyEventDispatcherProxy` | Dispatched events with timing |
| `ExceptionCollector` | — (fed by `HttpSubscriber`/`ConsoleSubscriber`) | Uncaught exceptions |
| `HttpClientCollector` | `HttpClientInterfaceProxy` | PSR-18 HTTP requests/responses |
| `ServiceCollector` | — | Service method calls |
| `VarDumperCollector` | — | `dump()` calls |
| `TimelineCollector` | — | Cross-collector timing data |
| `FilesystemStreamCollector` | — | File I/O operations |
| `HttpStreamCollector` | — | Stream HTTP operations |
| `CommandCollector` | — (fed by `ConsoleSubscriber`) | Console commands |
| `WebAppInfoCollector` | — (fed by `HttpSubscriber`) | Web app metadata |
| `ConsoleAppInfoCollector` | — (fed by `ConsoleSubscriber`) | Console app metadata |

### Symfony-Specific Collectors

| Collector | Fed by | Data |
|-----------|--------|------|
| `DoctrineCollector` | DBAL middleware calling `logQuery()` | SQL queries, params, time, backtrace |
| `TwigCollector` | Profiler extension calling `logRender()` | Template names, render times |
| `SecurityCollector` | Security event listener | User, roles, firewall, access decisions |
| `CacheCollector` | Decorated cache adapter calling `logCacheOperation()` | Cache operations, hits/misses |
| `MailerCollector` | Mailer MessageEvent listener | Emails sent |
| `MessengerCollector` | Messenger middleware calling `logMessage()` | Messages dispatched/handled/failed |

## Configuration Reference

```yaml
app_dev_panel:
    enabled: true                          # Master switch
    storage:
        path: '%kernel.project_dir%/var/debug'
        history_size: 50
    collectors:
        request: true                      # RequestCollector (Kernel, PSR-7)
        exception: true                    # ExceptionCollector (Kernel)
        log: true                          # LogCollector (Kernel)
        event: true                        # EventCollector (Kernel)
        service: true                      # ServiceCollector
        http_client: true                  # HttpClientCollector
        timeline: true                     # TimelineCollector
        var_dumper: true                   # VarDumperCollector
        filesystem_stream: true            # FilesystemStreamCollector
        http_stream: true                  # HttpStreamCollector
        command: true                      # CommandCollector
        doctrine: true                     # DoctrineCollector (requires doctrine/dbal)
        twig: true                         # TwigCollector (requires twig/twig)
        security: true                     # SecurityCollector (requires symfony/security-bundle)
        cache: true                        # CacheCollector
        mailer: true                       # MailerCollector (requires symfony/mailer)
        messenger: true                    # MessengerCollector (requires symfony/messenger)
    ignored_requests:
        - '/_wdt/*'
        - '/_profiler/*'
        - '/debug/api/*'
    ignored_commands:
        - 'completion'
        - 'help'
        - 'list'
        - 'debug:*'
        - 'cache:*'
    dumper:
        excluded_classes: []
    api:
        enabled: true
        allowed_ips: ['127.0.0.1', '::1']
        auth_token: ''
```

## Architecture Comparison: Yii vs Symfony

| Aspect | Yii 3 Adapter | Symfony Adapter |
|--------|--------------|-----------------|
| Registration | Config plugin auto-load | Bundle + Extension |
| DI wiring | `di.php` + `di-providers.php` | `AppDevPanelExtension` + `CompilerPass` |
| Proxy decoration | Service provider | `setDecoratedService()` in compiler pass |
| Event proxy | Kernel's `EventDispatcherInterfaceProxy` (PSR-14) | `SymfonyEventDispatcherProxy` (Symfony Component) |
| Event mapping | `events-web.php` / `events-console.php` | `EventSubscriberInterface` classes |
| Config | `params.php` (flat array) | `Configuration` tree builder |
| Request collector | Kernel's `RequestCollector` (native PSR-7) | Kernel's `RequestCollector` (converted from HttpFoundation) |
| PSR-7 bridge | Native (Yii uses PSR-7) | `nyholm/psr7-server` conversion for request + response |
| Inspector config | Application config service | `SymfonyConfigProvider` as `config` alias |
| Database inspector | `DbSchemaProvider` in Yiisoft adapter | `DoctrineSchemaProvider` (DBAL) or `NullSchemaProvider` |

## Creating a New Adapter

Use this Symfony adapter as a reference. A new adapter must:

1. **Register the Debugger** with all collectors and storage
2. **Wire event lifecycle**: map framework's request/command start/end events to `Debugger::startup()` / `shutdown()`
3. **Decorate PSR services** with Kernel proxies: `LoggerInterfaceProxy`, `HttpClientInterfaceProxy`, and a framework-specific event dispatcher proxy if needed
4. **Convert to PSR-7**: if framework doesn't use PSR-7 natively, convert request/response for `RequestCollector` and `StartupContext::forRequest()`
5. **Register inspector services**: `SymfonyConfigProvider`-equivalent as `config` alias, `SchemaProviderInterface` for database
6. **Pass container parameters** to `InspectController` (3rd constructor arg)
7. **Bridge API**: route `/debug/api/*` and `/inspect/api/*` to `ApiApplication`
