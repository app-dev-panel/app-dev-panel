---
title: Инспектор базы данных
---

# Инспектор базы данных

Просмотр таблиц базы данных, схемы и записей, выполнение SQL-запросов и анализ планов запросов.

![Инспектор базы данных — Список таблиц](/images/inspector/database.png)

## Обозреватель таблиц

Список всех таблиц базы данных с количеством столбцов и записей. Нажмите **View**, чтобы увидеть схему таблицы и записи.

![Инспектор базы данных — Записи таблицы](/images/inspector/database-table.png)

## Что отображается

| Функция | Описание |
|---------|----------|
| Список таблиц | Все таблицы с количеством столбцов/записей |
| Схема таблицы | Имена столбцов, типы, значения по умолчанию, допустимость NULL |
| Записи | Пагинированные строки данных (по умолчанию 50, максимум 1000) |
| SQL-запрос | Выполнение произвольного SQL к базе данных |
| EXPLAIN | Анализ планов выполнения запросов (с опциональным `ANALYZE`) |

## Исполнитель SQL-запросов

Выполняйте любой SQL-запрос прямо из панели. Поддерживаются параметризованные запросы для безопасности.

## Планы EXPLAIN

Выполняйте `EXPLAIN` или `EXPLAIN ANALYZE` для запросов, чтобы увидеть планы выполнения — полезно для отладки медленных запросов.

## Эндпоинты API

| Метод | Путь | Описание |
|-------|------|----------|
| GET | `/inspect/api/table` | Список всех таблиц |
| GET | `/inspect/api/table/{name}?limit=50&offset=0` | Схема таблицы + пагинированные записи |
| POST | `/inspect/api/table/explain` | EXPLAIN SQL-запроса |
| POST | `/inspect/api/table/query` | Выполнение произвольного SQL-запроса |

**Тело запроса EXPLAIN:**
```json
{
    "sql": "SELECT * FROM users WHERE id = ?",
    "params": [1],
    "analyze": true
}
```

## Поддержка адаптеров

| Адаптер | Провайдер |
|---------|-----------|
| Symfony | <class>AppDevPanel\Adapter\Symfony\Inspector\DoctrineSchemaProvider</class> (Doctrine DBAL) |
| Laravel | <class>AppDevPanel\Adapter\Laravel\Inspector\LaravelSchemaProvider</class> (Eloquent) |
| Yii 2 | `Yii2DbSchemaProvider` |
| Cycle ORM | <class>AppDevPanel\Adapter\Cycle\Inspector\CycleSchemaProvider</class> |

::: warning
SQL-запросы выполняются к рабочей базе данных. Используйте с осторожностью в продакшен-окружениях.
:::
