---
title: Коллектор кеша
---

# Коллектор кеша

Собирает операции кеша (get, set, delete) с показателями hit/miss, таймингом и разбивкой по пулам.

![Панель коллектора кеша](/images/collectors/cache.png)

## Что собирает

| Поле | Описание |
|------|----------|
| `pool` | Имя пула кеша (например, `default`, `sessions`) |
| `operation` | Тип операции (`get`, `set`, `delete`, `has`, `clear`) |
| `key` | Ключ кеша |
| `hit` | Было ли попадание в кеш |
| `duration` | Время выполнения операции в секундах |
| `value` | Кешированное значение (для операций get/set) |

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

- **Сводка по hit rate** — общее количество операций, попаданий, промахов с процентным соотношением
- **Разбивка по пулам** — статистика, сгруппированная по пулу кеша, при использовании нескольких пулов
- **Список операций** — фильтруемый список с типом операции, ключом, статусом hit/miss и таймингом
- **Цветовая кодировка** — попадания (зелёный), промахи (оранжевый), удаления (жёлтый)
- **Предпросмотр значений** — раскрываемые кешированные значения для операций get/set
