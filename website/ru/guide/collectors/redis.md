---
title: Коллектор Redis
---

# Коллектор Redis

<class>\AppDevPanel\Kernel\Collector\RedisCollector</class> захватывает все команды Redis, выполненные во время запроса — SET, GET, DEL, INCR и любые другие команды — с замерами времени, отслеживанием ошибок и поддержкой нескольких подключений.

## Собираемые данные

| Поле | Описание |
|------|----------|
| `connection` | Имя подключения Redis (например, `default`, `cache`) |
| `command` | Имя команды (например, `SET`, `GET`, `DEL`) |
| `arguments` | Массив аргументов команды |
| `result` | Возвращаемое значение |
| `duration` | Время выполнения в секундах |
| `error` | Сообщение об ошибке (или `null` при успехе) |
| `line` | Файл и строка исходного кода (опционально) |

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

**Сводка** (отображается в списке отладочных записей):

```json
{
    "redis": {
        "commandCount": 3,
        "errorCount": 0,
        "totalTime": 0.0035
    }
}
```

## Как это работает

Коллектор является фреймворк-агностичным. Он принимает нормализованные данные через `logCommand()`:

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
<class>\AppDevPanel\Kernel\Collector\RedisCollector</class> реализует <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class> и использует <class>\AppDevPanel\Kernel\Collector\CollectorTrait</class> для стандартных методов жизненного цикла. Зависит от <class>\AppDevPanel\Kernel\Collector\TimelineCollector</class> для интеграции с кросс-коллекторной временной шкалой.
:::

Адаптеры фреймворков отвечают за перехват вызовов Redis и передачу данных коллектору. Стандарта PSR для Redis не существует, поэтому каждый фреймворк использует собственный механизм перехвата.

## Интеграция с фреймворками

### Laravel

Laravel предоставляет `Redis::listen()` из коробки — декоратор не требуется:

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
Событие `CommandExecuted` в Laravel не предоставляет результат команды. Если вам нужно отслеживание результатов, используйте декоратор phpredis.
:::

### Symfony

Два варианта в зависимости от вашего Redis-клиента:

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

Зарегистрируйте декоратор phpredis в `services.yaml`:

```yaml
App\Redis\TrackedRedis:
    decorates: Redis
    arguments:
        $redis: '@.inner'
        $collector: '@AppDevPanel\Kernel\Collector\RedisCollector'
```

### Yii 3

Зарегистрируйте коллектор в DI и используйте паттерн декоратора phpredis:

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

Добавьте в список коллекторов в `config/params.php`:

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
    $module = \Yii::$app->getModule('app-dev-panel');
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

ADP предоставляет живой инспектор Redis по адресу `/inspector/redis`. Полное описание эндпоинтов см. в [API инспектора](/ru/api/inspector#redis).

**Вкладка «Ключи»** — просмотр, поиск, отображение и удаление ключей Redis:
- Поиск по паттерну (например, `user:*`) с использованием Redis SCAN
- Отображение значений с учётом типа (string, list, set, zset, hash, stream)
- Информация о TTL для каждого ключа
- Удаление отдельных ключей или очистка всей базы данных

**Вкладка «Информация о сервере»** — полная информация о сервере Redis из команды `INFO`.

::: warning
Инспектор требует `\Redis` (расширение phpredis), зарегистрированное в DI-контейнере. Predis для живой инспекции не поддерживается.
:::

## Панель отладки

Панель отладки показывает данные Redis, собранные во время запроса:

- **Карточки сводки** — общее количество команд, общее время, количество ошибок, подключения
- **Разбивка по подключениям** — статистика по подключениям при использовании нескольких подключений
- **Фильтруемый список команд** — поиск по имени команды, подключению или аргументам
- **Раскрываемые подробности** — аргументы, результат, сообщение об ошибке, местоположение в исходном коде
- **Цветовая кодировка** — чтение (синий), запись (зелёный), удаление (жёлтый), ошибки (красный)
