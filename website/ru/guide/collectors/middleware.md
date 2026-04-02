---
title: Коллектор middleware
---

# Коллектор middleware

Захватывает выполнение стека HTTP middleware — фазы обработки до и после с временем выполнения и использованием памяти.

## Собираемые данные

| Поле | Описание |
|------|----------|
| `beforeStack` | Middleware, вызванные до обработчика действия |
| `actionHandler` | Основной обработчик действия/контроллера |
| `afterStack` | Middleware, вызванные после обработчика действия |

Каждая запись middleware содержит:

| Поле | Описание |
|------|----------|
| `name` | Имя класса middleware |
| `time` | Временная метка выполнения |
| `memory` | Использование памяти в этой точке |
| `request` | Состояние запроса (стек до) |
| `response` | Состояние ответа (стек после) |

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

Адаптеры фреймворков инструментируют конвейер middleware:
- **Yii 3**: <class>\AppDevPanel\Adapter\Yii3\Collector\Middleware\MiddlewareEventListener</class> слушает события middleware Yii
- **Symfony**: События ядра (`kernel.request`, `kernel.response`, `kernel.controller`)
- **Laravel**: Хуки конвейера middleware

## Панель отладки

- **Стек middleware** — визуальный конвейер до/после с обработчиком действия в середине
- **Время выполнения** — время выполнения каждого middleware
- **Отслеживание памяти** — изменение использования памяти по конвейеру
