---
title: Коллектор Elasticsearch
---

# Коллектор Elasticsearch

Захватывает запросы к Elasticsearch — метод, эндпоинт, индекс, тело запроса/ответа, тайминг, количество совпадений и обнаружение дубликатов.

![Панель коллектора Elasticsearch](/images/collectors/elasticsearch.png)

## Собираемые данные

| Поле | Описание |
|------|----------|
| `method` | HTTP-метод (GET, POST, PUT, DELETE) |
| `endpoint` | Путь эндпоинта Elasticsearch |
| `index` | Имя целевого индекса |
| `body` | Тело запроса (JSON) |
| `status` | Статус запроса (`success` или `error`) |
| `statusCode` | HTTP-код статуса ответа |
| `responseBody` | Тело ответа |
| `responseSize` | Размер ответа в байтах |
| `hitsCount` | Количество найденных совпадений (для поисковых запросов) |
| `duration` | Длительность запроса в секундах |

## Схема данных

```json
{
    "requests": [
        {
            "method": "GET",
            "endpoint": "/users/_search",
            "index": "users",
            "body": "{\"query\": {\"match\": {\"name\": \"John\"}}}",
            "line": "/app/src/SearchService.php:42",
            "status": "success",
            "startTime": 1711878000.100,
            "endTime": 1711878000.150,
            "duration": 0.05,
            "statusCode": 200,
            "responseBody": "{\"hits\": {\"total\": 5, ...}}",
            "responseSize": 1024,
            "hitsCount": 5,
            "exception": null
        }
    ],
    "duplicates": {
        "groups": [],
        "totalDuplicatedCount": 0
    }
}
```

**Сводка** (отображается в списке отладочных записей):

```json
{
    "elasticsearch": {
        "total": 3,
        "errors": 0,
        "totalTime": 0.15,
        "duplicateGroups": 0,
        "totalDuplicatedCount": 0
    }
}
```

## Контракт

```php
use AppDevPanel\Kernel\Collector\ElasticsearchCollector;
use AppDevPanel\Kernel\Collector\ElasticsearchRequestRecord;

// Option A: start/end pattern
$collector->collectRequestStart(
    id: 'es-1',
    method: 'GET',
    endpoint: '/users/_search',
    body: '{"query": {"match": {"name": "John"}}}',
    line: '/app/src/SearchService.php:42',
);
$collector->collectRequestEnd(
    id: 'es-1',
    statusCode: 200,
    responseBody: '{"hits": {"total": 5}}',
    responseSize: 1024,
);

// Option B: single record
$collector->logRequest(new ElasticsearchRequestRecord(
    method: 'GET',
    endpoint: '/users/_search',
    index: 'users',
    body: '{"query": {"match": {"name": "John"}}}',
    duration: 0.05,
    statusCode: 200,
    hitsCount: 5,
    line: '/app/src/SearchService.php:42',
));
```

::: info
<class>\AppDevPanel\Kernel\Collector\ElasticsearchCollector</class> реализует <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class>, зависит от <class>\AppDevPanel\Kernel\Collector\TimelineCollector</class> и использует <class>\AppDevPanel\Kernel\Collector\DuplicateDetectionTrait</class>.
:::

Подробности настройки и интеграции см. на странице [Elasticsearch](/ru/guide/elasticsearch).

## Панель отладки

- **Список запросов** — все ES-запросы с методом, эндпоинтом, статусом и таймингом
- **Количество совпадений** — число результатов поиска для поисковых запросов
- **Обнаружение дубликатов** — подсветка повторяющихся идентичных запросов
- **Отслеживание ошибок** — неудачные запросы выделены с деталями ошибки
