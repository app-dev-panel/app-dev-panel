---
title: Коллектор мидлваров
---

# Коллектор мидлваров

Захватывает выполнение стека HTTP-мидлваров — фазы обработки до и после обработчика с таймингом и использованием памяти.

## Что собирает

| Поле | Описание |
|------|----------|
| `beforeStack` | Мидлвары, вызванные до обработчика действия |
| `actionHandler` | Основной обработчик действия/контроллера |
| `afterStack` | Мидлвары, вызванные после обработчика действия |

Каждая запись мидлвара содержит:

| Поле | Описание |
|------|----------|
| `name` | Имя класса мидлвара |
| `time` | Временная метка выполнения |
| `memory` | Использование памяти в этой точке |
| `request` | Состояние запроса (стек before) |
| `response` | Состояние ответа (стек after) |

## Схема данных

```json
{
    "beforeStack": [
        {"name": "App\\Middleware\\AuthMiddleware", "time": 1711878000.100, "memory": 2097152, "request": "..."}
    ],
    "actionHandler": {
        "name": "App\\Controller\\UserController::index",
        "startTime": 1711878000.105,
        "request": "...",
        "response": "...",
        "endTime": 1711878000.120,
        "memory": 4194304
    },
    "afterStack": [
        {"name": "App\\Middleware\\CorsMiddleware", "time": 1711878000.121, "memory": 4194304, "response": "..."}
    ]
}
```

**Сводка** (отображается в списке отладочных записей):

```json
{
    "middleware": {
        "total": 5
    }
}
```

## Контракт

```php
use AppDevPanel\Kernel\Collector\MiddlewareCollector;

$collector->collectBefore(
    name: 'App\\Middleware\\AuthMiddleware',
    time: microtime(true),
    memory: memory_get_usage(),
    request: $request,
);

$collector->collectAfter(
    name: 'App\\Middleware\\CorsMiddleware',
    time: microtime(true),
    memory: memory_get_usage(),
    response: $response,
);
```

::: info
<class>\AppDevPanel\Kernel\Collector\MiddlewareCollector</class> реализует <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class> и зависит от <class>\AppDevPanel\Kernel\Collector\TimelineCollector</class>.
:::

## Как это работает

Адаптеры фреймворков инструментируют конвейер мидлваров:
- **Yii 3**: <class>\AppDevPanel\Adapter\Yii3\Collector\Middleware\MiddlewareEventListener</class> слушает события мидлваров Yii
- **Symfony**: события ядра (`kernel.request`, `kernel.response`, `kernel.controller`)
- **Laravel**: хуки конвейера мидлваров

## Панель отладки

- **Стек мидлваров** — визуальный конвейер before/after с обработчиком действия в центре
- **Тайминг** — время выполнения каждого мидлвара
- **Отслеживание памяти** — дельта использования памяти по конвейеру
