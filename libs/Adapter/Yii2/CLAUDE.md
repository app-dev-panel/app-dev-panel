# Yii 2 Adapter

Bridges ADP Kernel and API into Yii 2. Third adapter after Yii 3 and Symfony.

## Package

- Composer: `app-dev-panel/adapter-yii2`
- Namespace: `AppDevPanel\Adapter\Yii2\`
- PHP: 8.4+
- Yii: 2.0.50+
- Dependencies: `app-dev-panel/kernel`, `app-dev-panel/api`, `app-dev-panel/cli`, `nyholm/psr7`, `nyholm/psr7-server`, `guzzlehttp/guzzle`, `guzzlehttp/psr7`
- Optional: Yii 2 DB component (database inspector)

## Directory Structure

```
src/
├── Bootstrap.php                              # Yii 2 BootstrapInterface, auto-registers Module
├── Module.php                                 # Core module: DI, collectors, event wiring, routes
├── EventListener/
│   ├── WebListener.php                        # beforeRequest/afterRequest → Debugger lifecycle
│   └── ConsoleListener.php                    # Console beforeRequest/afterRequest → Debugger lifecycle
├── Collector/
│   ├── DbCollector.php                        # SQL queries via yii\db events with timing
│   ├── DebugLogTarget.php                     # Real-time log target feeding LogCollector
│   ├── MailerCollector.php                    # Mail messages via BaseMailer events
│   └── AssetBundleCollector.php               # Asset bundles via View events
├── Inspector/
│   ├── Yii2ConfigProvider.php                 # Components, params, modules, events for inspector
│   ├── Yii2DbSchemaProvider.php               # Database schema via yii\db\Schema
│   ├── Yii2RouteCollection.php                # Wraps UrlManager rules for route inspector
│   ├── Yii2RouteAdapter.php                   # Wraps single UrlRule with __debugInfo()
│   └── NullSchemaProvider.php                 # Fallback when no database configured
└── Controller/
    └── AdpApiController.php                   # Yii 2 controller bridging to ADP ApiApplication
