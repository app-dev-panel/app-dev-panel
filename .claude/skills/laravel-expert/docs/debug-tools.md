# Laravel Debug Tools — Telescope & DebugBar Internals

## Laravel Telescope

### Architecture

```
Telescope\TelescopeServiceProvider
  ├── registers watchers (data collectors)
  ├── registers storage driver (DatabaseEntriesRepository)
  ├── registers routes (telescope/*)
  └── registers assets (SPA frontend)

Data flow:
  Watcher → Telescope::record(IncomingEntry) → EntryRepository::store() → DB table
```

### Watcher System

Each watcher is a self-contained data collector that listens to Laravel events:

```php
abstract class Watcher
{
    public function register($app): void;  // Subscribe to events
    public array $options = [];            // From config/telescope.php
}
```

**Built-in watchers:**

| Watcher | Events/Hooks | Data |
|---------|-------------|------|
| `RequestWatcher` | `RequestHandled` event | URI, method, headers, payload, response, duration |
| `QueryWatcher` | `QueryExecuted` event | SQL, bindings, time, connection, slow flag |
| `ModelWatcher` | `ModelEvent` (created/updated/deleted) | Model class, key, changes |
| `EventWatcher` | `EventDispatcher` proxy | Event name, payload, listeners |
| `ExceptionWatcher` | `ExceptionHandler` | Exception class, message, trace, context |
| `LogWatcher` | `MessageLogged` event | Level, message, context |
| `MailWatcher` | `MessageSent` event | To, subject, body, attachments |
| `NotificationWatcher` | `NotificationSent` event | Channel, notifiable, notification |
| `CacheWatcher` | Cache events | Key, value, hit/miss, TTL |
| `JobWatcher` | Queue events | Job class, connection, queue, status, duration |
| `ScheduleWatcher` | `ScheduledTaskFinished` | Command, expression, output |
| `CommandWatcher` | `CommandFinished` | Command name, arguments, exit code, output |
| `RedisWatcher` | `CommandExecuted` (Redis) | Command, duration |
| `GateWatcher` | `GateEvaluated` | Ability, result, user, arguments |
| `ViewWatcher` | `ComposingView` event | View name, path, data |
| `DumpWatcher` | `VarDumper::setHandler()` | Dump content with source file/line |
| `ClientRequestWatcher` | HTTP client events | URL, method, headers, response |
| `BatchWatcher` | Bus batch events | Batch ID, name, jobs count, progress |

### Telescope::record() Pipeline

```php
Telescope::record(IncomingEntry $entry)
  → check Telescope::$shouldRecord (master switch)
  → apply Telescope::$tagUsing callbacks (auto-tagging)
  → apply Telescope::$filterUsing callbacks (sampling, filtering)
  → apply Telescope::$afterStoringHooks
  → buffer entries in Telescope::$entriesQueue
  → on terminate: Telescope::store($entriesRepository)
    → EntryRepository::store(Collection $entries)
    → store tags in telescope_entries_tags table
```

### Storage

Default: `DatabaseEntriesRepository` with tables:
- `telescope_entries` — main data (uuid, batch_id, type, content JSON, created_at)
- `telescope_entries_tags` — many-to-many tags
- `telescope_monitoring` — monitored tags for filtering

### Limitations

| Limitation | Details |
|-----------|---------|
| DB-only storage | No file storage, no Redis storage out of the box |
| No real-time streaming | Polling only, no SSE/WebSocket |
| SPA is Inertia/Vue | Tightly coupled to Vue + Tailwind |
| Single app only | No cross-app debugging |
| No PSR interfaces | Everything is Laravel-specific |
| Heavy DB writes | Every request writes N rows (1 per watcher) |
| No structured query data | SQL stored as string in JSON blob |
| Authorization coupled | `TelescopeServiceProvider::gate()` + `viewTelescope` gate |
| Pruning required | `telescope:prune` cron job or DB grows unbounded |
| No timeline/waterfall | No cross-watcher timing correlation |

### How ADP Differs from Telescope

| Aspect | Telescope | ADP Laravel Adapter |
|--------|-----------|---------------------|
| Storage | Database (Eloquent) | JSON files (Kernel FileStorage) |
| Transport | HTTP polling | SSE real-time + REST |
| Frontend | Vue SPA (bundled) | React SPA (separate) |
| Architecture | Monolithic watchers | Kernel collectors + adapter events |
| Cross-framework | Laravel only | Any PHP framework |
| Data format | JSON in DB column | Structured JSON files |
| PSR compliance | None | PSR-3/7/14/18 proxies |
| Extensibility | Custom Watcher class | CollectorInterface |

## Laravel DebugBar (barryvdh/laravel-debugbar)

### Architecture

```
DebugbarServiceProvider
  ├── wraps DebugBar\DebugBar (maximebf/debugbar)
  ├── registers DataCollectors
  ├── registers middleware (InjectDebugbar)
  └── renders HTML/JS toolbar in response

Data flow:
  DataCollector::collect() → DebugBar → JsonHttpDriver → inject into HTML response
```

### DataCollector System

```php
interface DataCollectorInterface
{
    public function collect(): array;    // Return collected data
    public function getName(): string;   // Unique collector name
}

interface Renderable
{
    public function getWidgets(): array;  // Frontend widget definitions
}
```

**Built-in collectors:**

