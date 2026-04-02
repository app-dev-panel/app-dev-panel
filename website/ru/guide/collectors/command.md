---
title: Коллектор команд
---

# Коллектор команд

Захватывает выполнение консольных команд — имя команды, ввод/вывод, аргументы, опции, код завершения и ошибки.

## Что собирает

| Поле | Описание |
|------|----------|
| `name` | Имя команды |
| `command` | Объект команды |
| `input` | Строка ввода команды |
| `output` | Вывод команды |
| `exitCode` | Код завершения процесса |
| `error` | Сообщение об ошибке в случае неудачи |
| `arguments` | Аргументы команды |
| `options` | Опции команды |

## Схема данных

```json
{
    "command": {
        "name": "app:import-users",
        "class": "App\\Command\\ImportUsersCommand",
        "input": "app:import-users --force",
        "output": "Imported 42 users.",
        "exitCode": 0,
        "error": null,
        "arguments": {},
        "options": {"force": true}
    }
}
```

**Сводка** (отображается в списке отладочных записей):

```json
{
    "command": {
        "name": "app:import-users",
        "class": "App\\Command\\ImportUsersCommand",
        "input": "app:import-users --force",
        "exitCode": 0
    }
}
```

## Контракт

```php
use AppDevPanel\Kernel\Collector\Console\CommandCollector;

// Сбор из событий Symfony Console
$collector->collect(event: $consoleEvent);

// Или сбор сырых данных команды
$collector->collectCommandData([
    'name' => 'app:import-users',
    'input' => 'app:import-users --force',
    'exitCode' => 0,
]);
```

::: info
<class>\AppDevPanel\Kernel\Collector\Console\CommandCollector</class> реализует <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class> и зависит от <class>\AppDevPanel\Kernel\Collector\TimelineCollector</class>. Расположен в подпространстве имён `Console`.
:::

## Как это работает

Адаптеры фреймворков подключаются к жизненному циклу событий консоли:
- **Symfony**: `ConsoleCommandEvent`, `ConsoleTerminateEvent`, `ConsoleErrorEvent`
- **Laravel**: события команд Artisan
- **Yii 3**: события консольного приложения

## Панель отладки

- **Детали команды** — имя, класс, ввод и код завершения
- **Захват вывода** — полный вывод команды
- **Отображение ошибок** — сообщение об ошибке и стектрейс для неудачных команд
