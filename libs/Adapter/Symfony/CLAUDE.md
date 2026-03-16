# Symfony Adapter

Bridges the ADP Kernel and API into the Symfony framework. Second adapter implementation after Yii 3.

## Package

- Composer: `app-dev-panel/adapter-symfony`
- Namespace: `AppDevPanel\Adapter\Symfony\`
- PHP: 8.4+
- Symfony: 6.4+ / 7.x / 8.x
- Dependencies: `app-dev-panel/kernel`, `app-dev-panel/cli`, `nyholm/psr7`, Symfony components

## Directory Structure

```
src/
├── AppDevPanelBundle.php                           # Symfony Bundle entry point
├── DependencyInjection/
│   ├── AppDevPanelExtension.php                    # Loads config, registers services and collectors
│   ├── CollectorProxyCompilerPass.php              # Decorates PSR services with proxies, builds Debugger
│   └── Configuration.php                           # Bundle configuration tree (app_dev_panel.yaml)
├── EventSubscriber/
│   ├── HttpSubscriber.php                          # kernel.request/response/exception/terminate → Debugger
│   └── ConsoleSubscriber.php                       # console.command/error/terminate → Debugger
└── Collector/
    ├── SymfonyRequestCollector.php                 # HTTP request/response (Symfony HttpFoundation)
    ├── DoctrineCollector.php                       # SQL queries, timing, params
    ├── TwigCollector.php                           # Template renders, timing
    ├── SecurityCollector.php                       # User, roles, firewall, access decisions
    ├── CacheCollector.php                          # Cache hits/misses, operations
    ├── MailerCollector.php                         # Sent emails (from, to, subject)
    └── MessengerCollector.php                      # Dispatched/handled/failed messages
config/
docs/
├── integration-flow.md                             # Boot sequence and lifecycle mapping
├── collectors.md                                   # All collectors with data schemas
└── configuration.md                                # Configuration reference
tests/
```

## How It Works

### 1. Bundle Registration (`config/bundles.php`)

```php
AppDevPanel\Adapter\Symfony\AppDevPanelBundle::class => ['dev' => true, 'test' => true],
```

### 2. Configuration (`config/packages/app_dev_panel.yaml`)

The `Configuration` class defines the config tree. The `AppDevPanelExtension` processes it and registers services.

### 3. DI Registration (`AppDevPanelExtension`)

Registers:
- Core services: `DebuggerIdGenerator`, `StorageInterface` (FileStorage), `TimelineCollector`
- All enabled collectors (tagged `app_dev_panel.collector`)
- Event subscribers: `HttpSubscriber`, `ConsoleSubscriber`

### 4. Compiler Pass (`CollectorProxyCompilerPass`)

Runs after all extensions. Responsibilities:
- Collects all `app_dev_panel.collector` tagged services
- Builds `Debugger` with all collector references
- Decorates `LoggerInterface` → `LoggerInterfaceProxy`
- Decorates `EventDispatcherInterface` → `EventDispatcherInterfaceProxy`
- Decorates `ClientInterface` (PSR-18) → `HttpClientInterfaceProxy`

### 5. Event Wiring

**Web events (`HttpSubscriber`):**

| Symfony Event | Priority | ADP Action |
|---------------|----------|------------|
| `kernel.request` | 1024 | `Debugger::startup()`, `RequestCollector`, `WebAppInfoCollector` |
| `kernel.response` | -1024 | `RequestCollector` captures response, adds `X-Debug-Id` header |
| `kernel.exception` | 0 | `ExceptionCollector` captures throwable |
| `kernel.terminate` | -2048 | `Debugger::shutdown()` flushes data to storage |

**Console events (`ConsoleSubscriber`):**

| Symfony Event | Priority | ADP Action |
|---------------|----------|------------|
| `console.command` | 1024 | `Debugger::startup()`, `CommandCollector`, `ConsoleAppInfoCollector` |
| `console.error` | 0 | `ExceptionCollector`, `CommandCollector` |
| `console.terminate` | -2048 | `CommandCollector`, `Debugger::shutdown()` |

## Collectors

### Kernel Collectors (Reused)

| Collector | Data |
|-----------|------|
| `LogCollector` | PSR-3 log entries (level, message, context) |
| `EventCollector` | Dispatched events with timing |
| `ServiceCollector` | Service method calls |
| `HttpClientCollector` | PSR-18 HTTP requests/responses |
| `ExceptionCollector` | Uncaught exceptions with chain |
| `VarDumperCollector` | `dump()` calls |
| `TimelineCollector` | Cross-collector timing data |
| `FilesystemStreamCollector` | File I/O operations |
| `HttpStreamCollector` | Stream HTTP operations |
| `CommandCollector` | Console command execution |
| `WebAppInfoCollector` | App metadata (web) |
| `ConsoleAppInfoCollector` | App metadata (console) |

### Symfony-Specific Collectors

| Collector | Data |
|-----------|------|
| `SymfonyRequestCollector` | Request/response via HttpFoundation (route, controller, headers) |
| `DoctrineCollector` | SQL queries, params, execution time, backtrace |
| `TwigCollector` | Template names, render times |
| `SecurityCollector` | User, roles, firewall, access decision log |
| `CacheCollector` | Cache pool operations, hits/misses |
| `MailerCollector` | Emails sent (from, to, subject, transport) |
| `MessengerCollector` | Messages dispatched/handled/failed, bus, transport |

## Configuration Reference

```yaml
app_dev_panel:
    enabled: true                          # Master switch
    storage:
        path: '%kernel.project_dir%/var/debug'
        history_size: 50
    collectors:
        request: true                      # SymfonyRequestCollector
        exception: true                    # ExceptionCollector
        log: true                          # LogCollector
        event: true                        # EventCollector
        service: true                      # ServiceCollector
        http_client: true                  # HttpClientCollector
        timeline: true                     # TimelineCollector
        var_dumper: true                   # VarDumperCollector
        filesystem_stream: true            # FilesystemStreamCollector
        http_stream: true                  # HttpStreamCollector
        command: true                      # CommandCollector
        doctrine: true                     # DoctrineCollector
        twig: true                         # TwigCollector
        security: true                     # SecurityCollector
        cache: true                        # CacheCollector
        mailer: true                       # MailerCollector
        messenger: true                    # MessengerCollector
    ignored_requests:                      # URL patterns to skip
        - '/_wdt/*'
        - '/_profiler/*'
    ignored_commands:                      # Command patterns to skip
        - 'completion'
        - 'help'
        - 'list'
    dumper:
        excluded_classes: []               # Classes to skip in object dumps
```

## Architecture Comparison: Yii vs Symfony

| Aspect | Yii 3 Adapter | Symfony Adapter |
|--------|--------------|-----------------|
| Registration | Config plugin auto-load | Bundle + Extension |
| DI wiring | `di.php` + `di-providers.php` | `AppDevPanelExtension` + `CompilerPass` |
| Proxy decoration | Service provider | `setDecoratedService()` in compiler pass |
| Event mapping | `events-web.php` / `events-console.php` | `EventSubscriberInterface` classes |
| Config | `params.php` (flat array) | `Configuration` tree builder |
| Request bridge | Yii `BeforeRequest`/`AfterRequest` events | `kernel.request`/`kernel.response` events |
| PSR-7 bridge | Native (Yii uses PSR-7) | `nyholm/psr7-server` conversion |
