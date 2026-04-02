---
title: Коллектор запросов
---

# Коллектор запросов

Захватывает детали входящего HTTP-запроса и ответа — метод, путь, заголовки, параметры запроса, код статуса и необработанные тела.

![Панель коллектора запросов](/images/collectors/request.png)

## Собираемые данные

| Поле | Описание |
|------|----------|
| `requestUrl` | Полный URL запроса |
| `requestPath` | Путь URL |
| `requestQuery` | Строка запроса |
| `requestMethod` | HTTP-метод (GET, POST и т.д.) |
| `requestIsAjax` | Является ли AJAX/XHR запросом |
| `userIp` | IP-адрес клиента |
| `responseStatusCode` | HTTP-код статуса ответа |
| `request` | Полный объект PSR-7 ServerRequest |
| `requestRaw` | Необработанный HTTP-запрос |
| `response` | Полный объект PSR-7 Response |
| `responseRaw` | Необработанный HTTP-ответ |

## Схема данных

```json
{
    "requestUrl": "http://app.local/users?page=2",
    "requestPath": "/users",
    "requestQuery": "page=2",
    "requestMethod": "GET",
    "requestIsAjax": false,
    "userIp": "127.0.0.1",
    "responseStatusCode": 200,
    "requestRaw": "GET /users?page=2 HTTP/1.1\r\nHost: app.local\r\n\r\n",
    "responseRaw": "HTTP/1.1 200 OK\r\nContent-Type: text/html\r\n\r\n..."
}
```

**Сводка** (отображается в списке отладочных записей):

```json
{
    "request": {
        "url": "http://app.local/users?page=2",
        "path": "/users",
        "query": "page=2",
        "method": "GET",
        "isAjax": false,
        "userIp": "127.0.0.1"
    },
    "response": {
        "statusCode": 200
    }
}
```

## Контракт

```php
use AppDevPanel\Kernel\Collector\Web\RequestCollector;

$collector->collectRequest(request: $serverRequest);
$collector->collectResponse(response: $response);
```

::: info
<class>\AppDevPanel\Kernel\Collector\Web\RequestCollector</class> реализует <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class> и зависит от <class>\AppDevPanel\Kernel\Collector\TimelineCollector</class>. Расположен в подпространстве имён `Web`.
:::

## Как это работает

Адаптеры фреймворков собирают PSR-7 запрос в начале конвейера middleware и ответ в конце. Коллектор сохраняет как разобранные объекты, так и необработанные HTTP-представления.

## Панель отладки

- **Вкладки запрос/ответ** — переключение между представлениями запроса и ответа
- **Таблица заголовков** — фильтруемые пары ключ-значение заголовков
- **Необработанный вид** — полный HTTP-запрос/ответ в виде необработанного текста
- **Разобранный вид** — структурированное представление параметров запроса, тела, cookies
- **Значок статуса** — цветовой код статуса ответа (2xx зелёный, 4xx оранжевый, 5xx красный)
- **Повторить запрос** — кнопка для повторной отправки того же запроса
- **Копировать cURL** — копирование запроса в виде команды cURL
