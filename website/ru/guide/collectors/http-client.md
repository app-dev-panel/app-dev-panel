---
title: Коллектор HTTP-клиента
---

# Коллектор HTTP-клиента

Собирает исходящие PSR-18 HTTP-запросы и ответы с таймингом и кодами статуса.

![Панель коллектора HTTP-клиента](/images/collectors/http-client.png)

## Что собирает

| Поле | Описание |
|------|----------|
| `method` | HTTP-метод (GET, POST и т.д.) |
| `uri` | URI запроса |
| `headers` | Заголовки запроса |
| `line` | Исходный файл и строка HTTP-вызова |
| `responseStatus` | Код статуса HTTP-ответа |
| `responseRaw` | Сырое тело ответа |
| `totalTime` | Общее время запроса/ответа в секундах |

## Схема данных

```json
[
    {
        "startTime": 1711878000.100,
        "endTime": 1711878000.350,
        "totalTime": 0.25,
        "method": "GET",
        "uri": "https://api.example.com/users/42",
        "headers": {"Authorization": "Bearer ***"},
        "line": "/app/src/ApiClient.php:55",
        "responseRaw": "{\"id\": 42, \"name\": \"John\"}",
        "responseStatus": 200
    }
]
```

**Сводка** (отображается в списке отладочных записей):

```json
{
    "http": {
        "count": 3,
        "totalTime": 0.75
    }
}
```

## Контракт

```php
use AppDevPanel\Kernel\Collector\HttpClientCollector;

// Start collection
$collector->collect(
    request: $psrRequest,
    startTime: microtime(true),
    line: '/app/src/ApiClient.php:55',
    uniqueId: 'req-1',
);

// Complete with response
$collector->collectTotalTime(
    response: $psrResponse,
    endTime: microtime(true),
    uniqueId: 'req-1',
);
```

::: info
<class>\AppDevPanel\Kernel\Collector\HttpClientCollector</class> реализует <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class> и зависит от <class>\AppDevPanel\Kernel\Collector\TimelineCollector</class>.
:::

## Как это работает

Коллектор получает данные от <class>\AppDevPanel\Kernel\Collector\HttpClientInterfaceProxy</class> — декоратора PSR-18 <class>Psr\Http\Client\ClientInterface</class>. Каждый вызов `$client->sendRequest($request)` автоматически перехватывается, замеряется и записывается.

## Панель отладки

- **Список запросов** — все исходящие HTTP-вызовы с методом, URL, статусом и таймингом
- **Детали запроса/ответа** — раскрываемое представление с заголовками и телом
- **Бейджи статусов** — цветовая кодировка по статусу ответа (2xx зелёный, 4xx оранжевый, 5xx красный)
- **Разбивка по времени** — длительность каждого запроса
