---
title: Матрица возможностей
---

# Матрица возможностей

ADP поддерживает несколько PHP-фреймворков через адаптеры. Каждый адаптер подключает хуки фреймворка к общим коллекторам Kernel. На этой странице описано, что доступно в каждом адаптере.

## Поддержка коллекторов по адаптерам

Все коллекторы находятся в Kernel и не зависят от фреймворка. Адаптеры регистрируют и подключают их через хуки событий, прокси или декораторы.

### Универсальные коллекторы

Эти коллекторы зарегистрированы во **всех четырёх адаптерах**:

| Коллектор | Панель | Описание |
|-----------|--------|----------|
| `TimelineCollector` | Timeline | События временной шкалы производительности |
| `LogCollector` | Logs | PSR-3 сообщения логов |
| `EventCollector` | Events | PSR-14 отправленные события |
| `ExceptionCollector` | Exceptions | Необработанные исключения со стек-трейсами |
| `DeprecationCollector` | _(в Logs)_ | PHP deprecation-предупреждения |
| `ServiceCollector` | Services | Разрешения сервисов DI-контейнера |
| `HttpClientCollector` | HTTP Client | PSR-18 исходящие HTTP-запросы |
| `VarDumperCollector` | Var Dumper | Вызовы `dump()` / `dd()` |
| `EnvironmentCollector` | Environment | Информация о PHP и ОС |
| `FilesystemStreamCollector` | Filesystem | Операции файловых потоков |
| `HttpStreamCollector` | _(скрыт)_ | Сырые HTTP-потоки (подвид HTTP Client) |
| `RequestCollector` | Request | Входящие HTTP запрос/ответ (web-записи) |
| `CommandCollector` | Request | Детали консольной команды (console-записи) |
| `WebAppInfoCollector` | _(мета)_ | Сводка web-приложения для списка записей |
| `ConsoleAppInfoCollector` | _(мета)_ | Сводка консольного приложения для списка записей |
| `RouterCollector` | Router | Данные маршрутизации HTTP |
| `ValidatorCollector` | Validator | Операции валидации и результаты |
| `TranslatorCollector` | Translator | Поиск переводов, пропущенные переводы |
| `AuthorizationCollector` | Security | Данные аутентификации и авторизации |
| `OpenTelemetryCollector` | OpenTelemetry | OpenTelemetry span-ы и трейсы |

### Матрица доступности коллекторов

| Коллектор | Yiisoft | Symfony | Laravel | Yii2 | Панель |
|-----------|:-------:|:-------:|:-------:|:----:|--------|
| Database | ✅ | ✅ | ✅ | ✅ | Database |
| Cache | ✅ | ✅ | ✅ | ✅ | Cache |
| Mailer | ✅ | ✅ | ✅ | ✅ | Mailer |
| Queue | ✅ | ✅ | ✅ | ✅ | Queue |
| Redis | ✅ | ✅ | ✅ | ✅ | Redis |
| Elasticsearch | ✅ | ✅ | ✅ | ✅ | Elasticsearch |
| View | ✅ | — | — | ✅ | WebView |
| Templates | — | ✅ | — | ✅ | Templates |
| Code Coverage | — | ✅ | ✅ | ✅ | Coverage |
| Asset Bundles | — | — | — | ✅ | Asset Bundles |
| Middleware | ✅ | — | — | — | Middleware |
| Messenger | — | ✅ | — | — | Messenger |

### Итого коллекторов по адаптерам

| Адаптер | Универсальные | Дополнительные | Всего |
|---------|:-------------:|:--------------:|:-----:|
| Yiisoft | 20 | 8 | **28** |
| Symfony | 20 | 9 | **29** |
| Yii2 | 20 | 10 | **30** |
| Laravel | 20 | 7 | **27** |

## Механизмы проксирования / перехвата

Каждый адаптер использует разные стратегии для перехвата внутренних механизмов фреймворка и передачи данных в коллекторы:

| Интерфейс | Yiisoft | Symfony | Laravel | Yii2 |
|-----------|---------|---------|---------|------|
| PSR-3 Logger | `LoggerInterfaceProxy` | `LoggerInterfaceProxy` | `LoggerInterfaceProxy` | `DebugLogTarget` |
| PSR-14 Events | `EventDispatcherInterfaceProxy` | `SymfonyEventDispatcherProxy` | `LaravelEventDispatcherProxy` | Wildcard `Event::on('*')` |
| PSR-18 HTTP Client | `HttpClientInterfaceProxy` | `HttpClientInterfaceProxy` | `HttpClientInterfaceProxy` | `HttpClientInterfaceProxy` |
| PSR-11 Container | `ContainerInterfaceProxy` | Compiler pass | — | — |
| VarDumper | `VarDumperHandlerInterfaceProxy` | Handler hook | Handler hook | Handler hook |
| Database | `ConnectionInterfaceProxy` | DBAL middleware | Event listener | `DbProfilingTarget` |
| Mailer | `MailerInterfaceProxy` | Event listener | Event listener | Event hook |
| Router | `UrlMatcherInterfaceProxy` | — | `RouterDataExtractor` | `UrlRuleProxy` |
| Validator | `ValidatorInterfaceProxy` | — | — | — |
| Queue | `QueueProviderInterfaceProxy` | — | Event listener | — |
| View/Templates | — | Twig profiler extension | — | `View::EVENT_AFTER_RENDER` |
| Cache | — | Decorated `CacheAdapter` | Event listener | — |
| Messenger | — | Messenger middleware | — | — |
| Translator | `TranslatorInterfaceProxy` | `SymfonyTranslatorProxy` | `LaravelTranslatorProxy` | `I18NProxy` |

## Возможности инспектора

Инспектор предоставляет интроспекцию приложения в реальном времени (не привязана к записям отладки). Все API-функции не зависят от адаптера:

| Возможность | Yiisoft | Symfony | Laravel | Yii2 | Cycle |
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

| Возможность | Yiisoft | Symfony | Laravel | Yii2 |
|-------------|:-------:|:-------:|:-------:|:----:|
| Отладка шаблонов | ✅ | ✅ | ❌ | ✅ |
| Code coverage | ❌ | ✅ | ✅ | ✅ |
| Отладка middleware | ✅ | ❌ | ❌ | ❌ |
| Отладка шины сообщений | ❌ | ✅ | ❌ | ❌ |
| Отладка asset bundles | ❌ | ❌ | ❌ | ✅ |
| Прокси контейнера | ✅ | ❌ | ❌ | ❌ |

::: tip
Паритет возможностей активно улучшается. Если вам нужен определённый коллектор для вашего фреймворка, [участие в разработке приветствуется](/ru/guide/contributing).
:::
