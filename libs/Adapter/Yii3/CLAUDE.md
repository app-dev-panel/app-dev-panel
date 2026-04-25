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
│   ├── DbSchemaProvider.php             # Database schema via Yiisoft DB
│   ├── Yii3AuthorizationConfigProvider.php  # Live auth config via RBAC/User/Auth (all optional)
│   └── Yii3ConfigProvider.php           # Wraps ConfigInterface; normalises events-web listeners for the inspector
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

The adapter provides three middleware classes that must be added to the application's middleware stack:

| Middleware | Purpose |
|-----------|---------|
| `DebugHeaders` (from `AppDevPanel\Api`) | Adds `X-Debug-Id` response header linking each response to its debug entry |
| `ToolbarMiddleware` | Injects the ADP debug toolbar into HTML responses (before `</body>`) |
| `YiiApiMiddleware` | Routes requests matching `/debug/api/*` to the ADP API application, bypassing normal app routing |

**Required middleware stack order** (in `config/web/di/application.php`):
```
DebugHeaders → ToolbarMiddleware → ErrorCatcher → YiiApiMiddleware → SessionMiddleware → CsrfTokenMiddleware → FormatDataResponse → RequestCatcherMiddleware → Router
```

`DebugHeaders` must be outermost (before `ErrorCatcher`) to attach the debug ID even on error responses. `ToolbarMiddleware` must be after `DebugHeaders` (needs the debug ID) and before `ErrorCatcher` so the toolbar appears even on error pages. `YiiApiMiddleware` must be before `Router` to intercept API requests early.

## Inspector

`GET /inspect/api/events` is served by `Yii3ConfigProvider`, registered as the `config` alias in `config/di-api.php`. It wraps `Yiisoft\Config\ConfigInterface` and normalises each listener returned by `$config->get('events')` / `$config->get('events-web')` into the shape expected by the frontend Events page: `{name, class, listeners}` where each listener is a `Class::method` string, a `[class, method]` tuple, or a `ClosureDescriptor` array (`{__closure: true, source, file, startLine, endLine}`) so that closures/arrow functions render as syntax-highlighted code blocks. Non-event groups (`params`, `di`, etc.) are delegated to the underlying `ConfigInterface`.

`GET /inspect/api/authorization` is served by `Yii3AuthorizationConfigProvider`, wired in `config/di-api.php`. It introspects the DI container for optional Yii packages — if a package is absent, its section is empty rather than producing an error.

| Section | Source |
|---------|--------|
| `guards` | `Yiisoft\Auth\AuthenticationMethodInterface` + concrete `HttpBasic`/`HttpBearer`/`HttpHeader`/`QueryParam`/`Composite` (yiisoft/auth). Deduplicated across the interface id and concrete class. |
| `roleHierarchy` | `Yiisoft\Rbac\ItemsStorageInterface::getAll()` + `getDirectChildren()` (yiisoft/rbac v2) or `getChildren()` (v1) — map `role name → list of child role names`. |
| `voters` | `Yiisoft\Access\AccessCheckerInterface` (yiisoft/access) entry plus every rule returned by `Yiisoft\Rbac\RulesStorageInterface::getAll()`. |
| `config` | `user`, `rbac`, `auth` subtrees of `app-dev-panel/yii3` params; plus live `CurrentUser` snapshot (`isGuest`, `id`) when `Yiisoft\User\CurrentUser` is in the container. |

All four packages (`yiisoft/rbac`, `yiisoft/user`, `yiisoft/auth`, `yiisoft/access`) are declared in `composer.json` `suggest` — the adapter works without them, the inspector just renders an empty section.

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
