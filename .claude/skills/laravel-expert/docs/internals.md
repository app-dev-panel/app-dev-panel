# Laravel Internals Deep Dive

## Application Lifecycle

```
public/index.php
  → require bootstrap/app.php
    → Application::__construct($basePath)
      → registerBaseBindings()        # $app, Container::class, PackageManifest
      → registerBaseServiceProviders() # EventServiceProvider, LogServiceProvider, RoutingServiceProvider
      → registerCoreContainerAliases() # 100+ aliases (app, auth, cache, config, db, etc.)
  → $app->handleRequest(Request::capture())
    → bootstrap()                     # LoadEnvironmentVariables, LoadConfiguration, HandleExceptions,
                                      # RegisterFacades, RegisterProviders, BootProviders
    → Pipeline::send($request)->through($middleware)->then(dispatchToRouter)
      → Global middleware (TrustProxies, CORS, ValidateCsrfToken, etc.)
      → Router::dispatch($request)
        → findRoute($request)         # Match URI + method against compiled RouteCollection
        → runRoute($request, $route)
          → Route middleware pipeline
          → Controller::callAction($method, $params)
      → Response preparation
    → terminate($request, $response)  # Terminable middleware, service provider terminate()
```

## Service Container (Illuminate\Container\Container)

Laravel's DI container is the most powerful in PHP ecosystem.

### Binding Types

```php
// Simple binding — new instance each time
$app->bind(InterfaceA::class, ConcreteA::class);
$app->bind('foo', fn ($app) => new Foo($app->make('bar')));

// Singleton — one instance for app lifetime
$app->singleton(InterfaceB::class, ConcreteB::class);
$app->singleton('cache', fn ($app) => new CacheManager($app));

// Scoped — singleton within a request, reset between requests (Octane)
$app->scoped(InterfaceC::class, ConcreteC::class);

// Instance — already-created object
$app->instance('config', $configInstance);

// Contextual binding — different implementation per consumer
$app->when(PhotoController::class)
    ->needs(StorageInterface::class)
    ->give(S3Storage::class);

// Tagged bindings
$app->tag([CpuReport::class, MemoryReport::class], 'reports');
$app->tagged('reports'); // iterable of all tagged services
```

### Resolution Order

1. Check `$instances` (already resolved singletons/instances)
2. Check `$bindings` → execute closure or resolve class
3. If contextual binding exists for current build stack → use it
4. Check `$aliases` → resolve alias chain
5. Auto-resolve via reflection (constructor injection)
6. Apply `$extenders` (decoration callbacks)
7. Fire `resolving` and `afterResolving` callbacks
8. Store in `$instances` if singleton/scoped

### Container Internals

```
$bindings     — array<string, {concrete: Closure, shared: bool}>
$instances    — array<string, object>  (resolved singletons)
$aliases      — array<string, string>  (alias → abstract)
$abstractAliases — array<string, string[]>  (abstract → [aliases])
$extenders    — array<string, Closure[]>  (decoration stack)
$tags         — array<string, string[]>  (tag → [abstracts])
$buildStack   — string[]  (current resolution chain, for contextual)
$reboundCallbacks — array<string, Closure[]>  (fire when re-bound)
$scopedInstances  — array<string, object>  (reset per request in Octane)
```

### extend() — Service Decoration

```php
$app->extend(EventDispatcher::class, function ($dispatcher, $app) {
    return new EventDispatcherProxy($dispatcher, $app->make(EventCollector::class));
});
```

**How it works internally:**
1. Stores closure in `$extenders[abstract][]`
2. On next `make()`: resolves concrete, then pipes through ALL extenders in order
3. If already resolved (in `$instances`): immediately applies extender, updates `$instances`
4. Multiple `extend()` calls stack — each wraps the previous result

**Gotcha:** If service already resolved before `extend()`, the extender runs immediately and replaces the cached instance. Order matters — call `extend()` in `boot()`, not `register()`.

### Deferred Providers

```php
class HeavyServiceProvider extends ServiceProvider
{
    public array $defer = true;
    public function provides(): array { return [HeavyService::class]; }
}
```

- Not loaded until `HeavyService::class` is first resolved
- Manifest cached in `bootstrap/cache/services.php`
- `PackageManifest` scans `extra.laravel.providers` in `composer.json`

## Service Provider Lifecycle

```
ServiceProvider::register()     # Bind into container. NO $app->make() here.
  ↓ (all providers registered)
ServiceProvider::boot()         # Use container freely. Wire events, routes, middleware.
  ↓ (all providers booted)
Application handles request
  ↓
ServiceProvider::terminate()    # Cleanup (if method exists, called after response sent)
```

**Critical rules:**
- `register()` — ONLY `$app->bind()`, `$app->singleton()`, `$app->extend()`. Never resolve services.
- `boot()` — safe to resolve services, register event listeners, publish configs, load routes.
- `terminate()` — runs after response is sent to client (terminable middleware pattern).

### Package Discovery

```json
// composer.json
"extra": {
    "laravel": {
        "providers": ["AppDevPanel\\Adapter\\Laravel\\AppDevPanelServiceProvider"],
        "aliases": {}
    }
}
```

Auto-discovered via `PackageManifest`. No manual registration in `config/app.php` needed.