docs/
├── integration-flow.md                        # Boot sequence and lifecycle mapping
├── collectors.md                              # All collectors with data schemas
└── configuration.md                           # Configuration reference
```

## How It Works

### 1. Bootstrap Registration (`composer.json`)

Via `extra.bootstrap` in composer.json, Yii 2 auto-loads `Bootstrap` class.
It registers the `debug-panel` module if enabled (auto-enables in YII_DEBUG mode).

### 2. Configuration (application config)

```php
'modules' => [
    'debug-panel' => [
        'class' => \AppDevPanel\Adapter\Yii2\Module::class,
        'storagePath' => '@runtime/debug',
        'historySize' => 50,
        'collectors' => [
            'request' => true,
            'exception' => true,
            'log' => true,
            'event' => true,
            'db' => true,
            'mailer' => true,
            'assets' => true,
        ],
        'ignoredRequests' => ['/debug/api/*', '/inspect/api/*'],
        'ignoredCommands' => ['help', 'list'],
        'allowedIps' => ['127.0.0.1', '::1'],
    ],
],
```

### 3. Module Bootstrap (`Module::bootstrap()`)

Executes in order:
1. `registerServices($app)` — Core DI: `StorageInterface`, `DebuggerIdGenerator`, PSR-17 factories, API services, inspector providers, all inspector controllers
2. `registerCollectors()` — Creates all enabled collector instances
3. `buildDebugger()` — Builds `Debugger` with all collectors + storage
4. `registerEventListeners()` — Attaches web/console event listeners
5. `registerRoutes()` — Adds URL rules for `/debug/api/*` and `/inspect/api/*`

### 4. Event Wiring

**Web events (`WebListener`):**

| Yii 2 Event | ADP Action |
|---|---|
| `Application::EVENT_BEFORE_REQUEST` | Convert Yii Request → PSR-7, `Debugger::startup()`, `RequestCollector`, `WebAppInfoCollector` |
| `Application::EVENT_AFTER_REQUEST` | Convert Yii Response → PSR-7, `RequestCollector` captures response, adds `X-Debug-Id` header, `Debugger::shutdown()` |
| `afterAction` | `ExceptionCollector` captures error handler exceptions |

**Console events (`ConsoleListener`):**

| Yii 2 Event | ADP Action |
|---|---|
| `Application::EVENT_BEFORE_REQUEST` | Extract command name, `Debugger::startup()`, `CommandCollector`, `ConsoleAppInfoCollector` |
| `Application::EVENT_AFTER_REQUEST` | Capture exceptions, `CommandCollector`, `Debugger::shutdown()` |

### 5. PSR-7 Conversion

Yii 2 uses its own `yii\web\Request` / `yii\web\Response` objects.
`WebListener` and `AdpApiController` convert these to PSR-7 via `nyholm/psr7`.

### 6. DB Profiling

`Module::registerDbProfiling()` hooks into:
- `yii\db\Connection::EVENT_AFTER_OPEN` → `DbCollector::logConnection()`
- `yii\db\Command::EVENT_BEFORE_EXECUTE` → `DbCollector::beginQuery()` (starts timer)
- `yii\db\Command::EVENT_AFTER_EXECUTE` → `DbCollector::logQuery()` (stops timer, records query with timing, params, SQL type)

### 6a. Real-time Log Capture

`Module::registerDebugLogTarget()` registers `DebugLogTarget` as a Yii log target:
- Feeds `LogCollector` in real-time as messages are flushed (not at shutdown)
- Maps Yii log levels to PSR-3 levels
- `exportInterval = 1` ensures immediate capture

### 6b. Mailer Profiling

`Module::registerMailerProfiling()` hooks into:
- `yii\mail\BaseMailer::EVENT_AFTER_SEND` → `MailerCollector::logMessage()` (captures from, to, cc, bcc, subject, success)

### 6c. Asset Bundle Profiling

`Module::registerAssetProfiling()` hooks into (web only):
- `yii\web\View::EVENT_END_PAGE` → `AssetBundleCollector::collectBundles()` (reads View::$assetBundles)

### 7. Inspector Integration

All inspector controllers are explicitly registered in `Module::registerServices()`.
This follows the same pattern as Yiisoft and Symfony adapters (no auto-wiring for controllers).

**Registered controllers:**
`FileController`, `RoutingController`, `InspectController`, `DatabaseController`,
`GitController`, `ServiceController`, `CacheController`, `CommandController`,
`ComposerController`, `RequestController`, `TranslationController`, `OpcacheController`.

**`Yii2ConfigProvider`** registered as `config` service alias:

| Group | Source |
|---|---|
| `params` / `parameters` | `$app->params` |
| `di` / `services` | `$app->getComponents()` |
| `events` / `events-web` | `Event::$_events` (class-level via reflection) + `$app->_events` (instance-level) + `$app->getBehaviors()` |
| `modules` | `$app->getModules()` |

**`Yii2RouteCollection`** wraps `UrlManager->rules` for route inspector.
Each `UrlRule` is wrapped in `Yii2RouteAdapter` exposing `__debugInfo()` with: name, pattern, methods, defaults, route.

**`Yii2DbSchemaProvider`** uses `yii\db\Schema` for database inspection.

## Collectors

### Kernel Collectors (Reused via Event Listeners)

| Collector | Fed By | Data |
|---|---|---|
| `RequestCollector` | `WebListener` | PSR-7 request/response |
| `LogCollector` | PSR-3 proxy (if configured) | PSR-3 log entries |
| `EventCollector` | PSR-14 proxy (if configured) | Dispatched events |
| `ExceptionCollector` | `WebListener` / `ConsoleListener` | Uncaught exceptions |
| `HttpClientCollector` | PSR-18 proxy (if configured) | HTTP client requests |
| `ServiceCollector` | — | Service method calls |
| `VarDumperCollector` | — | `dump()` calls |
| `TimelineCollector` | — | Cross-collector timing |
| `FilesystemStreamCollector` | — | File I/O operations |
| `HttpStreamCollector` | — | Stream HTTP operations |
| `CommandCollector` | `ConsoleListener` | Console commands |
| `WebAppInfoCollector` | `WebListener` | Web app metadata |
| `ConsoleAppInfoCollector` | `ConsoleListener` | Console app metadata |

### Yii 2-Specific Collectors

| Collector | Fed By | Data |
|---|---|---|
| `DbCollector` | `Command::EVENT_BEFORE/AFTER_EXECUTE` | SQL queries, params, row count, execution time, SQL type, backtrace |
| `DebugLogTarget` | Yii log target (real-time) | Feeds `LogCollector` with Yii log messages as they are flushed |
| `MailerCollector` | `BaseMailer::EVENT_AFTER_SEND` | From, to, cc, bcc, subject, success status |
| `AssetBundleCollector` | `View::EVENT_END_PAGE` | Asset bundles: class, source/base paths, CSS/JS files, dependencies |

## Architecture Comparison: Symfony vs Yii 2

| Aspect | Symfony Adapter | Yii 2 Adapter |
|---|---|---|
| Registration | Bundle + Extension | BootstrapInterface + Module |
| DI wiring | `AppDevPanelExtension` + `CompilerPass` | `Module::registerServices()` via `Yii::$container` |
| Proxy decoration | `setDecoratedService()` in compiler pass | Direct singleton replacement in DI container |
| Event mapping | `EventSubscriberInterface` classes | `Event::on()` static bindings |
| Config format | `Configuration` tree builder (YAML) | Module public properties (PHP array) |
| Request handling | Symfony HttpFoundation → PSR-7 | Yii 2 Request/Response → PSR-7 |
| DB inspector | `DoctrineSchemaProvider` (DBAL) | `Yii2DbSchemaProvider` (yii\db\Schema) |
| Config inspector | `SymfonyConfigProvider` | `Yii2ConfigProvider` |
| API bridge | `AdpApiController` (Symfony controller) | `AdpApiController` (Yii 2 controller) |
| Route registration | PHP route configurator | `UrlManager::addRules()` |
