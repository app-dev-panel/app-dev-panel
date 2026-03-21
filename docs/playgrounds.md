# Playgrounds

Reference applications demonstrating ADP integration with specific frameworks.

## What Is a Playground

A playground is a minimal, working application for a specific PHP framework with ADP fully integrated. Each playground:

- Installs the corresponding ADP adapter (`adapter-yiisoft`, `adapter-symfony`, `adapter-yii2`)
- Configures collectors, storage, and API routes
- Exposes demo endpoints that generate debug data (logs, exceptions, events)
- Exposes `/test/fixtures/*` endpoints for automated testing
- Serves the ADP debug API at `/debug/api/*` and inspector API at `/inspect/api/*`

Playgrounds have **no unit tests**. Quality is enforced via Mago only.

## Directory Layout

```
playground/
├── yiisoft-app/          # Yii 3 — port 8101
├── symfony-basic-app/    # Symfony 7 — port 8102
└── yii2-basic-app/       # Yii 2 — port 8103
```

## Port Allocation

| Playground | Port | Makefile Variable |
|------------|------|-------------------|
| Yiisoft | 8101 | `YIISOFT_PORT` |
| Symfony | 8102 | `SYMFONY_PORT` |
| Yii2 | 8103 | `YII2_PORT` |

Override: `make fixtures-symfony SYMFONY_PORT=9000`

## Common URLs

All playgrounds expose:

| Path | Purpose |
|------|---------|
| `/` | Home / demo page |
| `/debug/api/` | Debug entry list (JSON) |
| `/debug/api/view/{id}` | Full debug entry data |
| `/debug/api/summary/{id}` | Entry summary |
| `/inspect/api/*` | Inspector endpoints (config, schema, services) |
| `/test/fixtures/*` | Test fixture endpoints (see Testing section) |

## Starting Servers

```bash
# Install all playground deps first
make install-playgrounds

# Start each server (separate terminals)
cd playground/yiisoft-app && ./yii serve --port=8101
cd playground/symfony-basic-app && PHP_CLI_SERVER_WORKERS=3 php -S 127.0.0.1:8102 -t public
cd playground/yii2-basic-app && PHP_CLI_SERVER_WORKERS=3 php -S 127.0.0.1:8103 -t public
```

`PHP_CLI_SERVER_WORKERS=3` is required for SSE (one worker handles the SSE stream, others handle API requests).

## Running Test Fixtures

```bash
make fixtures              # All playgrounds in parallel
make fixtures-yiisoft      # Yiisoft only
make fixtures-symfony      # Symfony only
make fixtures-yii2         # Yii2 only
```

---

## Yiisoft Playground (Yii 3)

**Path:** `playground/yiisoft-app/`
**Adapter:** `app-dev-panel/adapter-yiisoft`
**Integration method:** Yiisoft config-plugin (auto-loaded via composer)

### Installation

The adapter registers automatically via `yiisoft/config` plugin system. No manual bundle/module registration needed.

**composer.json:**
```json
{
    "require": {
        "app-dev-panel/adapter-yiisoft": "*"
    }
}
```

### How It Works

1. `yiisoft/config` discovers `adapter-yiisoft`'s `extra.config-plugin-options`
2. Adapter provides DI definitions (`di.php`, `di-web.php`, `di-api.php`), event listeners (`events-web.php`), and bootstrap (`bootstrap.php`)
3. DI wires: `StorageInterface`, `Debugger`, all collectors, PSR proxy decorators
4. Events wire: `ApplicationStartup` → `Debugger::startup()`, `AfterEmit` → `Debugger::shutdown()`
5. Middleware: `YiiApiMiddleware` intercepts `/debug/api/*` and `/inspect/api/*` requests

### Key Config Files

| File | Purpose |
|------|---------|
| `config/common/routes.php` | App routes + test fixture routes |
| `config/common/di/logger.php` | PSR-3 logger (FileTarget + StreamTarget) |
| `config/common/di/router.php` | Route collection builder |
| `config/web/di/application.php` | Middleware stack |
| `config/web/di/psr17.php` | PSR-17 factories |

### Entry Points

- **HTTP:** `public/index.php` → `HttpApplicationRunner`
- **CLI:** `./yii` → `ConsoleApplicationRunner`

