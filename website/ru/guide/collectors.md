---
title: Коллекторы
description: "Обзор коллекторов ADP -- основного механизма сбора логов, событий, запросов и других данных времени выполнения."
---

# Коллекторы

Коллекторы -- основной механизм сбора данных в ADP. Каждый коллектор реализует интерфейс <class>AppDevPanel\Kernel\Collector\CollectorInterface</class> и отвечает за захват определённого типа данных во время выполнения приложения.

## Встроенные коллекторы

### Основные коллекторы

| Коллектор | Собираемые данные |
|-----------|-------------------|
| <class>AppDevPanel\Kernel\Collector\LogCollector</class> | PSR-3 сообщения логов (уровень, текст, контекст) |
| <class>AppDevPanel\Kernel\Collector\EventCollector</class> | PSR-14 отправленные события и слушатели |
| <class>AppDevPanel\Kernel\Collector\ExceptionCollector</class> | Необработанные исключения со стектрейсами |
| <class>AppDevPanel\Kernel\Collector\HttpClientCollector</class> | PSR-18 исходящие HTTP-запросы и ответы |
| <class>AppDevPanel\Kernel\Collector\DatabaseCollector</class> | SQL-запросы, время выполнения, транзакции |
| <class>AppDevPanel\Kernel\Collector\ElasticsearchCollector</class> | Запросы к Elasticsearch, тайминг, количество hits |
| <class>AppDevPanel\Kernel\Collector\CacheCollector</class> | Операции кеша с отслеживанием hit/miss |
| <class>AppDevPanel\Kernel\Collector\RedisCollector</class> | Redis-команды с таймингом и отслеживанием ошибок |
| <class>AppDevPanel\Kernel\Collector\MailerCollector</class> | Отправленные email-сообщения |
| <class>AppDevPanel\Kernel\Collector\TranslatorCollector</class> | Обращения к переводам, отсутствующие переводы |
| <class>AppDevPanel\Kernel\Collector\QueueCollector</class> | Операции очереди сообщений (push, consume, fail) |
| <class>AppDevPanel\Kernel\Collector\ServiceCollector</class> | Разрешение сервисов DI-контейнера |
| <class>AppDevPanel\Kernel\Collector\RouterCollector</class> | Данные маршрутизации HTTP-запросов |
| <class>AppDevPanel\Kernel\Collector\MiddlewareCollector</class> | Выполнение и тайминг стека middleware |
| <class>AppDevPanel\Kernel\Collector\ValidatorCollector</class> | Операции валидации и результаты |
| <class>AppDevPanel\Kernel\Collector\AuthorizationCollector</class> | Аутентификация и авторизация |
| <class>AppDevPanel\Kernel\Collector\TemplateCollector</class> | Рендеринг шаблонов/представлений с таймингами, захватом вывода и детекцией дубликатов |
| <class>AppDevPanel\Kernel\Collector\VarDumperCollector</class> | Ручные вызовы `dump()` / `dd()` |
| <class>AppDevPanel\Kernel\Collector\TimelineCollector</class> | Кросс-коллекторная временная шкала производительности |
| <class>AppDevPanel\Kernel\Collector\EnvironmentCollector</class> | Информация об окружении PHP и ОС |
| <class>AppDevPanel\Kernel\Collector\DeprecationCollector</class> | Предупреждения PHP о депрекациях |
| <class>AppDevPanel\Kernel\Collector\OpenTelemetryCollector</class> | Спаны и трейсы OpenTelemetry |
| <class>AppDevPanel\Kernel\Collector\AssetBundleCollector</class> | Бандлы фронтенд-ассетов (Yii) |
| <class>AppDevPanel\Kernel\Collector\Stream\FilesystemStreamCollector</class> | Операции файловых потоков |
| <class>AppDevPanel\Kernel\Collector\Stream\HttpStreamCollector</class> | Операции HTTP-потоков |
| <class>AppDevPanel\Kernel\Collector\CodeCoverageCollector</class> | Построчное покрытие PHP-кода за запрос (требуется pcov или xdebug) |

### Веб-специфичные

