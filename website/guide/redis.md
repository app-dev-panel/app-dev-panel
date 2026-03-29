---
title: Redis Collector
---

# Redis Collector

The `RedisCollector` captures all Redis commands executed during a request — SET, GET, DEL, INCR, and any other command — with timing, error tracking, and multi-connection support.

## What It Captures

- **Command**: name, arguments, return value
- **Timing**: execution duration in seconds
- **Errors**: error message when a command fails
- **Connection**: which Redis connection was used
- **Source**: file and line where the command was called

## Data Schema

```json
{
    "commands": [
        {
            "connection": "default",
            "command": "SET",
            "arguments": ["user:42", "{\"name\":\"John\"}"],
            "result": true,
            "duration": 0.0012,
            "error": null,
            "line": "/app/src/UserService.php:42"
        }
    ],
    "totalTime": 0.0035,
    "errorCount": 0,
    "totalCommands": 3,
    "connections": ["default", "cache"]
}
```

**Summary** (shown in debug entry list):

```json
{
    "redis": {
        "commandCount": 3,
        "errorCount": 0,
        "totalTime": 0.0035
    }
}
```

## How It Works

The collector is **framework-agnostic**. It accepts normalized data via `logCommand()`:

```php
use AppDevPanel\Kernel\Collector\RedisCollector;
use AppDevPanel\Kernel\Collector\RedisCommandRecord;

$collector->logCommand(new RedisCommandRecord(
    connection: 'default',
    command: 'SET',
    arguments: ['user:42', '{"name":"John"}'],
    result: true,
    duration: 0.0012,
    error: null,
    line: '/app/src/UserService.php:42',
));
```

Framework adapters are responsible for intercepting Redis calls and feeding data to the collector.

## Framework Integration

### Laravel

Laravel is the simplest — it provides `Redis::listen()` out of the box:

```php
use Illuminate\Redis\Events\CommandExecuted;
use Illuminate\Support\Facades\Redis;

// In your AppDevPanelServiceProvider
Redis::enableEvents();
Redis::listen(function (CommandExecuted $event) {
    app(RedisCollector::class)->logCommand(new RedisCommandRecord(
        connection: $event->connectionName,
        command: strtoupper($event->command),
        arguments: $event->parameters,
        result: null,
        duration: $event->time / 1000, // ms → seconds
    ));
});
```

### Symfony

Two options depending on your Redis client:

::: code-group

```php [Predis Plugin]
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

```php [phpredis Decorator]
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

:::

Register as a Symfony service decorator:

```yaml
# services.yaml
App\Redis\TrackedRedis:
    decorates: Redis
    arguments:
        $redis: '@.inner'
        $collector: '@AppDevPanel\Kernel\Collector\RedisCollector'
```

### Yii 3

Register the collector in DI and use the phpredis decorator pattern:

```php
// config/di.php
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

Add to the collectors list in `config/params.php`:

```php
'app-dev-panel' => [
    'collectors' => [
        RedisCollector::class,
    ],
],
```

### Yii 2

If using `yiisoft/yii2-redis`:

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

## Redis Inspector

ADP also provides a **live Redis inspector** at `/inspector/redis` with two tabs:

**Keys** — browse, search, view, and delete Redis keys:
- Pattern-based search (e.g., `user:*`)
- Type-aware value display (string, list, set, zset, hash, stream)
- TTL information
- Delete individual keys or flush the entire database

**Server Info** — full Redis server information from the `INFO` command.

The inspector requires `\Redis` (phpredis extension) registered in the DI container.

## Frontend Panel

The debug panel shows Redis data with:
- **Summary cards**: total commands, total time, error count, connections
- **Connection breakdown**: per-connection statistics (when multiple connections are used)
- **Command list**: filterable, with expandable details for each command
- **Color coding**: read commands (blue), write commands (green), delete commands (yellow), errors (red)
