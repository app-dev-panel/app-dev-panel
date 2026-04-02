---
title: Коллектор потоков файловой системы
---

# Коллектор потоков файловой системы

Захватывает операции потоков файловой системы (`file://`) через прокси PHP stream wrapper — чтение, запись, получение статистики и операции с директориями.

![Панель коллектора потоков файловой системы](/images/collectors/filesystem-stream.png)

## Что собирает

| Поле | Описание |
|------|----------|
| `operation` | Тип операции (`open`, `read`, `write`, `stat`, `unlink`, `mkdir` и т.д.) |
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
<class>\AppDevPanel\Kernel\Collector\Stream\FilesystemStreamCollector</class> реализует <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class>. Поддерживает настраиваемые шаблоны исключений для пропуска путей (например, директория vendor).
:::

## Как это работает

Коллектор использует прокси PHP stream wrapper (<class>\AppDevPanel\Kernel\Collector\Stream\FilesystemStreamProxy</class>), который регистрируется для протокола `file://`. Все операции файловой системы (`fopen`, `file_get_contents`, `is_file`, `mkdir` и т.д.) перехватываются через механизм stream wrapper PHP. Пути, соответствующие шаблонам `excludePaths`, игнорируются.

## Панель отладки

- **Группы операций** — операции файловой системы, сгруппированные по типу
- **Список путей к файлам** — все файлы, к которым был доступ, с деталями операций
- **Счётчики операций** — сводка операций по типу в боковом бейдже (I/O)