## Event System

### Architecture

```
Illuminate\Events\Dispatcher
  ├── $listeners     — array<string, Closure[]>  (event → handlers)
  ├── $wildcards     — array<string, Closure[]>  (pattern → handlers)
  ├── $wildcardsCache — array<string, Closure[]>  (resolved wildcard matches)
  └── $queueResolver — Closure  (for queued listeners)
```

### Dispatch Flow

```php
$events->dispatch(new OrderShipped($order));
// OR
$events->dispatch('order.shipped', [$order]);
```

1. Get event name (class name or string)
2. Collect listeners: exact match + wildcard matches
3. If event is `ShouldBroadcast` → queue for broadcasting
4. Call each listener in registration order
5. If listener returns `false` → stop propagation (only with `dispatch($event, $payload, $halt=true)`)
6. If listener implements `ShouldQueue` → push to queue instead of calling

### Listener Registration

```php
// Closure
Event::listen(OrderShipped::class, function (OrderShipped $event) { ... });

// Class (resolved from container)
Event::listen(OrderShipped::class, SendShipmentNotification::class);

// Class with method
Event::listen(OrderShipped::class, [OrderListener::class, 'handleShipped']);

// Wildcard
Event::listen('order.*', function (string $eventName, array $data) { ... });

// Subscriber (class with subscribe() method)
Event::subscribe(OrderEventSubscriber::class);
```

### Built-in Events Used by ADP

| Event Class | When | Data |
|-------------|------|------|
| `Illuminate\Database\Events\QueryExecuted` | After each DB query | `$sql`, `$bindings`, `$time`, `$connection` |
| `Illuminate\Cache\Events\CacheHit` | Cache key found | `$key`, `$value`, `$tags` |
| `Illuminate\Cache\Events\CacheMissed` | Cache key not found | `$key`, `$tags` |
| `Illuminate\Cache\Events\KeyWritten` | Cache key set | `$key`, `$value`, `$seconds`, `$tags` |
| `Illuminate\Cache\Events\KeyForgotten` | Cache key deleted | `$key`, `$tags` |
| `Illuminate\Mail\Events\MessageSent` | Email sent | `$sent` (SentMessage), `$data` |
| `Illuminate\Queue\Events\JobProcessing` | Job starting | `$connectionName`, `$job` |
| `Illuminate\Queue\Events\JobProcessed` | Job finished | `$connectionName`, `$job` |
| `Illuminate\Queue\Events\JobFailed` | Job failed | `$connectionName`, `$job`, `$exception` |
| `Illuminate\Http\Client\Events\RequestSending` | HTTP request starting | `$request` |
| `Illuminate\Http\Client\Events\ResponseReceived` | HTTP response received | `$request`, `$response` |
| `Illuminate\Http\Client\Events\ConnectionFailed` | HTTP connection failed | `$request`, `$exception` |
| `Illuminate\Console\Events\CommandStarting` | CLI command start | `$command`, `$input`, `$output` |
| `Illuminate\Console\Events\CommandFinished` | CLI command end | `$command`, `$exitCode` |

## Middleware Pipeline

```
Global middleware (Kernel::$middleware)
  → Route group middleware (Kernel::$middlewareGroups['web'] or ['api'])
    → Route-specific middleware
      → Controller constructor
        → Controller method
```

### Middleware Internals

```php
class ExampleMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Before request
        $response = $next($request);
        // After response
        return $response;
    }

    public function terminate(Request $request, Response $response): void
    {
        // After response sent to client
    }
}
```

**Pipeline implementation:** `Illuminate\Pipeline\Pipeline` uses `array_reduce()` to build a nested closure chain. Each middleware wraps the next. Execution is outside-in (first registered = first to handle request, last to handle response).

### Registering Middleware

```php
// In ServiceProvider::boot()
$this->app->make(HttpKernel::class)->pushMiddleware(DebugMiddleware::class);

// Or prepend (runs before all others)
$this->app->make(HttpKernel::class)->prependMiddleware(DebugMiddleware::class);

// Route-level
Route::middleware(['auth', 'throttle:60,1'])->group(function () { ... });
```

## Routing

### Route Registration

```php
Route::get('/api/debug/{id}', [DebugController::class, 'show']);
Route::prefix('debug/api')->group(function () {
    Route::get('/{path}', [AdpApiController::class, 'handle'])->where('path', '.*');
});
```

### Route Resolution

```
Router::dispatch($request)
  → RouteCollection::match($request)
    → getRoutesByMethod($request->getMethod())
    → foreach: matchAgainstRoutes($routes, $request)
      → Route::matches($request)  // URI regex + domain + scheme + method check
    → return first match or throw NotFoundHttpException
  → Route::bind($request)         // Resolve route parameters, model bindings
  → runRouteWithinStack($route, $request)
    → Route middleware pipeline
    → Route::run()
      → Controller::callAction() or Closure
```

### Route Model Binding

```php
Route::get('/users/{user}', function (User $user) { ... });
// Resolves: User::findOrFail($routeParam)

// Explicit binding
Route::model('user', User::class);

// Custom resolution
Route::bind('user', fn ($value) => User::where('slug', $value)->firstOrFail());
```
