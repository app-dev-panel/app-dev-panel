# Yii 2 Adapter

Bridges ADP Kernel and API into Yii 2. Third adapter after Yii 3 (Yii3) and Symfony.

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
│   ├── AuthorizationListener.php                   # User::EVENT_AFTER_LOGIN/LOGOUT → AuthorizationCollector
│   └── ConsoleListener.php                    # Console beforeRequest/afterRequest → Debugger lifecycle
├── Collector/
│   ├── DbProfilingTarget.php                  # Yii 2 log target feeding Kernel DatabaseCollector
│   └── DebugLogTarget.php                     # Real-time log target feeding LogCollector
├── Proxy/
│   ├── I18NProxy.php                          # Extends yii\i18n\I18N, feeds TranslatorCollector
│   ├── RouterMatchRecorder.php                # Records route matching data
│   └── UrlRuleProxy.php                       # Wraps UrlRule for route profiling
├── Inspector/
│   ├── Yii2ConfigProvider.php                 # Components, params, modules, events for inspector
│   ├── Yii2DbSchemaProvider.php               # Database schema via yii\db\Schema
│   ├── Yii2RouteCollection.php                # Wraps UrlManager rules for route inspector
│   ├── Yii2RouteAdapter.php                   # Wraps single UrlRule with __debugInfo()
│   └── NullSchemaProvider.php                 # Fallback when no database configured
└── Controller/
    ├── AdpApiController.php                   # Yii 2 controller bridging to ADP ApiApplication
    ├── DebugQueryController.php               # Direct SQL query execution for inspector
    └── DebugResetController.php               # Reset/clear debug storage
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
            'service' => true,
            'http_client' => true,
            'timeline' => true,
            'var_dumper' => true,
            'filesystem_stream' => true,
            'http_stream' => true,
            'command' => true,
            'db' => true,
            'mailer' => true,
            'assets' => true,
            'translator' => true,
            'security' => true,
            'redis' => true,
            'elasticsearch' => true,
            'template' => true,
            'code_coverage' => false,
        ],
        'ignoredRequests' => ['/debug/api/**', '/inspect/api/**'],
        'ignoredCommands' => ['help', 'list', 'cache/*', 'asset/*'],
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
| `User::EVENT_AFTER_LOGIN` / `EVENT_AFTER_LOGOUT` | `AuthorizationListener` → `AuthorizationCollector` |

**Console events (`ConsoleListener`):**

| Yii 2 Event | ADP Action |
|---|---|
| `Application::EVENT_BEFORE_REQUEST` | Extract command name, `Debugger::startup()`, `CommandCollector`, `ConsoleAppInfoCollector` |
| `Application::EVENT_AFTER_REQUEST` | Capture exceptions, `CommandCollector`, `Debugger::shutdown()` |

### 5. PSR-7 Conversion

Yii 2 uses its own `yii\web\Request` / `yii\web\Response` objects.
`WebListener` and `AdpApiController` convert these to PSR-7 via `nyholm/psr7`.

### 6. DB Profiling

`Module::registerDbProfiling()` registers a `DbProfilingTarget` Yii log target that intercepts
Yii 2's `beginProfile()`/`endProfile()` messages for DB commands. The target tracks query start
times internally and calls `DatabaseCollector::logQuery()` (from Kernel) on profile end.

### 6a. Real-time Log Capture

`Module::registerDebugLogTarget()` registers `DebugLogTarget` as a Yii log target:
- Feeds `LogCollector` in real-time as messages are flushed (not at shutdown)
- Maps Yii log levels to PSR-3 levels
- `exportInterval = 1` ensures immediate capture

### 6b. Mailer Profiling

`Module::registerMailerProfiling()` hooks into:
- `yii\mail\BaseMailer::EVENT_AFTER_SEND` → normalizes Yii 2 `MessageInterface` to array → `MailerCollector::collectMessage()` (Kernel)

### 6c. Asset Bundle Profiling

`Module::registerAssetProfiling()` hooks into (web only):
- `yii\web\View::EVENT_END_PAGE` → `AssetBundleCollector::collectBundles()` (reads View::$assetBundles)

### 6d. Template Profiling

`Module::registerTemplateProfiling()` hooks into:
- `yii\base\View::EVENT_BEFORE_RENDER` → records `microtime(true)` per `$viewFile` (using a stack for nested renders)
- `yii\base\View::EVENT_AFTER_RENDER` → calculates render duration, calls `TemplateCollector::collectRender($viewFile, $output, $params, $renderTime)`
- Captures timing, rendered output, and parameters in a single call
- Uses a per-file timer stack to handle nested rendering correctly (layout → partial → widget)

### 6e. Redis, Elasticsearch, Code Coverage

`RedisCollector`, `ElasticsearchCollector`, and `CodeCoverageCollector` are registered in `buildCollectorMap()` and require no event wiring — they are fed data directly by application code or by the Kernel lifecycle (`startup()`/`shutdown()` for coverage).

- `code_coverage` is opt-in (default: `false`), requires `pcov` or `xdebug` extension

### 7. Inspector Integration

All inspector controllers are explicitly registered in `Module::registerServices()`.
This follows the same pattern as Yii3 and Symfony adapters (no auto-wiring for controllers).

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
| `LogCollector` | `DebugLogTarget` (Yii log target) | Log entries captured in real-time |
| `EventCollector` | Yii 2 global event listener (`Event::on('*', '*', ...)`) | Dispatched events |
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

### Yii 2-Specific Collectors and Helpers

| Class | Fed By | Data |
|---|---|---|
| `DbProfilingTarget` | Yii Logger profiling messages | Feeds Kernel `DatabaseCollector::logQuery()` with SQL, timing |
| `DebugLogTarget` | Yii log target (real-time) | Feeds `LogCollector` with Yii log messages as they are flushed |

### Kernel Collectors Used Directly (with Yii 2 event wiring)

| Collector | Fed By | Data |
|---|---|---|
| `DatabaseCollector` | `DbProfilingTarget` (Yii Logger) | SQL queries, params, row count, execution time |
| `MailerCollector` | `BaseMailer::EVENT_AFTER_SEND` (normalized in Module) | From, to, cc, bcc, subject, body, charset |
| `AssetBundleCollector` | `View::EVENT_END_PAGE` (normalized in Module) | Asset bundles: class, source/base paths, CSS/JS files, dependencies |
| `TranslatorCollector` | `I18NProxy` replacing `Yii::$app->i18n` | Translation lookups, missing translations |
| `AuthorizationCollector` | `AuthorizationListener` on `User::EVENT_AFTER_LOGIN/LOGOUT` | Auth events: login, logout, user identity |
| `TemplateCollector` | `View::EVENT_BEFORE_RENDER` + `EVENT_AFTER_RENDER` | Render timing, output, parameters (nested-safe) |
| `RedisCollector` | Direct collector calls | Redis commands, timing, errors |
| `ElasticsearchCollector` | Direct collector calls | ES requests, timing, hits |
| `CodeCoverageCollector` | `pcov` / `xdebug` lifecycle | Per-file line coverage (opt-in) |

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