### Debug Storage

`runtime/debug/` (via `@runtime` alias)

### App Routes

| Method | Path | Handler |
|--------|------|---------|
| GET | `/` | `Web\HomePage\Action` |
| GET | `/test/fixtures/*` | `Web\TestFixtures\*Action` |

### Controller Structure

Yiisoft uses PSR-15 request handler classes (one class per action):

```
src/Web/
├── HomePage/Action.php
└── TestFixtures/
    ├── CacheAction.php
    ├── CacheHeavyAction.php
    ├── DatabaseAction.php
    ├── DumpAction.php
    ├── EventsAction.php
    ├── ExceptionAction.php
    ├── ExceptionChainedAction.php
    ├── FilesystemAction.php
    ├── HttpClientAction.php
    ├── LogsAction.php
    ├── LogsContextAction.php
    ├── LogsHeavyAction.php
    ├── MailerAction.php
    ├── MessengerAction.php
    ├── MultiAction.php
    ├── RequestInfoAction.php
    ├── ResetAction.php
    ├── ResetCliAction.php
    ├── RouterAction.php
    ├── TestFixtureEvent.php
    ├── TimelineAction.php
    └── ValidatorAction.php
```

---

## Symfony Playground (Symfony 7)

**Path:** `playground/symfony-basic-app/`
**Adapter:** `app-dev-panel/adapter-symfony`
**Integration method:** Symfony Bundle (manual registration)

### Installation

Register the bundle and create the config file:

**`config/bundles.php`:**
```php
return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    AppDevPanel\Adapter\Symfony\AppDevPanelBundle::class => ['dev' => true, 'test' => true],
];
```

**`config/packages/app_dev_panel.yaml`:**
```yaml
app_dev_panel:
    enabled: true
    storage:
        path: '%kernel.project_dir%/var/debug'
        history_size: 50
    collectors:
        request: true
        exception: true
        log: true
        event: true
        service: true
        http_client: true
        timeline: true
        var_dumper: true
        filesystem_stream: true
        http_stream: true
        command: true
        cache: true
    ignored_requests:
        - '/debug/api/*'
```

**`config/routes/app_dev_panel.php`:**
```php
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->import('@AppDevPanelBundle/config/routes/adp.php');
};
```

### How It Works

1. `AppDevPanelBundle::build()` registers `CollectorProxyCompilerPass`
2. `AppDevPanelExtension` loads config, registers storage, collectors, event subscribers, API services
3. `CollectorProxyCompilerPass` decorates PSR services: Logger → `LoggerInterfaceProxy`, EventDispatcher → `SymfonyEventDispatcherProxy`, HttpClient → `HttpClientInterfaceProxy`
4. `HttpSubscriber` maps `kernel.request/response/exception/terminate` → Debugger lifecycle
5. Routes imported from bundle serve `/debug/api/*` and `/inspect/api/*`

### Key Config Files

| File | Purpose |
|------|---------|
| `config/bundles.php` | Bundle registration |
| `config/packages/app_dev_panel.yaml` | ADP configuration |
| `config/routes/app_dev_panel.php` | ADP route import |
| `config/routes.yaml` | Controller auto-discovery |
| `config/services.yaml` | App service definitions |

### Entry Points

- **HTTP:** `public/index.php` → Symfony Runtime + Kernel
- **CLI:** `bin/console` → Symfony Console Application

### Debug Storage

`var/debug/` (via `%kernel.project_dir%`)

### App Routes

| Method | Path | Handler |
|--------|------|---------|
| GET | `/` | `HomeController::index()` |
| GET | `/api/users` | `HomeController::users()` |
| GET | `/api/error` | `HomeController::error()` |
| GET | `/test/fixtures/*` | `TestFixturesController::*()` |

### Controller Structure

Standard Symfony controllers with route attributes:

```
src/Controller/
├── HomeController.php
└── TestFixtures/
    ├── CacheAction.php
    ├── CacheHeavyAction.php
    ├── DatabaseAction.php
    ├── DumpAction.php
    ├── EventsAction.php
    ├── ExceptionAction.php
    ├── ExceptionChainedAction.php
    ├── FilesystemAction.php
    ├── HttpClientAction.php
    ├── LogsAction.php
    ├── LogsContextAction.php
    ├── LogsHeavyAction.php
    ├── MailerAction.php
    ├── MessengerAction.php
    ├── MultiAction.php
    ├── RequestInfoAction.php
    ├── ResetAction.php
    ├── ResetCliAction.php
    ├── RouterAction.php
    ├── TestFixtureEvent.php
    ├── TimelineAction.php
    └── ValidatorAction.php
```

