# Laravel Adapter

Bridges ADP Kernel and API into Laravel. Uses Laravel's event system, middleware, and service container.

## Package

- Composer: `app-dev-panel/adapter-laravel`
- Namespace: `AppDevPanel\Adapter\Laravel\`
- PHP: 8.4+
- Laravel: 11.x / 12.x
- Dependencies: `app-dev-panel/kernel`, `app-dev-panel/api`, `app-dev-panel/cli`, `nyholm/psr7`, `guzzlehttp/guzzle`, `guzzlehttp/psr7`, `symfony/var-dumper`, PSR packages (`container`, `event-dispatcher`, `http-client`, `http-message`, `log`), Illuminate components (`contracts`, `http`, `routing`, `support`)

## Directory Structure

```
src/
├── AppDevPanelServiceProvider.php              # Service provider: registers all services, collectors, API
├── Middleware/
│   └── DebugMiddleware.php                     # HTTP lifecycle: startup/shutdown, request/response capture
├── EventListener/
│   ├── DatabaseListener.php                    # QueryExecuted → DatabaseCollector
│   ├── CacheListener.php                       # CacheHit/CacheMissed/KeyWritten/KeyForgotten → CacheCollector
│   ├── MailListener.php                        # MessageSent → MailerCollector
│   ├── QueueListener.php                       # JobProcessing/JobProcessed/JobFailed → QueueCollector
│   ├── HttpClientListener.php                  # RequestSending/ResponseReceived/ConnectionFailed → HttpClientCollector
│   └── ConsoleListener.php                     # CommandStarting/CommandFinished → Debugger lifecycle
├── Proxy/
│   └── LaravelEventDispatcherProxy.php         # Wraps Illuminate\Contracts\Events\Dispatcher
├── Collector/
│   └── RouterDataExtractor.php                 # Extracts route data from Laravel router
├── Inspector/
│   ├── LaravelConfigProvider.php               # Config, services, event listeners, providers
│   ├── LaravelSchemaProvider.php               # Database schema via Illuminate\Database\Connection
│   ├── NullSchemaProvider.php                  # Fallback when no database configured
│   ├── LaravelRouteCollectionAdapter.php       # Route inspection adapter
│   ├── LaravelRouteAdapter.php                 # Single route adapter
│   ├── LaravelUrlMatcherAdapter.php            # URL matching adapter
│   └── LaravelMatchResult.php                  # Match result DTO
└── Controller/
    └── AdpApiController.php                    # Laravel controller bridging to ADP ApiApplication
