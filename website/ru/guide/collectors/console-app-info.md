---
title: Коллектор информации о консольном приложении
---

# Коллектор информации о консольном приложении

Собирает метрики производительности консольного приложения — время обработки, использование памяти и имя адаптера. Консольный аналог [коллектора информации о веб-приложении](/ru/guide/collectors/web-app-info).

## Что собирает

| Поле | Описание |
|------|----------|
| `applicationProcessingTime` | Общее время обработки приложением |
| `requestProcessingTime` | Время выполнения команды |
| `applicationEmit` | Время вывода результата |
| `preloadTime` | Время предзагрузки/инициализации |
| `memoryPeakUsage` | Пиковое использование памяти в байтах |
| `memoryUsage` | Текущее использование памяти в байтах |
| `adapter` | Имя адаптера фреймворка |

## Схема данных

```json
{
    "applicationProcessingTime": 1.250,
    "requestProcessingTime": 1.200,
    "applicationEmit": 0.001,
    "preloadTime": 0.049,
    "memoryPeakUsage": 16777216,
    "memoryUsage": 12582912,
    "adapter": "symfony"
}
```

**Сводка** (отображается в списке отладочных записей):

```json
{
    "console": {
        "adapter": "symfony",
        "request": {
            "startTime": 1711878000.100,
            "processingTime": 1.200
        },
        "memory": {
            "peakUsage": 16777216
        }
    }
}
```

## Контракт

```php
use AppDevPanel\Kernel\Collector\Console\ConsoleAppInfoCollector;

$collector->markApplicationStarted();
// ... выполнение команды ...
$collector->markApplicationFinished();
```

::: info
<class>\AppDevPanel\Kernel\Collector\Console\ConsoleAppInfoCollector</class> реализует <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class> и зависит от <class>\AppDevPanel\Kernel\Collector\TimelineCollector</class>. Расположен в подпространстве имён `Console`.
:::

## Как это работает

Адаптеры фреймворков вызывают методы `mark*()` в ключевых точках жизненного цикла консольной команды. Метрики памяти снимаются через `memory_get_peak_usage()` и `memory_get_usage()`.

## Панель отладки

Метаданные консольной записи (время обработки, память) отображаются в заголовке отладочной записи, аналогично веб-записям.
