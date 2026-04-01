# Yii 3 Adapter

Bridges ADP Kernel and API into Yii 3. Reference adapter. Includes Yii-specific collectors (DB, mailer, queue, router, validator, view) and database inspector via `Yiisoft\Db`.

## Package

- Composer: `app-dev-panel/adapter-yii3`
- Namespace: `AppDevPanel\Adapter\Yii3\`
- PHP: 8.4+
- Dependencies: `app-dev-panel/kernel`, `app-dev-panel/api`, `app-dev-panel/cli`, Yiisoft packages

## Directory Structure

```
config/
├── bootstrap.php         # VarDumper handler setup (runs at bootstrap)
├── di.php                # Common DI definitions (storage, proxies, collectors)
├── di-web.php            # Web-specific DI (RequestCollector, WebAppInfoCollector)
├── di-console.php        # Console-specific DI (CommandCollector, ConsoleAppInfoCollector)
├── di-api.php            # API bridge DI (controllers, middleware, inspector)
├── di-providers.php      # Service provider registration
├── events-web.php        # Web event → debugger lifecycle mapping
├── events-console.php    # Console event → debugger lifecycle mapping
└── params.php            # Master configuration for all settings
src/
├── Api/
│   ├── AliasPathResolver.php            # PathResolverInterface via Yii Aliases
│   └── YiiApiMiddleware.php             # PSR-15 middleware bridging to ApiApplication
├── Collector/
│   ├── Db/                              # Database query interception (uses core DatabaseCollector)
│   │   ├── CommandInterfaceProxy.php
│   │   ├── ConnectionInterfaceProxy.php
│   │   └── TransactionInterfaceDecorator.php
│   ├── Mailer/                          # Mail interception (uses core MailerCollector)
│   │   └── MailerInterfaceProxy.php
│   ├── Middleware/
│   │   └── MiddlewareCollector.php
│   ├── Queue/
│   │   ├── QueueCollector.php
│   │   ├── QueueDecorator.php
│   │   ├── QueueProviderInterfaceProxy.php
│   │   └── QueueWorkerInterfaceProxy.php
│   ├── Router/
│   │   ├── RouterCollector.php
│   │   └── UrlMatcherInterfaceProxy.php
│   ├── Translator/
│   │   └── TranslatorInterfaceProxy.php
│   ├── Validator/
│   │   ├── ValidatorCollector.php
│   │   └── ValidatorInterfaceProxy.php
│   └── View/
│       └── ViewEventListener.php
├── Inspector/
│   └── DbSchemaProvider.php             # Database schema via Yiisoft DB
├── Proxy/
│   ├── ContainerInterfaceProxy.php      # PSR-11 container proxy
│   ├── ContainerProxyConfig.php
│   ├── ProxyLogTrait.php
│   ├── ServiceProxy.php
│   ├── ServiceMethodProxy.php
│   └── VarDumperHandlerInterfaceProxy.php
└── DebugServiceProvider.php             # Wraps container with ContainerInterfaceProxy
```

## How It Works

### 1. Config Plugin Auto-Registration

Yii 3 uses config plugins. This adapter registers itself via `composer.json` extra config,
so installing the package automatically wires everything up.

### 2. Bootstrap Phase (`bootstrap.php`)

Replaces the VarDumper handler with `VarDumperHandlerInterfaceProxy` so that
`dump()` calls are captured by `VarDumperCollector`.

### 3. DI Registration (`di.php`, `di-web.php`, `di-console.php`)

Registers all proxy services as decorators in the DI container:

- `LoggerInterface` → `LoggerInterfaceProxy`
- `EventDispatcherInterface` → `EventDispatcherInterfaceProxy`
- `ClientInterface` (HTTP) → `HttpClientInterfaceProxy`
- Any tracked services → `ServiceProxy`

Registers storage, collectors, and their configurations.

### 4. Service Provider (`DebugServiceProvider.php`)

Wraps the `ContainerInterface` itself with `ContainerInterfaceProxy` to track
which services are resolved from the DI container.

### 5. Event Wiring (`events-web.php`, `events-console.php`)

Maps framework lifecycle events to debugger lifecycle:

**Web events:**
| Framework Event | Debugger Action |
|----------------|-----------------|
| `ApplicationStartup` | `Debugger::startup()`, `WebAppInfoCollector::markApplicationStarted()` |
| `BeforeRequest` | `Debugger::startup()` (with request context), `WebAppInfoCollector::markRequestStarted()`, `RequestCollector::collectRequest()` |
| `AfterRequest` | `WebAppInfoCollector::markRequestFinished()`, `RequestCollector::collectResponse()` |
| `ApplicationShutdown` | `WebAppInfoCollector::markApplicationFinished()` |
| `AfterEmit` | `Profiler::flush()`, `WebAppInfoCollector::markApplicationFinished()`, `Debugger::shutdown()` |
| `ApplicationError` | `ExceptionCollector` captures error |

**Console events:**
| Framework Event | Debugger Action |
|----------------|-----------------|
| `ApplicationStartup` | `Debugger::startup()`, `ConsoleAppInfoCollector::markApplicationStarted()` |
| `ApplicationShutdown` | `ConsoleAppInfoCollector::markApplicationFinished()`, `Debugger::shutdown()` |
| `ConsoleCommandEvent` | `ConsoleAppInfoCollector::collect()`, `CommandCollector::collect()` |
| `ConsoleErrorEvent` | `ConsoleAppInfoCollector::collect()`, `CommandCollector::collect()` |
| `ConsoleTerminateEvent` | `ConsoleAppInfoCollector::collect()`, `CommandCollector::collect()` |

## Middleware

The adapter provides two middleware classes that must be added to the application's middleware stack:

| Middleware | Purpose |
|-----------|---------|
| `DebugHeaders` (from `AppDevPanel\Api`) | Adds `X-Debug-Id` response header linking each response to its debug entry |
| `YiiApiMiddleware` | Routes requests matching `/debug/api/*` to the ADP API application, bypassing normal app routing |

**Required middleware stack order** (in `config/web/di/application.php`):
```
DebugHeaders → ErrorCatcher → YiiApiMiddleware → SessionMiddleware → CsrfTokenMiddleware → FormatDataResponse → RequestCatcherMiddleware → Router
```

`DebugHeaders` must be outermost (before `ErrorCatcher`) to attach the debug ID even on error responses. `YiiApiMiddleware` must be before `Router` to intercept API requests early.

## Configuration (`params.php`)

```php
'app-dev-panel/yii3' => [
    'enabled' => true,                    // Enable/disable debugger
    'collectors' => [...],                // Active collectors
    'trackedServices' => [...],           // Services to proxy with ServiceProxy
    'ignoredRequests' => [],              // URL patterns to skip
    'ignoredCommands' => [],              // Command patterns to skip
    'dumper' => [
        'excludedClasses' => [],          // Classes to skip in dumps
    ],
    'logLevel' => [
        'AppDevPanel\\' => 0,             // Log level per namespace
    ],
    'storage' => [
        'path' => '@runtime/debug',       // Storage directory
        'historySize' => 50,              // Max entries
        'exclude' => [],                  // Collectors to exclude from storage
    ],
],
```

## Creating a New Adapter

To integrate ADP with another framework (e.g., Laravel), follow this adapter's pattern:

1. **DI integration**: Register Kernel proxies as service decorators
2. **Event mapping**: Map framework events to `Debugger::startup()`/`shutdown()`
3. **Config**: Provide sensible defaults, let users customize
4. **Bootstrap**: Wire VarDumper handler early in the boot process
5. **Context separation**: Different collectors for web vs. CLI
