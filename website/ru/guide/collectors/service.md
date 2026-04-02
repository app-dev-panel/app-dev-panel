---
title: Коллектор сервисов
---

# Коллектор сервисов

Захватывает вызовы методов сервисов DI-контейнера — вызванный сервис, метод, аргументы, результат и время выполнения.

![Панель коллектора сервисов](/images/collectors/service.png)

## Собираемые данные

| Поле | Описание |
|------|----------|
| `service` | Идентификатор сервиса |
| `class` | Имя класса сервиса |
| `method` | Вызванный метод |
| `arguments` | Аргументы метода |
| `result` | Возвращаемое значение |
| `status` | Статус вызова (`success` или `error`) |
| `error` | Сообщение об ошибке при неудаче |
| `timeStart` | Время начала вызова |
| `timeEnd` | Время завершения вызова |

## Схема данных

```json
[
    {
        "service": "App\\Service\\UserService",
        "class": "App\\Service\\UserService",
        "method": "findById",
        "arguments": [42],
        "result": {"id": 42, "name": "John"},
        "status": "success",
        "error": null,
        "timeStart": 1711878000.100,
        "timeEnd": 1711878000.105
    }
]
```

**Сводка** (отображается в списке отладочных записей):

```json
{
    "service": {
        "total": 5
    }
}
```

## Контракт

```php
use AppDevPanel\Kernel\Collector\ServiceCollector;
use AppDevPanel\Kernel\Event\MethodCallRecord;

$collector->collect(new MethodCallRecord(
    service: 'App\\Service\\UserService',
    class: 'App\\Service\\UserService',
    method: 'findById',
    arguments: [42],
    result: $result,
    status: 'success',
    timeStart: $start,
    timeEnd: $end,
));
```

::: info
<class>\AppDevPanel\Kernel\Collector\ServiceCollector</class> реализует <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class> и зависит от <class>\AppDevPanel\Kernel\Collector\TimelineCollector</class>.
:::

## Как это работает

Коллектор получает данные от <class>\AppDevPanel\Adapter\Yii3\Proxy\ContainerInterfaceProxy</class>, который оборачивает PSR-11 <class>Psr\Container\ContainerInterface</class>. Когда сервисы разрешаются и их методы вызываются через прокси, вызовы перехватываются и записываются.

## Панель отладки

- **Список вызовов сервисов** — все отслеживаемые вызовы методов с классом, методом и временем
- **Раскрываемые детали** — аргументы и возвращаемые значения
- **Индикаторы статуса** — значки успеха (зелёный) и ошибки (красный)
