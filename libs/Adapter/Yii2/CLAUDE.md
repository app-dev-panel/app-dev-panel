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
│   ├── DbCollector.php                        # SQL queries via yii\db events
│   └── Yii2LogCollector.php                   # Yii 2 Logger messages
├── Inspector/
│   ├── Yii2ConfigProvider.php                 # Components, params, modules for inspector
│   ├── Yii2DbSchemaProvider.php               # Database schema via yii\db\Schema
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
            'yii_log' => true,
        ],
        'ignoredRequests' => ['/debug/api/*', '/inspect/api/*'],
        'ignoredCommands' => ['help', 'list'],
        'allowedIps' => ['127.0.0.1', '::1'],
    ],
],
```

### 3. Module Bootstrap (`Module::bootstrap()`)

Executes in order:
1. `registerServices()` — Core DI: `StorageInterface`, `DebuggerIdGenerator`, PSR-17 factories, API services, inspector providers
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
- `yii\db\Command::EVENT_AFTER_EXECUTE` → `DbCollector::logQuery()`

### 7. Inspector Integration

`Yii2ConfigProvider` registered as `config` service alias:

| Group | Source |
|---|---|
| `params` / `parameters` | `$app->params` |
| `di` / `services` | `$app->getComponents()` |
| `events` | `$app->getBehaviors()` |
| `modules` | `$app->getModules()` |

`Yii2DbSchemaProvider` uses `yii\db\Schema` for database inspection.

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
| `DbCollector` | `yii\db\Command::EVENT_AFTER_EXECUTE` | SQL queries, params, row count |
| `Yii2LogCollector` | `Yii::getLogger()` messages at shutdown | Log messages with levels and categories |

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
