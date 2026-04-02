---
title: Коллектор OpenTelemetry
---

# Коллектор OpenTelemetry

Захватывает спаны и трейсы OpenTelemetry — данные распределённой трассировки с подсчётом ошибок и метаданными спанов.

![Панель коллектора OpenTelemetry](/images/collectors/opentelemetry.png)

## Что собирает

| Поле | Описание |
|------|----------|
| `spans` | Массив собранных спанов |
| `traceCount` | Количество уникальных трейсов |
| `spanCount` | Общее количество спанов |
| `errorCount` | Количество спанов с ошибками |

## Схема данных

```json
{
    "spans": [
        {
            "traceId": "abc123...",
            "spanId": "def456...",
            "parentSpanId": null,
            "name": "HTTP GET /users",
            "kind": "SERVER",
            "startTime": 1711878000100,
            "endTime": 1711878000350,
            "status": "OK",
            "attributes": {"http.method": "GET", "http.url": "/users"},
            "events": []
        }
    ],
    "traceCount": 1,
    "spanCount": 5,
    "errorCount": 0
}
```

**Сводка** (отображается в списке отладочных записей):

```json
{
    "opentelemetry": {
        "spans": 5,
        "traces": 1,
        "errors": 0
    }
}
```

## Контракт

```php
use AppDevPanel\Kernel\Collector\OpenTelemetryCollector;
use AppDevPanel\Kernel\Collector\SpanRecord;

$collector->collect(new SpanRecord(
    traceId: 'abc123...',
    spanId: 'def456...',
    name: 'HTTP GET /users',
    kind: 'SERVER',
    startTime: 1711878000100,
    endTime: 1711878000350,
    status: 'OK',
    attributes: ['http.method' => 'GET'],
));

// Или пакетный сбор
$collector->collectBatch([$span1, $span2, $span3]);
```

::: info
<class>\AppDevPanel\Kernel\Collector\OpenTelemetryCollector</class> реализует <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class> и зависит от <class>\AppDevPanel\Kernel\Collector\TimelineCollector</class>.
:::

## Как это работает

Коллектор получает спаны от адаптера OpenTelemetry `SpanExporter`. Когда приложение использует OpenTelemetry SDK для трассировки, спаны экспортируются в этот коллектор вместо (или в дополнение к) внешних бэкендов, таких как Jaeger или Zipkin.

## Панель отладки

- **Просмотр трейсов** — спаны, сгруппированные по trace ID
- **Временная шкала спанов** — визуальная временная шкала длительности спанов
- **Подсветка ошибок** — спаны с ошибками выделены красным
- **Инспекция атрибутов** — раскрываемые атрибуты и события спанов
