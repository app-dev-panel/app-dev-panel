---
title: Коллектор запросов
description: "Коллектор запросов ADP собирает данные PSR-7 HTTP-запросов и ответов: метод, URL, заголовки, тело, статус."
---

# Коллектор запросов

Собирает данные входящего HTTP-запроса и ответа — метод, путь, заголовки, параметры запроса, код статуса и необработанные тела.

![Панель коллектора запросов](/images/collectors/request.png)

## Что собирает

| Поле | Описание |
|------|----------|
| `requestUrl` | Полный URL запроса |
| `requestPath` | Путь URL |
| `requestQuery` | Строка запроса |
| `requestMethod` | HTTP-метод (GET, POST и т.д.) |
| `requestIsAjax` | Является ли запрос AJAX/XHR |
| `userIp` | IP-адрес клиента |
| `responseStatusCode` | Код HTTP-статуса ответа |
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

**Сводка** (отображается в списке записей отладки):

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
<class>\AppDevPanel\Kernel\Collector\Web\RequestCollector</class> реализует <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class> и зависит от <class>\AppDevPanel\Kernel\Collector\TimelineCollector</class>. Находится в подпространстве имён `Web`.
:::

## Как это работает

Адаптеры фреймворков собирают PSR-7 запрос в начале цепочки мидлваров и ответ в конце. Коллектор сохраняет как разобранные объекты, так и необработанные HTTP-представления.

## Панель отладки

- **Вкладки Запрос/Ответ** — переключение между представлениями запроса и ответа
- **Таблица заголовков** — фильтруемые пары ключ-значение заголовков
- **Необработанный вид** — полный HTTP-запрос/ответ в виде текста
- **Разобранный вид** — структурированное представление параметров запроса, тела, cookies
- **Бейдж статуса** — цветовая индикация статуса ответа (2xx зелёный, 4xx оранжевый, 5xx красный)
- **Повторить запрос** — кнопка для повторной отправки того же запроса
- **Копировать cURL** — копирование запроса в формате cURL-команды
