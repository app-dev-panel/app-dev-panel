# Redis Integration Guide

How to integrate `RedisCollector` into each supported framework. The collector is framework-agnostic — it accepts `RedisCommandRecord` via `logCommand()`. All framework-specific wiring lives in adapters.

## Architecture

```
App code → Redis client (decorated) → RedisCollector::logCommand() → Debugger → Storage
```

Two steps per adapter:
1. **Register** `RedisCollector` in DI (with `TimelineCollector` dependency)
2. **Intercept** Redis commands via decorator, proxy, or event listener

## RedisCollector API

```php
use AppDevPanel\Kernel\Collector\RedisCollector;
use AppDevPanel\Kernel\Collector\RedisCommandRecord;

$collector->logCommand(new RedisCommandRecord(
    connection: 'default',       // Connection/pool name
    command: 'SET',              // Redis command (uppercase)
    arguments: ['key', 'value'], // Command arguments
    result: true,                // Command return value
    duration: 0.0012,            // Seconds
    error: null,                 // Error message or null
    line: '/app/Service.php:42', // Source location (optional)
));
```

## RedisController (Inspector)

The API module provides `RedisController` for live Redis inspection at `/inspect/api/redis/*`.
It requires `\Redis` (phpredis) in the DI container. Endpoints: ping, info, db-size, keys (SCAN), get (type-aware), delete, flush-db.

## Framework Integration

### Symfony

**Registration** (`AppDevPanelExtension` or `services.yaml`):

```php
use AppDevPanel\Kernel\Collector\RedisCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;

$container->register(RedisCollector::class, RedisCollector::class)
    ->setArguments([new Reference(TimelineCollector::class)])
    ->setPublic(true)
    ->addTag('app_dev_panel.collector');
```

**Interception — Predis** (recommended for Symfony + SNCRedisBundle):

```php
use Predis\Command\CommandInterface;
use Predis\Plugin\PluginInterface;

final class RedisCollectorPlugin implements PluginInterface
{
    public function __construct(private RedisCollector $collector) {}

    public function onCommand(CommandInterface $command, callable $next): mixed
    {
        $start = microtime(true);
        $error = null;
        try {
            $result = $next($command);
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            throw $e;
        } finally {
            $this->collector->logCommand(new RedisCommandRecord(
                connection: 'default',
                command: strtoupper($command->getId()),
                arguments: $command->getArguments(),
                result: $result ?? null,
                duration: microtime(true) - $start,
                error: $error,
            ));
        }
        return $result;
    }
}
```

**Interception — phpredis** (decorator pattern):

```php
final class TrackedRedis
{
    public function __construct(
        private \Redis $redis,
        private RedisCollector $collector,
        private string $connection = 'default',
    ) {}

    public function __call(string $method, array $args): mixed
    {
        $start = microtime(true);
        $error = null;
        try {
            $result = $this->redis->$method(...$args);
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            throw $e;
        } finally {
            $this->collector->logCommand(new RedisCommandRecord(
                connection: $this->connection,
                command: strtoupper($method),
                arguments: $args,
                result: $result ?? null,
                duration: microtime(true) - $start,
                error: $error,
            ));
        }
        return $result;
    }
}
```

Register as decorator via `services.yaml`:
```yaml
App\Redis\TrackedRedis:
    decorates: Redis
    arguments:
        $redis: '@.inner'
        $collector: '@AppDevPanel\Kernel\Collector\RedisCollector'
```

### Laravel

**Registration** (`AppDevPanelServiceProvider`):

```php
$this->app->singleton(RedisCollector::class, fn($app) =>
    new RedisCollector($app->make(TimelineCollector::class))
);
$this->app->tag(RedisCollector::class, 'adp.collectors');
```

**Interception** — Laravel provides `Redis::listen()` out of the box:

```php
use Illuminate\Redis\Events\CommandExecuted;
use Illuminate\Support\Facades\Redis;

Redis::enableEvents();
Redis::listen(function (CommandExecuted $event) {
    app(RedisCollector::class)->logCommand(new RedisCommandRecord(
        connection: $event->connectionName,
        command: strtoupper($event->command),
        arguments: $event->parameters,
        result: null, // Laravel event doesn't expose result
        duration: $event->time / 1000, // ms → seconds
    ));
});
```

This is the simplest integration — no decorator needed.

### Yii 3 (Yiisoft)

**Registration** (`config/di.php`):

```php
use AppDevPanel\Kernel\Collector\RedisCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use Yiisoft\Definitions\DynamicReference;

return [
    RedisCollector::class => [
        'class' => RedisCollector::class,
        '__construct()' => [
            DynamicReference::to(TimelineCollector::class),
        ],
    ],
];
```

Add to collectors list in `config/params.php`:
```php
'app-dev-panel' => [
    'collectors' => [
        RedisCollector::class,
    ],
],
```

**Interception** — decorator via DI (Yii 3 has no built-in Redis component):

Use the `TrackedRedis` `__call` pattern shown above, registered as a DI decorator for whatever Redis client the application uses (phpredis or Predis).

### Yii 2

**Registration** (module config):

```php
'modules' => [
    'debug-panel' => [
        'class' => \AppDevPanel\Adapter\Yii2\Module::class,
        'collectors' => [
            'redis' => true,
        ],
    ],
],
```

**Interception** — via `yii\redis\Connection` events (if `yiisoft/yii2-redis` is installed):

```php
use yii\redis\Connection;

Event::on(Connection::class, Connection::EVENT_AFTER_EXECUTE, function ($event) {
    $module = \Yii::$app->getModule('debug-panel');
    $collector = $module->getCollector(RedisCollector::class);
    $collector?->logCommand(new RedisCommandRecord(
        connection: 'default',
        command: strtoupper($event->command),
        arguments: $event->params,
        result: $event->result,
        duration: $event->duration,
    ));
});
```

## Inspector Integration

To enable the Redis inspector page, register `\Redis` in the DI container:

| Framework | Registration |
|-----------|-------------|
| Symfony | `services.yaml`: `Redis: { factory: [...] }` or auto-wired from SNCRedisBundle |
| Laravel | Available automatically via `app(\Redis::class)` when phpredis is installed |
| Yii 3 | `di.php`: `\Redis::class => fn() => new \Redis(...)` |
| Yii 2 | `Yii::$container->setSingleton(\Redis::class, fn() => ...)` |

## Fixture Testing

The `redis:basic` fixture is available at `/test/fixtures/redis` in all playgrounds.
It logs 6 commands (SET, GET, DEL, INCR, LPUSH, GET-with-error) across 2 connections.

Expectations: `summaryGte('redis.commandCount', 5)`, `summaryGte('redis.errorCount', 1)`.
