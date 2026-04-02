---
title: Коллектор HTTP-потоков
---

# Коллектор HTTP-потоков

Захватывает операции HTTP/HTTPS stream wrapper — запросы, выполненные через `file_get_contents('http://...')`, `fopen('https://...')` и аналогичные PHP-функции потоков.

![Панель коллектора HTTP-потоков](/images/collectors/http-stream.png)

## Что собирает

| Поле | Описание |
|------|----------|
| `operation` | Тип операции потока (`open`, `read`, `stat` и т.д.) |
| `uri` | HTTP/HTTPS URL, к которому выполнен доступ |
| `args` | Аргументы операции |

## Схема данных

Операции сгруппированы по типу:

```json
{
    "open": [
        {"uri": "https://api.example.com/data", "args": {"mode": "r"}}
    ]
}
```

**Сводка** (отображается в списке отладочных записей):

```json
{
    "http_stream": {
        "open": 2,
        "read": 2
    }
}
```

## Контракт

```php
use AppDevPanel\Kernel\Collector\Stream\HttpStreamCollector;

$collector->collect(
    operation: 'open',
    path: 'https://api.example.com/data',
    args: ['mode' => 'r'],
);
```

::: info
<class>\AppDevPanel\Kernel\Collector\Stream\HttpStreamCollector</class> реализует <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class>. Поддерживает настраиваемые шаблоны исключений.
:::

## Как это работает

Коллектор использует прокси PHP stream wrapper (<class>\AppDevPanel\Kernel\Collector\Stream\HttpStreamProxy</class>), который регистрируется для протоколов `http://` и `https://`. Операции потоков через нативные PHP-функции перехватываются. Пути, соответствующие шаблонам `excludePaths`, игнорируются.

::: warning
Этот коллектор захватывает только HTTP-запросы, выполненные через PHP-функции потоков (`file_get_contents`, `fopen`). Для вызовов PSR-18 HTTP-клиента используйте [коллектор HTTP-клиента](/ru/guide/collectors/http-client).
:::

## Панель отладки

- **Список операций** — операции HTTP-потоков с URL-адресами
- **Совмещение с файловой системой** — отображается вместе с <class>\AppDevPanel\Kernel\Collector\Stream\FilesystemStreamCollector</class> под пунктом "I/O" в боковой панели
