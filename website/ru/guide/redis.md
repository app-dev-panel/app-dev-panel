---
title: Redis Коллектор
---

# Redis Коллектор

`RedisCollector` перехватывает все Redis-команды во время запроса — SET, GET, DEL, INCR и любые другие — с замером времени, отслеживанием ошибок и поддержкой нескольких соединений.

## Что собирает

| Поле | Описание |
|------|----------|
| `connection` | Имя Redis-соединения (например, `default`, `cache`) |
| `command` | Имя команды (например, `SET`, `GET`, `DEL`) |
| `arguments` | Массив аргументов команды |
| `result` | Возвращаемое значение |
| `duration` | Время выполнения в секундах |
| `error` | Сообщение об ошибке (или `null` при успехе) |
| `line` | Исходный файл и строка (опционально) |

## Схема данных

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

**Сводка** (отображается в списке debug-записей):

```json
{
    "redis": {
        "commandCount": 3,
        "errorCount": 0,
        "totalTime": 0.0035
    }
}
```

## Как работает

Коллектор фреймворк-независимый. Принимает нормализованные данные через `logCommand()`:

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
`RedisCollector` реализует `SummaryCollectorInterface` и использует `CollectorTrait` для стандартных методов жизненного цикла. Зависит от `TimelineCollector` для интеграции с кросс-коллекторной временной шкалой.
:::

Адаптеры фреймворков отвечают за перехват Redis-вызовов и передачу данных в коллектор. Для Redis нет PSR-стандарта, поэтому каждый фреймворк использует свой механизм перехвата.

## Интеграция с фреймворками

### Laravel

Laravel предоставляет `Redis::listen()` из коробки — декоратор не нужен:

```php
use Illuminate\Redis\Events\CommandExecuted;
use Illuminate\Support\Facades\Redis;

// В вашем сервис-провайдере
Redis::enableEvents();
Redis::listen(function (CommandExecuted $event) {
    app(RedisCollector::class)->logCommand(new RedisCommandRecord(
        connection: $event->connectionName,
        command: strtoupper($event->command),
        arguments: $event->parameters,
        result: null,
        duration: $event->time / 1000, // мс → секунды
    ));
});
```

::: tip
Событие `CommandExecuted` в Laravel не предоставляет результат команды. Если нужно отслеживать результаты, используйте декоратор phpredis.
:::

### Symfony

Два варианта в зависимости от Redis-клиента:

::: code-group

```php [Плагин Predis]
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

```php [Декоратор phpredis]
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

Регистрация декоратора phpredis в `services.yaml`:

```yaml
App\Redis\TrackedRedis:
    decorates: Redis
    arguments:
        $redis: '@.inner'
        $collector: '@AppDevPanel\Kernel\Collector\RedisCollector'
```

### Yii 3

Регистрация коллектора в DI и использование декоратора phpredis:

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

Добавить в список коллекторов в `config/params.php`:

```php
'app-dev-panel' => [
    'collectors' => [
        RedisCollector::class,
    ],
],
```

### Yii 2

При использовании `yiisoft/yii2-redis`:

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

## Инспектор Redis

ADP предоставляет live-инспектор Redis на странице `/inspector/redis`. Полный справочник эндпоинтов — в [API инспектора](/ru/api/inspector#redis).

**Вкладка Keys** — просмотр, поиск и удаление ключей:
- Поиск по паттерну (например, `user:*`) через Redis SCAN
- Отображение значений с учётом типа (string, list, set, zset, hash, stream)
- Информация о TTL для каждого ключа
- Удаление отдельных ключей или очистка всей базы

**Вкладка Server Info** — полная информация о Redis-сервере из команды `INFO`.

::: warning
Инспектор требует `\Redis` (расширение phpredis), зарегистрированный в DI-контейнере. Predis для live-инспекции не поддерживается.
:::

## Панель отладки

Панель отладки показывает данные Redis, собранные во время запроса:

- **Карточки сводки** — общее количество команд, время, ошибки, соединения
- **Разбивка по соединениям** — статистика по каждому соединению при использовании нескольких
- **Фильтруемый список команд** — поиск по имени команды, соединению или аргументам
- **Раскрываемые детали** — аргументы, результат, сообщение об ошибке, расположение в коде
- **Цветовая кодировка** — чтение (синий), запись (зелёный), удаление (жёлтый), ошибки (красный)
