# PHP 8.4+ Best Practices for Laravel Integration

## Code Standards

1. **`declare(strict_types=1)`** — always
2. **`final` classes** — all new classes unless designed for extension
3. **`readonly` properties** — for immutable state
4. **Property hooks** (PHP 8.4) — for computed/validated properties
5. **Named arguments** — for Laravel APIs with many optional params
6. **Enums** — instead of class constants for finite value sets
7. **Union/intersection types** — full type declarations, no `mixed` unless truly polymorphic
8. **`#[Override]`** attribute — on all methods overriding parent/interface
9. **First-class callables** — `$this->method(...)` over `[$this, 'method']`
10. **`array_find()`**, **`array_any()`**, **`array_all()`** (PHP 8.4) — over manual loops

## Architecture Patterns

1. **Wrap, don't extend** — composition over inheriting Laravel base classes
2. **PSR interfaces** — depend on PSR-3/7/11/14/15/17/18, not Illuminate contracts
3. **Immutable DTOs** — `readonly class` for data between Laravel and Kernel layers
4. **No Facades in new code** — inject dependencies via constructor
5. **No `app()` helper** — pass dependencies explicitly
6. **No `config()` helper in library code** — accept config values via constructor
7. **Adapter pattern** — wrap Laravel services behind PSR interfaces for Kernel
8. **No `@` error suppression**
9. **No `extract()`/`compact()`**
10. **Fiber-aware** — don't assume single-thread (Octane runs in long-lived process)

## Service Provider Patterns

### Correct Registration

```php
final class AppDevPanelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ONLY bindings — no resolving
        $this->mergeConfigFrom(__DIR__ . '/../config/app-dev-panel.php', 'app-dev-panel');

        $this->app->singleton(StorageInterface::class, function ($app) {
            $config = $app->make('config')->get('app-dev-panel.storage');
            return new FileStorage($config['path'], $config['history_size']);
        });
    }

    public function boot(): void
    {
        // Safe to resolve, register events, routes, middleware
        $this->publishes([...], 'config');
        $this->loadRoutesFrom(__DIR__ . '/../routes/adp.php');

        // Decorate services
        $this->app->extend(LoggerInterface::class, function ($logger, $app) {
            return new LoggerInterfaceProxy($logger, $app->make(LogCollector::class));
        });
    }
}
```

### Anti-Patterns

```php
// BAD: resolving in register()
public function register(): void
{
    $debugger = $this->app->make(Debugger::class); // May not be bound yet!
}

// BAD: using Facade in library code
use Illuminate\Support\Facades\Config;
$path = Config::get('app-dev-panel.storage.path'); // Couples to Laravel

// BAD: relying on boot() order
public function boot(): void
{
    // Another provider's boot() may not have run yet
    $otherService = $this->app->make(OtherPackageService::class); // May be unbooted
}
```

## Event Listener Patterns

### Correct

```php
// In boot()
$events = $this->app->make(Dispatcher::class);
$events->listen(QueryExecuted::class, function (QueryExecuted $event) use ($collector) {
    $collector->logQuery(
        $event->sql,
        $event->bindings,
        $event->time,
        $event->connectionName,
    );
});
```

### Queued Listeners — When NOT to Use

ADP collectors must capture data synchronously during the request. Never use `ShouldQueue` for debug data collection — data arrives after request is gone.

## Middleware Patterns

### Terminable Middleware for Debugger

```php
final class DebugMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Convert to PSR-7, start debugger
        $debugger->startup($context);
        $response = $next($request);
        // Capture response data
        return $response;
    }

    public function terminate(Request $request, Response $response): void
    {
        // Flush to storage AFTER response sent
        $debugger->shutdown();
    }
}
```

**Why terminate():** Flushing to storage is I/O. Doing it in `handle()` delays the response. `terminate()` runs after `$response->send()`.

## Common Pitfalls

| Pitfall | Why | Fix |
|---------|-----|-----|
| `extend()` in `register()` | Service may not exist yet | Call `extend()` in `boot()` |
| `extend()` after resolution | Extender applies immediately, may miss | Ensure `extend()` before first `make()` |
| Decorating `events` | Must wrap `Illuminate\Contracts\Events\Dispatcher`, not `Illuminate\Events\Dispatcher` | Check contract vs concrete |
| CSRF on API routes | Laravel validates CSRF on web routes | Exclude debug API routes from `VerifyCsrfToken` |
| Request body consumed | `php://input` read once | Cache raw body before middleware chain |
| Octane compatibility | Singletons persist across requests | Use `scoped()` or reset state in `terminate()` |
| Config caching | `config:cache` flattens all config | Always use `mergeConfigFrom()` in `register()` |
| Route caching | `route:cache` skips closure routes | Use controller classes, not closures |
| PSR-7 conversion | Laravel uses Symfony HttpFoundation internally | Use `nyholm/psr7` factories for conversion |
| Multiple DB connections | `QueryExecuted` fires for all connections | Filter by `$event->connectionName` if needed |

## Testing Laravel Integration

- Use `Orchestra\Testbench` for package testing (but ADP uses inline mocks per project convention)
- Mock `Application` container via `$this->createMock(Container::class)`
- Mock `Config` via `$this->createMock(Repository::class)`
- Test event listeners in isolation — instantiate listener, call method with mock event
- Test middleware — call `handle()` with mock `Request` and `Closure`
- Never use `artisan test` in library code — use `vendor/bin/phpunit`

## Before Implementing

1. Read the ADP Laravel adapter — `libs/Adapter/Laravel/src/`
2. Read the Kernel collector interfaces — `libs/Kernel/src/Collector/`
3. Read existing tests — `libs/Adapter/Laravel/tests/`
4. Check Laravel source for the exact event/hook you need

## After Implementing

1. Run `make test-php` — all tests pass
2. Run `make mago-fix` — formatting and lint clean
3. Test against Laravel playground: `make fixtures-laravel`
4. Verify no Laravel classes leak into Kernel or API modules