---

## Yii2 Playground (Yii 2)

**Path:** `playground/yii2-basic-app/`
**Adapter:** `app-dev-panel/adapter-yii2`
**Integration method:** Yii2 Module + BootstrapInterface (auto-bootstrap via composer)

### Installation

The adapter auto-bootstraps via `extra.bootstrap` in composer. Configure the module in app config:

**`config/web.php`:**
```php
return [
    'bootstrap' => ['debug-panel', 'log'],
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
            ],
            'ignoredRequests' => ['/debug/api/*', '/inspect/api/*', '/assets/*'],
        ],
    ],
];
```

### How It Works

1. `Bootstrap` class auto-detected via `extra.bootstrap` in adapter's composer.json
2. `Module::bootstrap($app)` is called during application bootstrap
3. Module registers: DI services, collectors, `Debugger`, event listeners, URL rules
4. `WebListener` maps `EVENT_BEFORE_REQUEST` / `EVENT_AFTER_REQUEST` → Debugger lifecycle
5. Module adds URL rules for `/debug/api/*` and `/inspect/api/*` via `UrlManager::addRules()`
6. Extra hooks: DB profiling (`yii\db\Connection` events), Mailer (`EVENT_AFTER_SEND`), Assets (`View::EVENT_END_PAGE`)

### Key Config Files

| File | Purpose |
|------|---------|
| `config/web.php` | App config + ADP module + URL rules |
| `config/console.php` | Console app config |
| `config/params.php` | App parameters |

### Entry Points

- **HTTP:** `public/index.php` → `yii\web\Application`
- **CLI:** `./yii` → `yii\console\Application`

### Debug Storage

`runtime/debug/` (via `@runtime` alias)

### App Routes

| Method | Path | Handler |
|--------|------|---------|
| GET | `/` | `SiteController::actionIndex()` |
| GET | `/api/users` | `SiteController::actionUsers()` |
| GET | `/api/error` | `SiteController::actionErrorDemo()` |
| GET | `/test/fixtures/*` | `TestFixturesController::action*()` |

### Controller Structure

Yii2 uses standalone action classes (one class per action):

```
src/
├── controllers/
│   └── SiteController.php
└── actions/testFixtures/
    ├── CacheAction.php
    ├── CacheHeavyAction.php
    ├── DatabaseAction.php
    ├── DumpAction.php
    ├── EventsAction.php
    ├── ExceptionAction.php
    ├── ExceptionChainedAction.php
    ├── FilesystemAction.php
    ├── HttpClientAction.php
    ├── LogsAction.php
    ├── LogsContextAction.php
    ├── LogsHeavyAction.php
    ├── MailerAction.php
    ├── MultiAction.php
    ├── RequestInfoAction.php
    ├── ResetAction.php
    ├── ResetCliAction.php
    └── TimelineAction.php
```

Yii2 playground has 18 fixture actions. Missing compared to Yiisoft/Symfony: `MessengerAction`, `RouterAction`, `ValidatorAction` (these collectors are not supported in Yii 2).

---

## Adding a New Playground

To add a playground for a new framework:

### 1. Create the App

Create `playground/<framework>-app/` with a minimal application for the target framework.
Use the framework's official skeleton/starter (e.g., `composer create-project`).

### 2. Install the ADP Adapter

```bash
cd playground/<framework>-app
composer require app-dev-panel/kernel app-dev-panel/api app-dev-panel/adapter-<framework>
```

Use path repositories in `composer.json` to reference the local monorepo packages:

```json
{
    "repositories": [
        {"type": "path", "url": "../../libs/Kernel"},
        {"type": "path", "url": "../../libs/API"},
        {"type": "path", "url": "../../libs/Cli"},
        {"type": "path", "url": "../../libs/Testing"},
        {"type": "path", "url": "../../libs/Adapter/<Framework>"}
    ]
}
```

