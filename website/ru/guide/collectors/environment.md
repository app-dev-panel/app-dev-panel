---
title: Коллектор окружения
---

# Коллектор окружения

Собирает информацию о среде выполнения — версию PHP, расширения, данные ОС, ветку Git, параметры сервера и переменные окружения.

![Панель коллектора окружения](/images/collectors/environment.png)

## Что собирает

| Раздел | Поля |
|--------|------|
| **PHP** | версия, SAPI, путь к бинарному файлу, расширения, статус xdebug/opcache/pcov, настройки INI |
| **ОС** | семейство, название, uname, имя хоста |
| **Git** | ветка, хеш коммита (короткий и полный) |
| **Сервер** | параметры `$_SERVER` |
| **Окружение** | Переменные окружения |

## Схема данных

```json
{
    "php": {
        "version": "8.4.1",
        "sapi": "cli-server",
        "binary": "/usr/bin/php",
        "os": "Linux",
        "extensions": ["pdo", "mbstring", "json", "..."],
        "xdebug": false,
        "opcache": "8.4.1",
        "pcov": false,
        "ini": {
            "memory_limit": "256M",
            "max_execution_time": "30",
            "display_errors": "1",
            "error_reporting": 32767
        },
        "zend_extensions": ["Zend OPcache"]
    },
    "os": {
        "family": "Linux",
        "name": "Ubuntu 24.04",
        "uname": "Linux hostname 6.5.0 ...",
        "hostname": "app-server"
    },
    "git": {
        "branch": "main",
        "commit": "a1b2c3d",
        "commitFull": "a1b2c3d4e5f6..."
    },
    "server": {...},
    "env": {...}
}
```

**Сводка** (отображается в списке отладочных записей):

```json
{
    "environment": {
        "php": {"version": "8.4.1", "sapi": "cli-server"},
        "os": "Linux",
        "git": {"branch": "main", "commit": "a1b2c3d"}
    }
}
```

## Контракт

```php
use AppDevPanel\Kernel\Collector\EnvironmentCollector;

// Сбор из PSR-7 запроса
$collector->collectFromRequest(request: $serverRequest);

// Или сбор из глобальных переменных PHP
$collector->collectFromGlobals();
```

::: info
<class>\AppDevPanel\Kernel\Collector\EnvironmentCollector</class> реализует <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class>. Не имеет зависимостей от других коллекторов.
:::

## Как это работает

Коллектор считывает информацию о среде выполнения PHP через `phpversion()`, `php_sapi_name()`, `get_loaded_extensions()`, значения INI и `php_uname()`. Информация о Git получается через shell-команды (`git rev-parse`, `git branch`). Параметры сервера берутся из PSR-7 запроса или `$_SERVER`.

## Панель отладки

- **Информация о PHP** — версия, SAPI, расширения, настройки INI в структурированном виде
- **Информация об ОС** — семейство и версия операционной системы
- **Информация о Git** — текущая ветка и хеш коммита
- **Вкладки Сервер/Окружение** — фильтруемые таблицы ключ-значение для параметров сервера и переменных окружения
