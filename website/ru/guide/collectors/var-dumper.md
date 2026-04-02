---
title: Коллектор VarDumper
---

# Коллектор VarDumper

Захватывает ручные дампы переменных (вызовы `dump()` / `dd()`) с информацией об исходном файле и строке.

![Панель коллектора VarDumper](/images/collectors/var-dumper.png)

## Собираемые данные

| Поле | Описание |
|------|----------|
| `variable` | Значение выведенной переменной |
| `line` | Исходный файл и строка вызова dump |

## Схема данных

```json
[
    {
        "variable": {"key": "value", "nested": [1, 2, 3]},
        "line": "/app/src/Controller.php:42"
    }
]
```

**Сводка** (отображается в списке отладочных записей):

```json
{
    "var-dumper": {
        "total": 2
    }
}
```

## Контракт

```php
use AppDevPanel\Kernel\Collector\VarDumperCollector;

$collector->collect(
    variable: ['key' => 'value'],
    line: '/app/src/Controller.php:42',
);
```

::: info
<class>\AppDevPanel\Kernel\Collector\VarDumperCollector</class> реализует <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class> и зависит от <class>\AppDevPanel\Kernel\Collector\TimelineCollector</class>.
:::

## Как это работает

Адаптеры фреймворков перехватывают функции `dump()` / `dd()` для захвата дампов переменных. Вместо вывода в браузер значения переменных захватываются и отправляются в коллектор с указанием местоположения в исходном коде.

## Панель отладки

- **Список переменных** — все выведенные переменные с указанием местоположения в исходном коде
- **Глубокая инспекция** — раскрываемый просмотрщик переменных с поддержкой вложенных объектов/массивов
- **Ссылки на файлы** — кликабельные пути к исходным файлам для интеграции с IDE
