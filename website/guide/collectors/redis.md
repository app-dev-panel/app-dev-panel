---
title: Redis Collector
description: "ADP Redis Collector captures Redis commands with arguments, execution time, and connection info."
---

# Redis Collector

The <class>\AppDevPanel\Kernel\Collector\RedisCollector</class> captures all Redis commands executed during a request — SET, GET, DEL, INCR, and any other command — with timing, error tracking, and multi-connection support.

## What It Captures

| Field | Description |
|-------|-------------|
| `connection` | Redis connection name (e.g., `default`, `cache`) |
| `command` | Command name (e.g., `SET`, `GET`, `DEL`) |
| `arguments` | Command arguments array |
| `result` | Return value |
| `duration` | Execution time in seconds |
| `error` | Error message (or `null` on success) |
| `line` | Source file and line (optional) |

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

The collector is framework-agnostic. It accepts normalized data via `logCommand()`:

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

::: info
<class>\AppDevPanel\Kernel\Collector\RedisCollector</class> implements <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class> and uses <class>\AppDevPanel\Kernel\Collector\CollectorTrait</class> for the standard lifecycle methods. It depends on <class>\AppDevPanel\Kernel\Collector\TimelineCollector</class> for cross-collector timeline integration.
:::

Framework adapters are responsible for intercepting Redis calls and feeding data to the collector. There is no PSR standard for Redis, so each framework uses its own interception mechanism.

## Framework Integration

### Laravel

Laravel provides `Redis::listen()` out of the box — no decorator needed:

```php
use Illuminate\Redis\Events\CommandExecuted;
use Illuminate\Support\Facades\Redis;

// In your service provider
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

::: tip
Laravel's `CommandExecuted` event does not expose the command result. If you need result tracking, use a phpredis decorator instead.
:::

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

Register the phpredis decorator in `services.yaml`:

```yaml
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

Add to collectors list in `config/params.php`:

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
    $module = \Yii::$app->getModule('adp');
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

ADP provides a live Redis inspector at `/inspector/redis`. See the full endpoint reference in [Inspector API](/api/inspector#redis).

**Keys tab** — browse, search, view, and delete Redis keys:
- Pattern-based search (e.g., `user:*`) using Redis SCAN
- Type-aware value display (string, list, set, zset, hash, stream)
- TTL information for each key
- Delete individual keys or flush the entire database

**Server Info tab** — full Redis server information from the `INFO` command.

::: warning
The inspector requires `\Redis` (phpredis extension) registered in the DI container. It does not support Predis for live inspection.
:::

## Debug Panel

The debug panel shows Redis data collected during a request:

- **Summary cards** — total commands, total time, error count, connections
- **Connection breakdown** — per-connection statistics when multiple connections are used
- **Filterable command list** — search by command name, connection, or arguments
- **Expandable details** — arguments, result, error message, source location
- **Color coding** — reads (blue), writes (green), deletes (yellow), errors (red)
