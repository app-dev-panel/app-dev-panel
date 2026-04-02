---
title: Коллектор событий
---

# Коллектор событий

Собирает PSR-14 отправленные события с таймингом, метаданными слушателей и местом вызова.

![Панель коллектора событий](/images/collectors/event.png)

## Что собирает

| Поле | Описание |
|------|----------|
| `name` | Имя класса события |
| `event` | Сериализованный объект события |
| `file` | Исходный файл вызова dispatch |
| `line` | Строка вызова dispatch |
| `time` | Временная метка отправки события |

## Схема данных

```json
[
    {
        "name": "App\\Event\\UserRegistered",
        "event": "object@App\\Event\\UserRegistered#12",
        "file": "/app/src/UserService.php",
        "line": "42",
        "time": 1711878000.456
    }
]
```

**Сводка** (отображается в списке отладочных записей):

```json
{
    "event": {
        "total": 8
    }
}
```

## Контракт

```php
use AppDevPanel\Kernel\Collector\EventCollector;

$collector->collect(
    event: $event,     // The dispatched event object
    line: '/app/src/UserService.php:42',
);
```

::: info
<class>\AppDevPanel\Kernel\Collector\EventCollector</class> реализует <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class> и зависит от <class>\AppDevPanel\Kernel\Collector\TimelineCollector</class> для интеграции с кросс-коллекторной временной шкалой.
:::

## Как это работает

Коллектор получает данные от <class>\AppDevPanel\Kernel\Collector\EventDispatcherInterfaceProxy</class> — декоратора PSR-14 <class>Psr\EventDispatcher\EventDispatcherInterface</class>. Каждый вызов `$dispatcher->dispatch($event)` перехватывается, замеряется и передаётся коллектору.

Адаптеры фреймворков регистрируют прокси автоматически.

## Панель отладки

- **Бейджи типов событий** — цветовая кодировка по категории события (запрос, ответ, контроллер и т.д.)
- **Хронологический список** — события отображаются в порядке отправки с временными метками
- **Раскрываемые детали** — клик для просмотра полного объекта события и цепочки слушателей
- **Количество событий** — общее число событий отображается в бейдже боковой панели
