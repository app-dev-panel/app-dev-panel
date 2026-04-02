---
title: Коллектор базы данных
---

# Коллектор базы данных

Захватывает SQL-запросы, параметры, время выполнения, транзакции и обнаружение дублирующихся запросов.

![Панель коллектора базы данных](/images/collectors/database.png)

## Собираемые данные

| Поле | Описание |
|------|----------|
| `sql` | SQL-запрос с плейсхолдерами параметров |
| `rawSql` | SQL-запрос с подставленными параметрами |
| `params` | Массив привязанных параметров |
| `line` | Файл и строка исходного кода вызова запроса |
| `status` | Статус запроса (`success` или `error`) |
| `rowsNumber` | Количество затронутых/возвращённых строк |
| `exception` | Исключение в случае ошибки запроса |
| `transactionId` | Идентификатор связанной транзакции |

## Схема данных

```json
{
    "queries": [
        {
            "position": 0,
            "transactionId": null,
            "sql": "SELECT * FROM users WHERE id = :id",
            "rawSql": "SELECT * FROM users WHERE id = 42",
            "params": {":id": 42},
            "line": "/app/src/UserRepository.php:35",
            "status": "success",
            "rowsNumber": 1,
            "exception": null,
            "actions": []
        }
    ],
    "transactions": {},
    "duplicates": {
        "groups": [],
        "totalDuplicatedCount": 0
    }
}
```

**Сводка** (отображается в списке отладочных записей):

```json
{
    "db": {
        "queries": {"error": 0, "total": 3},
        "transactions": {"error": 0, "total": 1},
        "duplicateGroups": 0,
        "totalDuplicatedCount": 0
    }
}
```

## Контракт

### Жизненный цикл запроса

```php
use AppDevPanel\Kernel\Collector\DatabaseCollector;

// Option A: start/end pattern
$collector->collectQueryStart(
    id: 'query-1',
    sql: 'SELECT * FROM users WHERE id = :id',
    rawSql: 'SELECT * FROM users WHERE id = 42',
    params: [':id' => 42],
    line: '/app/src/UserRepository.php:35',
);
$collector->collectQueryEnd(id: 'query-1', rowsNumber: 1);

// Option B: single record
use AppDevPanel\Kernel\Collector\QueryRecord;

$collector->logQuery(new QueryRecord(
    sql: 'SELECT * FROM users WHERE id = :id',
    rawSql: 'SELECT * FROM users WHERE id = 42',
    params: [':id' => 42],
    duration: 0.0023,
    line: '/app/src/UserRepository.php:35',
));
```

### Транзакции

```php
$collector->collectTransactionStart(isolationLevel: 'READ COMMITTED', line: '...');
$collector->collectTransactionEnd(status: 'commit', line: '...');
```

::: info
<class>\AppDevPanel\Kernel\Collector\DatabaseCollector</class> реализует <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class>, зависит от <class>\AppDevPanel\Kernel\Collector\TimelineCollector</class> и использует <class>\AppDevPanel\Kernel\Collector\DuplicateDetectionTrait</class> для обнаружения повторяющихся запросов.
:::

## Как это работает

Адаптеры фреймворков перехватывают операции с базой данных через специфичные для фреймворка хуки:
- **Symfony**: middleware Doctrine DBAL
- **Laravel**: слушатель DB-запросов
- **Yii 2**: лог-таргет для сообщений профилирования БД
- **Yii 3**: слушатели событий запросов

## Панель отладки

- **Количество запросов и общее время** — заголовок сводки с агрегированной статистикой
- **Подсветка синтаксиса SQL** — запросы отображаются с цветовым выделением ключевых слов
- **Бейджи типов запросов** — SELECT, INSERT, UPDATE, DELETE с цветовой кодировкой
- **Количество строк и время** — метрики выполнения для каждого запроса
- **План выполнения (Explain)** — визуальное дерево EXPLAIN для SELECT-запросов
- **Обнаружение дубликатов** — подсветка повторяющихся идентичных запросов
- **Группировка по транзакциям** — запросы сгруппированы по границам транзакций