### 3. Configure ADP

- Wire collectors, storage, and API routes per the adapter's docs
- Set storage path to a framework-appropriate runtime directory
- Configure `ignored_requests` to exclude `/debug/api/*` from debug collection

### 4. Add Demo Endpoints

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/` | Home / demo page |
| GET | `/api/users` | Example JSON API endpoint |
| GET | `/api/error` | Example error-throwing endpoint |

### 5. Implement Fixture Endpoints

All playgrounds **must** implement `/test/fixtures/*` endpoints matching `FixtureRegistry` (see `libs/Testing/CLAUDE.md`).

**Required fixture endpoints (core):**

| Endpoint | Fixture | Collectors Tested |
|----------|---------|-------------------|
| `/test/fixtures/reset` | (setup) | Clears debug storage |
| `/test/fixtures/reset-cli` | (setup) | Clears storage via CLI command |
| `/test/fixtures/logs` | logs:basic | LogCollector |
| `/test/fixtures/logs-context` | logs:context | LogCollector |
| `/test/fixtures/logs-heavy` | logs:heavy | LogCollector |
| `/test/fixtures/events` | events:basic | EventCollector |
| `/test/fixtures/dump` | var-dumper:basic | VarDumperCollector |
| `/test/fixtures/timeline` | timeline:basic | TimelineCollector |
| `/test/fixtures/request-info` | request:basic | RequestCollector, WebAppInfoCollector |
| `/test/fixtures/exception` | exception:runtime | ExceptionCollector |
| `/test/fixtures/exception-chained` | exception:chained | ExceptionCollector |
| `/test/fixtures/multi` | multi:logs-and-events | LogCollector, EventCollector, TimelineCollector |
| `/test/fixtures/http-client` | http-client:basic | HttpClientCollector |
| `/test/fixtures/filesystem` | filesystem:basic | FilesystemStreamCollector |
| `/test/fixtures/cache` | cache:basic | CacheCollector |
| `/test/fixtures/cache-heavy` | cache:heavy | CacheCollector |
| `/test/fixtures/database` | database:basic | DatabaseCollector |

**Optional fixture endpoints (framework-dependent):**

| Endpoint | Fixture | Notes |
|----------|---------|-------|
| `/test/fixtures/mailer` | mailer:basic | Requires mailer integration |
| `/test/fixtures/messenger` | messenger:basic | Requires queue/messenger integration |
| `/test/fixtures/validator` | validator:basic | Requires validator integration |
| `/test/fixtures/router` | router:basic | Requires router introspection |

All fixture responses must return JSON. The reset endpoints accept both GET and POST.

### 6. Add Composer Serve Script

Add a `serve` script to `composer.json`:

```json
{
    "scripts": {
        "serve": "PHP_CLI_SERVER_WORKERS=3 php -S 127.0.0.1:<PORT> -t public"
    }
}
```

### 7. Add Makefile Targets

In the root `Makefile`, add:

- `FRAMEWORK_PORT ?= 810X` — port allocation variable
- `serve-<framework>` — start the server
- `mago-playground-<framework>` — Mago checks
- `mago-playground-<framework>-fix` — Mago fix
- `fixtures-<framework>` — CLI fixtures
- `test-fixtures-<framework>` — PHPUnit E2E fixtures
- `test-scenario-<framework>` — Scenario test
- `test-playground-<framework>` — Full integration (start server, run E2E, stop server)

Update the aggregate targets (`mago-playgrounds`, `fixtures`, `test-fixtures`, `serve`) to include the new playground.

### 8. Add Mago Configuration

Create `mago.toml` in the playground root (or add the playground to a shared config).
Create baseline files (`mago-lint-baseline.php`, `mago-analyze-baseline.php`) if the framework's skeleton has pre-existing issues.

### 9. Update Documentation

- Update this file (`docs/playgrounds.md`) with the new playground section
- Update `docs/getting-started.md` project structure table
- Update root `CLAUDE.md` repository structure
- Add port allocation to the port table above

### Current Port Allocation

| Port | Playground |
|------|------------|
| 8100 | Frontend dev server |
| 8101 | Yiisoft |
| 8102 | Symfony |
| 8103 | Yii2 |
| 8104+ | Available for new playgrounds |