config/
└── app-dev-panel.php                           # Default configuration
routes/
└── adp.php                                     # API routes (/debug/api/*, /inspect/api/*)
```

## How It Works

### 1. Package Discovery

Auto-registered via `extra.laravel.providers` in composer.json. No manual registration needed.

### 2. Service Provider (`AppDevPanelServiceProvider`)

Registers in `register()`:
- Core services: `DebuggerIdGenerator`, `StorageInterface` (FileStorage), `TimelineCollector`
- All enabled collectors (based on config)
- `Debugger` with all collector references
- API services: middleware stack, controllers, inspector endpoints
- CLI commands: `debug:reset`, `debug:query`

Wires in `boot()`:
- Publishes config file
- Loads API routes
- Pushes `DebugMiddleware` into HTTP kernel
- Registers event listeners for Laravel events
- Decorates PSR services (Logger, HttpClient, EventDispatcher)

### 3. Data Capture Mechanisms

**HTTP Lifecycle (`DebugMiddleware`):**

| Phase | Action |
|-------|--------|
| Before request | Convert Laravel Request → PSR-7, `Debugger::startup()`, `RequestCollector`, `WebAppInfoCollector` |
| After response | `RequestCollector` captures response, route extraction, adds `X-Debug-Id` header |
| On exception | `ExceptionCollector` captures throwable |
| Terminate | `Debugger::shutdown()` flushes data to storage |

**Laravel Events → Collectors:**

| Laravel Event | Collector | Data |
|---------------|-----------|------|
| `Illuminate\Database\Events\QueryExecuted` | `DatabaseCollector` | SQL, bindings, time, backtrace |
| `Illuminate\Cache\Events\CacheHit` | `CacheCollector` | Cache get (hit) |
| `Illuminate\Cache\Events\CacheMissed` | `CacheCollector` | Cache get (miss) |
| `Illuminate\Cache\Events\KeyWritten` | `CacheCollector` | Cache set |
| `Illuminate\Cache\Events\KeyForgotten` | `CacheCollector` | Cache delete |
| `Illuminate\Mail\Events\MessageSent` | `MailerCollector` | Email details |
| `Illuminate\Queue\Events\JobProcessing` | `QueueCollector` | Job start time |
| `Illuminate\Queue\Events\JobProcessed` | `QueueCollector` | Job success + duration |
| `Illuminate\Queue\Events\JobFailed` | `QueueCollector` | Job failure + exception |
| `Illuminate\Http\Client\Events\RequestSending` | `HttpClientCollector` | Outgoing HTTP request |
| `Illuminate\Http\Client\Events\ResponseReceived` | `HttpClientCollector` | HTTP response |
| `Illuminate\Http\Client\Events\ConnectionFailed` | `HttpClientCollector` | Connection failure |
| `Illuminate\Console\Events\CommandStarting` | `Debugger` | Console lifecycle start |
| `Illuminate\Console\Events\CommandFinished` | `Debugger` | Console lifecycle end |

**PSR Proxy Decoration:**

| Service | Proxy | Method |
|---------|-------|--------|
| `Psr\Log\LoggerInterface` | `LoggerInterfaceProxy` | `$app->extend()` |
| `Psr\Http\Client\ClientInterface` | `HttpClientInterfaceProxy` | `$app->extend()` |
| `events` (event dispatcher) | `LaravelEventDispatcherProxy` | `$app->extend()` |

**VarDumper:**
- Custom handler registered via `VarDumper::setHandler()` in `DebugMiddleware`
- Captures `dump()` calls with source file/line

### 4. Inspector Integration

| Service | Class | Data |
|---------|-------|------|
| Config provider | `LaravelConfigProvider` | Services (bindings), config params, event listeners, service providers |
| Database schema | `LaravelSchemaProvider` | Tables, columns, records via `Illuminate\Database\Connection` |
| Route collection | `LaravelRouteCollectionAdapter` | All registered routes |
| URL matcher | `LaravelUrlMatcherAdapter` | Route matching test |

### 5. API Bridge

`AdpApiController` handles all `/debug/api/*` and `/inspect/api/*` requests:
1. Converts Laravel `Request` → PSR-7
2. Delegates to `ApiApplication::handle()`
3. Converts PSR-7 `Response` → Symfony `Response`
4. Detects SSE streams and returns `StreamedResponse`

CSRF protection is disabled for API routes.

## Configuration

```php
// config/app-dev-panel.php
return [
    'enabled' => env('APP_DEV_PANEL_ENABLED', env('APP_DEBUG', true)),
    'storage' => [
        'path' => storage_path('debug'),
        'history_size' => 50,
    ],
    'collectors' => [
        'environment' => true,
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
        'database' => true,
        'cache' => true,
        'mailer' => true,
        'queue' => true,
        'validator' => true,
        'router' => true,
    ],
    'ignored_requests' => ['/debug/api/*', '/inspect/api/*'],
    'ignored_commands' => ['completion', 'help', 'list', 'debug:*', 'cache:*'],
    'dumper' => ['excluded_classes' => []],
    'api' => [
        'enabled' => true,
        'allowed_ips' => ['127.0.0.1', '::1'],
        'auth_token' => env('APP_DEV_PANEL_TOKEN', ''),
    ],
];
```

## Architecture Comparison

| Aspect | Symfony Adapter | Laravel Adapter |
|--------|----------------|-----------------|
| Registration | Bundle + Extension + CompilerPass | ServiceProvider (register + boot) |
| DI wiring | `ContainerBuilder::register()` | `$app->singleton()` |
| Proxy decoration | `setDecoratedService()` in CompilerPass | `$app->extend()` in boot() |
| Event proxy | `SymfonyEventDispatcherProxy` (Component interface) | `LaravelEventDispatcherProxy` (Illuminate Dispatcher) |
| Event mapping | `EventSubscriberInterface` classes | `$events->listen()` in boot() |
| Config | YAML tree builder | PHP array (publishable) |
| Data capture | Symfony kernel events + CompilerPass decoration | Laravel events + middleware + extend() |
| Database | Doctrine DBAL `SchemaManager` | Illuminate `SchemaBuilder` + query builder |
| PSR-7 bridge | `nyholm/psr7-server` (HttpFoundation → PSR-7) | `Nyholm\Psr7\Factory\Psr17Factory` (direct) |
| Package discovery | Manual bundle registration | Auto via `extra.laravel.providers` |
