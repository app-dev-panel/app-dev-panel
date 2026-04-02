---
title: Коллектор файловых потоков
---

# Коллектор файловых потоков

Захватывает операции файловой системы (`file://`) через прокси обёртки PHP-потоков — чтение, запись, получение информации и операции с директориями.

![Панель коллектора файловых потоков](/images/collectors/filesystem-stream.png)

## Собираемые данные

| Поле | Описание |
|------|----------|
| `operation` | Тип операции (`open`, `read`, `write`, `stat`, `unlink`, `mkdir` и др.) |
| `path` | Путь к файлу |
| `args` | Аргументы операции |

## Схема данных

Операции сгруппированы по типу:

```json
{
    "open": [
        {"path": "/app/config/app.php", "args": {"mode": "r"}},
        {"path": "/app/var/cache/data.json", "args": {"mode": "w"}}
    ],
    "stat": [
        {"path": "/app/public/index.php", "args": {}}
    ]
}
```

**Сводка** (отображается в списке отладочных записей):

```json
{
    "fs_stream": {
        "open": 15,
        "read": 42,
        "stat": 8,
        "write": 3
    }
}
```

## Контракт

```php
use AppDevPanel\Kernel\Collector\Stream\FilesystemStreamCollector;

$collector->collect(
    operation: 'open',
    path: '/app/config/app.php',
    args: ['mode' => 'r'],
);
```

::: info
<class>\AppDevPanel\Kernel\Collector\Stream\FilesystemStreamCollector</class> реализует <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class>. Поддерживает настраиваемые шаблоны игнорирования для исключения путей (например, директория vendor).
:::

## Как это работает

Коллектор использует прокси обёртки PHP-потоков (<class>\AppDevPanel\Kernel\Collector\Stream\FilesystemStreamProxy</class>), которая регистрируется для протокола `file://`. Все операции файловой системы (`fopen`, `file_get_contents`, `is_file`, `mkdir` и др.) перехватываются через механизм обёрток потоков PHP. Пути, соответствующие шаблонам `excludePaths`, игнорируются.

## Панель отладки

- **Группы операций** — операции файловой системы, сгруппированные по типу
- **Список путей к файлам** — все доступные пути с деталями операций
- **Счётчики операций** — сводка операций по типу в значке боковой панели (I/O)