| Collector | Source | Data |
|-----------|-------|------|
| `PhpInfoCollector` | `phpinfo()` | PHP version |
| `MessagesCollector` | `Debugbar::info()` etc. | Log messages |
| `TimeDataCollector` | `Debugbar::startMeasure()` | Timing measurements |
| `MemoryCollector` | `memory_get_peak_usage()` | Memory usage |
| `ExceptionsCollector` | Exception handler | Caught exceptions |
| `QueryCollector` | DB `QueryExecuted` event | SQL queries, bindings, time, backtrace |
| `RouteCollector` | `Router::current()` | Current route, controller, middleware |
| `ViewCollector` | `View::composer('*')` | Rendered views, data passed |
| `EventCollector` | `Event::listen('*')` | Dispatched events |
| `LaravelCollector` | `App::version()` | Laravel/PHP version |
| `SymfonyRequestCollector` | Symfony Request/Response | Headers, cookies, session, server vars |
| `LogsCollector` | Log files | Last N lines from `storage/logs` |
| `FilesCollector` | `get_included_files()` | All included PHP files |
| `ConfigCollector` | `Config::all()` | Application config |
| `CacheCollector` | Cache events | Cache operations |
| `ModelCollector` | Eloquent model events | Model hydrations, duplicates |
| `GateCollector` | Gate checks | Authorization checks |
| `MultiAuthCollector` | Auth guards | Current user per guard |
| `SessionCollector` | Session data | Session contents |

### Injection Mechanism

```
InjectDebugbar middleware (runs last)
  → $debugbar->collect()  // Calls all collectors
  → $debugbar->modifyResponse($request, $response)
    → if HTML response:
      → inject <script>/<link> tags before </head>
      → inject toolbar HTML before </body>
    → if AJAX:
      → add X-DebugBar headers with data URL
      → store data in OpenHandler for later fetch
```

### Limitations

| Limitation | Details |
|-----------|---------|
| HTML injection only | Modifies response body — breaks non-HTML, streaming, binary |
| No standalone UI | Toolbar embedded in page, no separate SPA |
| No persistence | Data per-request only (OpenHandler for AJAX) |
| No console support | Web requests only |
| No SSE/streaming | No real-time updates |
| jQuery dependency | Frontend relies on jQuery |
| Performance overhead | All collectors run on every request |
| No structured storage | Data serialized to response, not stored |
| CORS issues | AJAX data fetch fails cross-origin |

### How ADP Differs from DebugBar

| Aspect | DebugBar | ADP Laravel Adapter |
|--------|----------|---------------------|
| UI delivery | Injected HTML toolbar | Separate React SPA |
| Data transport | Response injection / AJAX headers | REST API + SSE |
| Persistence | None (per-request) | JSON file storage |
| Console support | No | Yes (ConsoleListener) |
| Architecture | maximebf/debugbar DataCollectors | Kernel collectors + PSR proxies |
| Dependencies | jQuery, PHP Debug Bar lib | None (standalone Kernel) |
| Cross-framework | Laravel wrapper only | Any PHP framework |

## Database Layer

### Query Events

```php
// Laravel fires QueryExecuted for every query
DB::listen(function (QueryExecuted $query) {
    $query->sql;          // "select * from users where id = ?"
    $query->bindings;     // [1]
    $query->time;         // 3.45 (ms)
    $query->connection;   // Illuminate\Database\Connection instance
    $query->connectionName; // 'mysql'
});
```

**Internals:** `Connection::run()` wraps every query in timing + event dispatch:

```
Connection::select($query, $bindings)
  → run($query, $bindings, function () { PDO::execute() })
    → start timer
    → PDO::prepare() → execute()
    → stop timer
    → event(new QueryExecuted($sql, $bindings, $time, $this))
    → log to $this->queryLog if enabled
```

### Schema Inspection

```php
$schema = Schema::connection('mysql');

$schema->getTables();        // [{name, schema, size, comment, engine, collation}]
$schema->getColumns('users'); // [{name, type, nullable, default, ...}]
$schema->getIndexes('users'); // [{name, columns, type, unique, primary}]
$schema->getForeignKeys('users'); // [{name, columns, foreign_schema, foreign_table, ...}]
```

**ADP uses:** `Illuminate\Database\Connection` for `LaravelSchemaProvider` — wraps this in `SchemaProviderInterface`.

## Config System

```php
// Config is a Repository wrapping nested array
$config = config('app.debug');              // dot-notation access
$config = Config::get('database.default');  // Facade

// Internals
Illuminate\Config\Repository
  $items = [                  // Loaded from config/*.php files
      'app' => ['name' => 'ADP', 'debug' => true, ...],
      'database' => ['default' => 'mysql', ...],
      ...
  ];
```

**Config loading:**
1. `LoadConfiguration` bootstrapper reads all `config/*.php` files
2. Cached in `bootstrap/cache/config.php` (production)
3. Package configs published via `ServiceProvider::mergeConfigFrom()`

**ADP config:** `config/app-dev-panel.php` — published via `AppDevPanelServiceProvider`:
```php
$this->publishes([
    __DIR__ . '/../config/app-dev-panel.php' => config_path('app-dev-panel.php'),
], 'config');
```

## Facade System

```php
// Facade is a static proxy to a container binding
class Cache extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'cache';  // Resolves $app['cache']
    }
}

Cache::get('key');
// Actually: $app->make('cache')->get('key')
```

**Internals:** `Facade::__callStatic()` → `static::getFacadeRoot()` → `$app->make($accessor)` → forward method call.

**Real-time facades:** `use Facades\App\Services\Foo;` — auto-generates facade for any class.

**Gotcha for ADP:** If you decorate a service via `extend()`, facades automatically use the decorated version (since they resolve from container).
