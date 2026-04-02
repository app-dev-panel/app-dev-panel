---
title: Коллектор кэша
---

# Коллектор кэша

Захватывает операции с кэшем (get, set, delete) с показателями попаданий/промахов, замерами времени и разбивкой по пулам.

![Панель коллектора кэша](/images/collectors/cache.png)

## Собираемые данные

| Поле | Описание |
|------|----------|
| `pool` | Имя пула кэша (например, `default`, `sessions`) |
| `operation` | Тип операции (`get`, `set`, `delete`, `has`, `clear`) |
| `key` | Ключ кэша |
| `hit` | Было ли попадание в кэш |
| `duration` | Время выполнения операции в секундах |
| `value` | Кэшированное значение (для операций get/set) |

## Схема данных

```json
{
    "operations": [
        {
            "pool": "default",
            "operation": "get",
            "key": "user:42",
            "hit": true,
            "duration": 0.0003,
            "value": {"name": "John"}
        }
    ],
    "hits": 8,
    "misses": 2,
    "totalOperations": 10
}
```

**Сводка** (отображается в списке отладочных записей):

```json
{
    "cache": {
        "hits": 8,
        "misses": 2,
        "totalOperations": 10
    }
}
```

## Контракт

```php
use AppDevPanel\Kernel\Collector\CacheCollector;
use AppDevPanel\Kernel\Collector\CacheOperationRecord;

$collector->logCacheOperation(new CacheOperationRecord(
    pool: 'default',
    operation: 'get',
    key: 'user:42',
    hit: true,
    duration: 0.0003,
    value: ['name' => 'John'],
));
```

::: info
<class>\AppDevPanel\Kernel\Collector\CacheCollector</class> реализует <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class> и зависит от <class>\AppDevPanel\Kernel\Collector\TimelineCollector</class>.
:::

## Как это работает

Адаптеры фреймворков перехватывают операции PSR-16 <class>Psr\SimpleCache\CacheInterface</class> через декоратор `CacheInterfaceProxy`. Каждый вызов `get()`, `set()`, `delete()`, `has()` и `clear()` автоматически захватывается.

## Панель отладки

- **Сводка по попаданиям** — общее количество операций, попаданий, промахов с процентным соотношением
- **Разбивка по пулам** — статистика по пулам кэша при использовании нескольких пулов
- **Список операций** — фильтруемый список с типом операции, ключом, статусом попадания/промаха и временем
- **Цветовая кодировка** — попадания (зелёный), промахи (оранжевый), удаления (жёлтый)
- **Предпросмотр значений** — раскрываемые кэшированные значения для операций get/set
