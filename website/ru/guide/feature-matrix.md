---
title: Матрица возможностей
description: "Сравнение поддержки коллекторов и инспекторов в адаптерах ADP для Symfony, Laravel, Yii 3 и Yii 2."
---

# Матрица возможностей

ADP поддерживает несколько PHP-фреймворков через адаптеры. Каждый адаптер подключает хуки фреймворка к общим коллекторам Kernel. На этой странице описано, что доступно в каждом адаптере.

## Поддержка коллекторов по адаптерам

Все коллекторы находятся в Kernel и не зависят от фреймворка. Адаптеры регистрируют и подключают их через хуки событий, прокси или декораторы.

### Универсальные коллекторы

Эти коллекторы зарегистрированы во **всех четырёх адаптерах**:

| Коллектор | Панель | Описание |
|-----------|--------|----------|
| <class>AppDevPanel\Kernel\Collector\TimelineCollector</class> | Timeline | События временной шкалы производительности |
| <class>AppDevPanel\Kernel\Collector\LogCollector</class> | Logs | PSR-3 сообщения логов |
| <class>AppDevPanel\Kernel\Collector\EventCollector</class> | Events | PSR-14 отправленные события |
| <class>AppDevPanel\Kernel\Collector\ExceptionCollector</class> | Exceptions | Необработанные исключения со стек-трейсами |
| <class>AppDevPanel\Kernel\Collector\DeprecationCollector</class> | _(в Logs)_ | PHP deprecation-предупреждения |
| <class>AppDevPanel\Kernel\Collector\ServiceCollector</class> | Services | Разрешения сервисов DI-контейнера |
| <class>AppDevPanel\Kernel\Collector\HttpClientCollector</class> | HTTP Client | PSR-18 исходящие HTTP-запросы |
| <class>AppDevPanel\Kernel\Collector\VarDumperCollector</class> | Var Dumper | Вызовы `dump()` / `dd()` |
| <class>AppDevPanel\Kernel\Collector\EnvironmentCollector</class> | Environment | Информация о PHP и ОС |
| <class>AppDevPanel\Kernel\Collector\Stream\FilesystemStreamCollector</class> | Filesystem | Операции файловых потоков |
| <class>AppDevPanel\Kernel\Collector\Stream\HttpStreamCollector</class> | _(скрыт)_ | Сырые HTTP-потоки (подвид HTTP Client) |
| <class>AppDevPanel\Kernel\Collector\Web\RequestCollector</class> | Request | Входящие HTTP запрос/ответ (web-записи) |
| <class>AppDevPanel\Kernel\Collector\Console\CommandCollector</class> | Request | Детали консольной команды (console-записи) |
| <class>AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector</class> | _(мета)_ | Сводка web-приложения для списка записей |
| <class>AppDevPanel\Kernel\Collector\Console\ConsoleAppInfoCollector</class> | _(мета)_ | Сводка консольного приложения для списка записей |
| <class>AppDevPanel\Kernel\Collector\RouterCollector</class> | Router | Данные маршрутизации HTTP |
| <class>AppDevPanel\Kernel\Collector\ValidatorCollector</class> | Validator | Операции валидации и результаты |
| <class>AppDevPanel\Kernel\Collector\TranslatorCollector</class> | Translator | Поиск переводов, пропущенные переводы |
| <class>AppDevPanel\Kernel\Collector\AuthorizationCollector</class> | Security | Данные аутентификации и авторизации |
| <class>AppDevPanel\Kernel\Collector\OpenTelemetryCollector</class> | OpenTelemetry | OpenTelemetry span-ы и трейсы |

### Матрица доступности коллекторов

| Коллектор | Yii 3 | Symfony | Laravel | Yii2 | Панель |
|-----------|:-------:|:-------:|:-------:|:----:|--------|
| Database | ✅ | ✅ | ✅ | ✅ | Database |
| Cache | ✅ | ✅ | ✅ | ✅ | Cache |
| Mailer | ✅ | ✅ | ✅ | ✅ | Mailer |
| Queue | ✅ | ✅ | ✅ | ✅ | Queue |
| Redis | ✅ | ✅ | ✅ | ✅ | Redis |
| Elasticsearch | ✅ | ✅ | ✅ | ✅ | Elasticsearch |
| View | ✅ | — | — | ✅ | WebView |
| Templates | — | ✅ | ✅ | ✅ | Templates |
| Code Coverage | ✅ | ✅ | ✅ | ✅ | Coverage |
| Asset Bundles | ✅ | ✅ | ✅ | ✅ | Asset Bundles |
| Middleware | ✅ | — | — | — | Middleware |
| Messenger | — | ✅ | — | — | Messenger |

### Итого коллекторов по адаптерам

| Адаптер | Универсальные | Дополнительные | Всего |
|---------|:-------------:|:--------------:|:-----:|
| Yii 3 | 20 | 10 | **30** |
| Symfony | 20 | 10 | **30** |
| Yii2 | 20 | 10 | **30** |
| Laravel | 20 | 10 | **30** |

## Механизмы проксирования / перехвата

Каждый адаптер использует разные стратегии для перехвата внутренних механизмов фреймворка и передачи данных в коллекторы:

