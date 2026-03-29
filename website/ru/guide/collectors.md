---
title: Коллекторы
---

# Коллекторы

Коллекторы -- основной механизм сбора данных в ADP. Каждый коллектор реализует интерфейс `CollectorInterface` и отвечает за захват определённого типа данных во время выполнения приложения.

## Встроенные коллекторы

| Коллектор | Собираемые данные |
|-----------|-------------------|
| `LogCollector` | PSR-3 сообщения логов (уровень, текст, контекст) |
| `EventCollector` | PSR-14 отправленные события и слушатели |
| `HttpClientCollector` | PSR-18 исходящие HTTP-запросы и ответы |
| `DatabaseCollector` | SQL-запросы, время выполнения, транзакции |
| `ExceptionCollector` | Необработанные исключения со стектрейсами |
| `RequestCollector` | Входящие HTTP-запросы и ответы |
| `ServiceCollector` | Разрешение сервисов DI-контейнера |
| `AssetBundleCollector` | Зарегистрированные бандлы ассетов |
| `CommandCollector` | Выполнение консольных команд |
| `CacheCollector` | Операции кеша: get/set/delete |
| `MailerCollector` | Отправленные email-сообщения |
| `TimelineCollector` | События временной шкалы производительности |
| `TranslatorCollector` | Обращения к переводам, отсутствующие переводы |
| `ValidatorCollector` | Вызовы валидации и результаты |
| `EnvironmentCollector` | Информация об окружении PHP и ОС |

## CollectorInterface

Каждый коллектор реализует пять методов:

```php
interface CollectorInterface
{
    public function getId(): string;       // Уникальный ID (обычно FQCN)
    public function getName(): string;     // Короткое читаемое имя
    public function startup(): void;       // Вызывается в начале запроса
    public function shutdown(): void;      // Вызывается в конце запроса
    public function getCollected(): array; // Возвращает собранные данные
}
```

`Debugger` вызывает `startup()` на всех зарегистрированных коллекторах в начале запроса, а `shutdown()` и `getCollected()` -- в конце.

## Создание пользовательского коллектора

```php
<?php

declare(strict_types=1);

namespace App\Debug;

use AppDevPanel\Kernel\Collector\CollectorInterface;

final class MetricsCollector implements CollectorInterface
{
    private array $metrics = [];

    public function getId(): string
    {
        return self::class;
    }

    public function getName(): string
    {
        return 'metrics';
    }

    public function startup(): void
    {
        $this->metrics = [];
    }

    public function shutdown(): void
    {
        // Финализация данных при необходимости
    }

    public function getCollected(): array
    {
        return $this->metrics;
    }

    public function record(string $name, float $value): void
    {
        $this->metrics[] = ['name' => $name, 'value' => $value];
    }
}
```

Зарегистрируйте коллектор в DI-конфигурации адаптера вашего фреймворка, чтобы `Debugger` подхватил его автоматически.

## Поток данных

Коллекторы получают данные двумя способами:

1. **Через прокси** -- прокси PSR-интерфейсов (например, `LoggerInterfaceProxy`) перехватывают вызовы и автоматически передают данные соответствующему коллектору.
2. **Через прямые вызовы** -- хуки адаптера или код приложения вызывают методы коллектора напрямую (например, `DatabaseCollector` получает данные запросов через хуки базы данных фреймворка).

## SummaryCollectorInterface

Коллекторы также могут реализовать `SummaryCollectorInterface` для предоставления сводных данных, отображаемых в списке отладочных записей без загрузки полных данных коллектора.

## TranslatorCollector

Захватывает обращения к переводам во время выполнения запроса, включая обнаружение отсутствующих переводов. Реализует `SummaryCollectorInterface`.

Подробности на странице [Переводчик](/ru/guide/translator): поля TranslationRecord, структура собранных данных, логика определения отсутствующих переводов, интеграции proxy фреймворков и примеры конфигурации.
