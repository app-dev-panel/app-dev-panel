---
title: Коллектор информации о веб-приложении
---

# Коллектор информации о веб-приложении

Собирает метрики производительности веб-приложения — время обработки запроса, время предзагрузки, время отправки ответа, использование памяти и имя адаптера.

![Панель коллектора информации о веб-приложении](/images/collectors/web-app-info.png)

## Что собирает

| Поле | Описание |
|------|----------|
| `applicationProcessingTime` | Общее время обработки приложением |
| `requestProcessingTime` | Время обработки запроса |
| `applicationEmit` | Время отправки ответа |
| `preloadTime` | Время предзагрузки/инициализации |
| `memoryPeakUsage` | Пиковое использование памяти в байтах |
| `memoryUsage` | Текущее использование памяти в байтах |
| `adapter` | Имя адаптера фреймворка |

## Схема данных

```json
{
    "applicationProcessingTime": 0.045,
    "requestProcessingTime": 0.032,
    "applicationEmit": 0.001,
    "preloadTime": 0.012,
    "memoryPeakUsage": 8388608,
    "memoryUsage": 6291456,
    "adapter": "symfony"
}
```

**Сводка** (отображается в списке отладочных записей):

```json
{
    "web": {
        "adapter": "symfony",
        "request": {
            "startTime": 1711878000.100,
            "processingTime": 0.032
        },
        "memory": {
            "peakUsage": 8388608
        }
    }
}
```

## Контракт

```php
use AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector;

$collector->markApplicationStarted();
$collector->markRequestStarted();
// ... обработка запроса ...
$collector->markRequestFinished();
$collector->markApplicationFinished();
```

::: info
<class>\AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector</class> реализует <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class> и зависит от <class>\AppDevPanel\Kernel\Collector\TimelineCollector</class>. Расположен в подпространстве имён `Web`.
:::

## Как это работает

Адаптеры фреймворков вызывают методы `mark*()` в ключевых точках жизненного цикла запроса — загрузка приложения, начало запроса, конец запроса и отправка ответа. Метрики памяти снимаются через `memory_get_peak_usage()` и `memory_get_usage()`.

## Панель отладки

Данные WebAppInfo отображаются в **верхней панели** каждой отладочной записи как время обработки и использование памяти, а не в виде отдельной панели.
