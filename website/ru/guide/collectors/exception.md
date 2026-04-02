---
title: Коллектор исключений
---

# Коллектор исключений

Собирает необработанные исключения с полными стектрейсами и цепочками исключений (предыдущие исключения).

![Панель коллектора исключений](/images/collectors/exception.png)

## Что собирает

| Поле | Описание |
|------|----------|
| `class` | Имя класса исключения |
| `message` | Сообщение исключения |
| `file` | Файл, в котором было выброшено исключение |
| `line` | Номер строки |
| `code` | Код исключения |
| `trace` | Массив стектрейса |
| `traceAsString` | Стектрейс в виде отформатированной строки |

## Схема данных

Исключения сериализуются как массив (цепочка от внешнего к внутреннему):

```json
[
    {
        "class": "RuntimeException",
        "message": "Something went wrong",
        "file": "/app/src/Service.php",
        "line": 42,
        "code": 0,
        "trace": [...],
        "traceAsString": "#0 /app/src/Controller.php(15): ..."
    },
    {
        "class": "InvalidArgumentException",
        "message": "Original cause",
        "file": "/app/src/Validator.php",
        "line": 88,
        "code": 0,
        "trace": [...],
        "traceAsString": "..."
    }
]
```

**Сводка** (отображается в списке отладочных записей):

```json
{
    "exception": {
        "class": "RuntimeException",
        "message": "Something went wrong",
        "file": "/app/src/Service.php",
        "line": 42,
        "code": 0
    }
}
```

## Контракт

```php
use AppDevPanel\Kernel\Collector\ExceptionCollector;

$collector->collect(throwable: $exception);
```

::: info
<class>\AppDevPanel\Kernel\Collector\ExceptionCollector</class> реализует <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class> и зависит от <class>\AppDevPanel\Kernel\Collector\TimelineCollector</class>.
:::

## Как это работает

Адаптеры фреймворков подключаются к конвейеру обработки ошибок для захвата необработанных исключений. Коллектор обходит цепочку исключений через `getPrevious()` и сериализует каждое исключение в цепочке.

## Панель отладки

- **Заголовок исключения** — имя класса, сообщение и место выброса
- **Цепочка исключений** — предыдущие исключения отображаются в сворачиваемой цепочке
- **Подсветка синтаксиса исходного кода** — отображается файл вокруг строки выброса
- **Полный стектрейс** — раскрываемый, со ссылками на файлы для интеграции с IDE