| Интерфейс | Yii 3 | Symfony | Laravel | Yii2 |
|-----------|---------|---------|---------|------|
| PSR-3 Logger | <class>AppDevPanel\Kernel\Collector\LoggerInterfaceProxy</class> | <class>AppDevPanel\Kernel\Collector\LoggerInterfaceProxy</class> | <class>AppDevPanel\Kernel\Collector\LoggerInterfaceProxy</class> | <class>AppDevPanel\Adapter\Yii2\Collector\DebugLogTarget</class> |
| PSR-14 Events | <class>AppDevPanel\Kernel\Collector\EventDispatcherInterfaceProxy</class> | <class>AppDevPanel\Adapter\Symfony\Proxy\SymfonyEventDispatcherProxy</class> | <class>AppDevPanel\Adapter\Laravel\Proxy\LaravelEventDispatcherProxy</class> | Wildcard `Event::on('*')` |
| PSR-18 HTTP Client | <class>AppDevPanel\Kernel\Collector\HttpClientInterfaceProxy</class> | <class>AppDevPanel\Kernel\Collector\HttpClientInterfaceProxy</class> | <class>AppDevPanel\Kernel\Collector\HttpClientInterfaceProxy</class> | <class>AppDevPanel\Kernel\Collector\HttpClientInterfaceProxy</class> |
| PSR-11 Container | <class>AppDevPanel\Adapter\Yii3\Proxy\ContainerInterfaceProxy</class> | Compiler pass | — | — |
| VarDumper | <class>AppDevPanel\Adapter\Yii3\Proxy\VarDumperHandlerInterfaceProxy</class> | Handler hook | Handler hook | Handler hook |
| Database | <class>AppDevPanel\Adapter\Yii3\Collector\Db\ConnectionInterfaceProxy</class> | DBAL middleware | Event listener | <class>AppDevPanel\Adapter\Yii2\Collector\DbProfilingTarget</class> |
| Mailer | <class>AppDevPanel\Adapter\Yii3\Collector\Mailer\MailerInterfaceProxy</class> | Event listener | Event listener | Event hook |
| Router | <class>AppDevPanel\Adapter\Yii3\Collector\Router\UrlMatcherInterfaceProxy</class> | — | <class>AppDevPanel\Adapter\Laravel\Collector\RouterDataExtractor</class> | <class>AppDevPanel\Adapter\Yii2\Proxy\UrlRuleProxy</class> |
| Validator | <class>AppDevPanel\Adapter\Yii3\Collector\Validator\ValidatorInterfaceProxy</class> | — | — | — |
| Queue | <class>AppDevPanel\Adapter\Yii3\Collector\Queue\QueueProviderInterfaceProxy</class> | — | Event listener | — |
| View/Templates | — | Twig profiler extension | <class>AppDevPanel\Adapter\Laravel\Collector\TemplateCollectorCompilerEngine</class> | `View::EVENT_AFTER_RENDER` |
| Cache | — | Decorated `CacheAdapter` | Event listener | — |
| Messenger | — | Messenger middleware | — | — |
| Asset Bundles | <class>AppDevPanel\Adapter\Yii3\Collector\Asset\AssetLoaderInterfaceProxy</class> | <class>AppDevPanel\Adapter\Symfony\EventSubscriber\AssetMapperSubscriber</class> | <class>AppDevPanel\Adapter\Laravel\EventListener\ViteAssetListener</class> | `View::EVENT_END_PAGE` |
| OpenTelemetry | <class>AppDevPanel\Kernel\Collector\SpanProcessorInterfaceProxy</class> | <class>AppDevPanel\Kernel\Collector\SpanProcessorInterfaceProxy</class> | <class>AppDevPanel\Kernel\Collector\SpanProcessorInterfaceProxy</class> | <class>AppDevPanel\Kernel\Collector\SpanProcessorInterfaceProxy</class> |
| Translator | <class>AppDevPanel\Adapter\Yii3\Collector\Translator\TranslatorInterfaceProxy</class> | <class>AppDevPanel\Adapter\Symfony\Proxy\SymfonyTranslatorProxy</class> | <class>AppDevPanel\Adapter\Laravel\Proxy\LaravelTranslatorProxy</class> | <class>AppDevPanel\Adapter\Yii2\Proxy\I18NProxy</class> |

## Возможности инспектора

Инспектор предоставляет интроспекцию приложения в реальном времени (не привязана к записям отладки). Все API-функции не зависят от адаптера:

| Возможность | Yii 3 | Symfony | Laravel | Yii2 | Cycle |
|-------------|:-------:|:-------:|:-------:|:----:|:-----:|
| Конфигурация | ✅ | ✅ | ✅ | ✅ | — |
| Схема БД | ✅ | ✅ | ✅ | ✅ | ✅ |
| Маршруты | ✅ | ✅ | ✅ | ✅ | — |
| Файловый проводник | ✅ | ✅ | ✅ | ✅ | — |
| Git | ✅ | ✅ | ✅ | ✅ | — |
| Composer | ✅ | ✅ | ✅ | ✅ | — |
| Opcache | ✅ | ✅ | ✅ | ✅ | — |
| PHP Info | ✅ | ✅ | ✅ | ✅ | — |
| Команды | ✅ | ✅ | ✅ | ✅ | — |
| Container / DI | ✅ | ✅ | ✅ | ✅ | — |
| Кеш | ✅ | ✅ | ✅ | ✅ | — |
| Переводы | ✅ | ✅ | ✅ | ✅ | — |

## Различия в возможностях адаптеров

Текущие различия между адаптерами:

| Возможность | Yii 3 | Symfony | Laravel | Yii2 |
|-------------|:-------:|:-------:|:-------:|:----:|
| Отладка шаблонов | ✅ | ✅ | ✅ | ✅ |
| Code coverage | ✅ | ✅ | ✅ | ✅ |
| Отладка asset bundles | ✅ | ✅ | ✅ | ✅ |
| Отладка middleware | ✅ | ❌ | ❌ | ❌ |
| Отладка шины сообщений | ❌ | ✅ | ❌ | ❌ |
| Прокси контейнера | ✅ | ❌ | ❌ | ❌ |

::: tip
Паритет возможностей активно улучшается. Если вам нужен определённый коллектор для вашего фреймворка, [участие в разработке приветствуется](/ru/guide/contributing).
:::