| Коллектор | Собираемые данные |
|-----------|-------------------|
| <class>AppDevPanel\Kernel\Collector\Web\RequestCollector</class> | Входящие HTTP-запросы и ответы |
| <class>AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector</class> | Версия PHP, память, время выполнения |

### Консольные

| Коллектор | Собираемые данные |
|-----------|-------------------|
| <class>AppDevPanel\Kernel\Collector\Console\CommandCollector</class> | Выполнение консольных команд |
| <class>AppDevPanel\Kernel\Collector\Console\ConsoleAppInfoCollector</class> | Метаданные консольного приложения |

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

<class>AppDevPanel\Kernel\Debugger</class> вызывает `startup()` на всех зарегистрированных коллекторах в начале запроса, а `shutdown()` и `getCollected()` -- в конце.

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

Зарегистрируйте коллектор в DI-конфигурации адаптера вашего фреймворка, чтобы <class>AppDevPanel\Kernel\Debugger</class> подхватил его автоматически.

## Поток данных

Коллекторы получают данные двумя способами:

1. **Через прокси** -- прокси PSR-интерфейсов (например, <class>AppDevPanel\Kernel\Collector\LoggerInterfaceProxy</class>) перехватывают вызовы и автоматически передают данные соответствующему коллектору.
2. **Через прямые вызовы** -- хуки адаптера или код приложения вызывают методы коллектора напрямую (например, <class>AppDevPanel\Kernel\Collector\DatabaseCollector</class> получает данные запросов через хуки базы данных фреймворка).

## SummaryCollectorInterface

Коллекторы также могут реализовать <class>AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class> для предоставления сводных данных, отображаемых в списке отладочных записей без загрузки полных данных коллектора.

## TranslatorCollector

Захватывает обращения к переводам во время выполнения запроса, включая обнаружение отсутствующих переводов. Реализует <class>AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class>.

Подробности на странице [Переводчик](/ru/guide/translator): поля TranslationRecord, структура собранных данных, логика определения отсутствующих переводов, интеграции proxy фреймворков и примеры конфигурации.

## Code Coverage Collector

<class>AppDevPanel\Kernel\Collector\CodeCoverageCollector</class> собирает построчное покрытие PHP-кода за каждый HTTP-запрос, используя [pcov](https://github.com/krakjoe/pcov) или [xdebug](https://xdebug.org/) в качестве драйвера.

::: warning Предварительные требования
Необходимо расширение **pcov** (рекомендуется) или **xdebug** с включённым режимом coverage. Без них коллектор возвращает пустой результат с `driver: null`.
:::

### Как это работает

1. При `startup()` коллектор определяет доступный драйвер и запускает сбор покрытия
2. Код приложения выполняется в обычном режиме — каждая выполненная строка PHP отслеживается
3. При `shutdown()` сбор останавливается, и сырые данные обрабатываются в постатистику по файлам
4. Файлы, соответствующие `excludePaths` (по умолчанию: `vendor`), отфильтровываются

### Включение

Покрытие кода — **opt-in** (по умолчанию отключено) из-за влияния на производительность.

:::tabs key:framework
== Symfony
```yaml
# config/packages/app_dev_panel.yaml
app_dev_panel:
    collectors:
        code_coverage: true
```
== Laravel
```php
// config/app-dev-panel.php
'collectors' => [
    'code_coverage' => true,
],
```
== Yii 2
```php
// config/web.php — modules.app-dev-panel
'collectors' => [
    'code_coverage' => true,
],
```
:::

### Формат вывода

```json
{
    "driver": "pcov",
    "files": {
        "/app/src/Controller/HomeController.php": {
            "coveredLines": 12,
            "executableLines": 15,
            "percentage": 80.0
        }
    },
    "summary": {
        "totalFiles": 42,
        "coveredLines": 340,
        "executableLines": 500,
        "percentage": 68.0
    }
}
```

### Эндпоинт Inspector

Inspector также предоставляет эндпоинт `GET /inspect/api/coverage` для разового сбора покрытия. Подробнее — в разделе [Эндпоинты Inspector](/ru/api/inspector).
