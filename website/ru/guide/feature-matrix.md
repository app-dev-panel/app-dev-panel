---
title: Матрица возможностей
---

# Матрица возможностей

ADP поддерживает несколько PHP-фреймворков через адаптеры. Каждый адаптер подключает хуки фреймворка к общим коллекторам Kernel. На этой странице описано, что доступно в каждом адаптере.

## Коллекторы ядра

Все 17 коллекторов Kernel доступны для каждого адаптера. Адаптеры подключают хуки фреймворка для передачи данных в них.

| Коллектор | Панель | Описание |
|-----------|--------|----------|
| `TimelineCollector` | Timeline | События временной шкалы производительности |
| `LogCollector` | Logs | PSR-3 сообщения логов |
| `EventCollector` | Events | PSR-14 отправленные события |
| `ExceptionCollector` | Exceptions | Необработанные исключения со стек-трейсами |
| `ServiceCollector` | Services | Разрешения сервисов DI-контейнера |
| `HttpClientCollector` | HTTP Client | PSR-18 исходящие HTTP-запросы |
| `VarDumperCollector` | Var Dumper | Вызовы `dump()` / `dd()` |
| `EnvironmentCollector` | Environment | Информация о PHP и ОС |
| `FilesystemStreamCollector` | Filesystem | Операции файловых потоков |
| `HttpStreamCollector` | _(скрыт)_ | Сырые HTTP-потоки (подвид HTTP Client) |
| `RequestCollector` | Request | Входящие HTTP запрос/ответ (только web) |
| `WebAppInfoCollector` | _(мета)_ | Сводка web-приложения для списка записей |
| `CommandCollector` | _(мета)_ | Сводка консольной команды |
| `ConsoleAppInfoCollector` | _(мета)_ | Сводка консольного приложения для списка записей |
| `DatabaseCollector` | Database | SQL-запросы, время выполнения, транзакции |
| `MailerCollector` | Mailer | Отправленные email-сообщения |
| `AssetBundleCollector` | Asset Bundles | Зарегистрированные бандлы фронтенд-ассетов |

## Коллекторы адаптеров

Помимо общих коллекторов Kernel, каждый адаптер предоставляет коллекторы, специфичные для фреймворка:

| Коллектор | Yiisoft | Symfony | Laravel | Yii2 | Панель |
|-----------|:-------:|:-------:|:-------:|:----:|--------|
| Middleware | ✅ | — | — | — | Middleware |
| Queue | ✅ | — | ✅ | — | Queue |
| Router | ✅ | — | ✅ | — | Router |
| Validator | ✅ | — | — | — | Validator |
| WebView | ✅ | — | — | — | WebView |
| Twig Templates | — | ✅ | — | — | Twig |
| Security | ✅ | ✅ | ✅ | ✅ | Security |
| Cache | — | ✅ | ✅ | — | Cache |
| Messenger | — | ✅ | — | — | Messenger |

### Итого коллекторов по адаптерам

| Адаптер | Kernel | Адаптер | Всего |
|---------|:------:|:-------:|:-----:|
| Yiisoft | 17 | 5 | **22** |
| Symfony | 17 | 4 | **21** |
| Laravel | 17 | 2 | **19** |
| Yii2 | 17 | 0 | **17** |

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
| Router | `UrlMatcherInterfaceProxy` | — | `RouterDataExtractor` | — |
| Validator | `ValidatorInterfaceProxy` | — | — | — |
| Queue | `QueueProviderInterfaceProxy` | — | Event listener | — |
| View/Templates | — | Twig profiler extension | — | View event |
| Cache | — | Decorated `CacheAdapter` | Event listener | — |
| Messenger | — | Messenger middleware | — | — |

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

Текущие различия между адаптерами в специфичной функциональности:

| Возможность | Yiisoft | Symfony | Laravel | Yii2 |
|-------------|:-------:|:-------:|:-------:|:----:|
| Мониторинг очередей | ✅ | ❌ | ✅ | ❌ |
| Отладка маршрутов | ✅ | ❌ | ✅ | ❌ |
| Отладка валидации | ✅ | ❌ | ❌ | ❌ |
| Отладка шаблонов | ✅ | ✅ | ❌ | ❌ |
| Отладка middleware | ✅ | ❌ | ❌ | ❌ |
| Отладка кеша | ❌ | ✅ | ✅ | ❌ |
| Отладка шины сообщений | ❌ | ✅ | ❌ | ❌ |
| Прокси контейнера | ✅ | ❌ | ❌ | ❌ |

::: tip
Паритет возможностей активно улучшается. Если вам нужен определённый коллектор для вашего фреймворка, [участие в разработке приветствуется](/ru/guide/contributing).
:::
