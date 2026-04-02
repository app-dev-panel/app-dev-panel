---
title: Коллектор временной шкалы
---

# Коллектор временной шкалы

Собирает кросс-коллекторную временную шкалу производительности — единое представление всех событий от всех коллекторов в хронологическом порядке.

![Панель коллектора временной шкалы](/images/collectors/timeline.png)

## Что собирает

| Поле | Описание |
|------|----------|
| `time` | Временная метка события |
| `reference` | Идентификатор ссылки на запись данных исходного коллектора |
| `collector` | Имя класса исходного коллектора |
| `data` | Дополнительные данные события (зависят от коллектора) |

## Схема данных

События временной шкалы хранятся как массивы:

```json
[
    [1711878000.100, 0, "AppDevPanel\\Kernel\\Collector\\Web\\RequestCollector", []],
    [1711878000.105, 0, "AppDevPanel\\Kernel\\Collector\\LogCollector", ["level", "info"]],
    [1711878000.150, 0, "AppDevPanel\\Kernel\\Collector\\EventCollector", []],
    [1711878000.200, 1, "AppDevPanel\\Kernel\\Collector\\LogCollector", ["level", "warning"]]
]
```

**Сводка** (отображается в списке отладочных записей):

```json
{
    "timeline": {
        "total": 15
    }
}
```

## Контракт

```php
use AppDevPanel\Kernel\Collector\TimelineCollector;

// Called by other collectors to register timeline events
$timeline->collect(
    collector: $logCollector,
    reference: 0,           // Index in the source collector's data
    'level', 'info',        // Additional context data
);
```

::: info
<class>\AppDevPanel\Kernel\Collector\TimelineCollector</class> реализует <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class>. Большинство других коллекторов зависят от <class>\AppDevPanel\Kernel\Collector\TimelineCollector</class> для регистрации своих событий на временной шкале.
:::

## Как это работает

<class>\AppDevPanel\Kernel\Collector\TimelineCollector</class> является центральной точкой агрегации. Другие коллекторы (Log, Event, Database и т.д.) вызывают `$timeline->collect()` при записи события, передавая себя как источник. Это создаёт единое хронологическое представление по всем коллекторам.

## Панель отладки

- **Визуальная временная шкала** — горизонтальная столбчатая диаграмма, показывающая события во времени
- **Фильтрация по коллекторам** — переключение видимости отдельных коллекторов через чипы
- **Цветовая кодировка** — каждый тип коллектора имеет свой цвет
- **Масштаб времени** — автомасштабирующаяся ось времени с точностью до микросекунд
- **Количество событий** — общее число событий временной шкалы в бейдже боковой панели
