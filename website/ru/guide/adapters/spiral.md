---
description: "Установка и настройка ADP для Spiral Framework 3.x. Регистрация Bootloader, PSR-15 middleware-конвейер, фикстурные эндпоинты."
---

# Адаптер Spiral

Адаптер Spiral связывает ADP Kernel и API со Spiral Framework 3.14+ через два
Bootloader'а. Spiral нативно работает с PSR-7/PSR-15 и предоставляет
`Container::bindInjector()` и `InterceptorInterface` как first-class точки расширения,
поэтому адаптер получился самым идиоматичным из полных адаптеров: никаких мостов
HttpFoundation/Illuminate Request, никаких compiler pass, никакого императивного
переподключения сервисов в `boot()`.

## Установка

```bash
composer require app-dev-panel/adapter-spiral --dev
```

::: info Пакет
<pkg>app-dev-panel/adapter-spiral</pkg>
:::

## Подключение

Зарегистрируйте оба Bootloader'а в `Kernel` приложения:

```php
final class Kernel extends \Spiral\Framework\Kernel
{
    public function defineBootloaders(): array
    {
        return [
            // ... ваши Bootloader'ы ...
            \AppDevPanel\Adapter\Spiral\Bootloader\AppDevPanelBootloader::class,
            \AppDevPanel\Adapter\Spiral\Bootloader\AdpInterceptorBootloader::class,
        ];
    }
}
```

`AdpInterceptorBootloader` объявляет `AppDevPanelBootloader` своей зависимостью и
регистрирует консольный / очередной перехватчики только при наличии пакетов
`spiral/console` / `spiral/queue` — без обоих пакетов это no-op.

Подключите два PSR-15 middleware в HTTP-конвейер (наружные, до CSRF / sessions):

```php
// app/config/http.php
return [
    'middleware' => [
        \AppDevPanel\Adapter\Spiral\Middleware\AdpApiMiddleware::class,
        \AppDevPanel\Adapter\Spiral\Middleware\DebugMiddleware::class,
        // ... ваши middleware ...
    ],
];
```

`AdpApiMiddleware` должен идти перед `DebugMiddleware`, чтобы запросы к собственным
маршрутам ADP (`/debug/*`, `/inspect/api/*`) сразу уходили в `ApiApplication` и не
попадали под трассировку Debugger.

## Конфигурация

`AdpConfig` — это `Spiral\Core\InjectableConfig`. Значения по умолчанию совпадают со
старым env-only-режимом; чтобы их переопределить, добавьте файл
`app/config/app-dev-panel.php`:

```php
return [
    'enabled' => true,
    'storage' => [
        'path' => directory('runtime') . 'debug',
        'history_size' => 50,
    ],
    'panel' => [
        'static_url' => null,
        'base_path' => '/debug',
    ],
    'ignored_requests' => ['/health', '/_status/*'],
    'ignored_commands' => ['cache:*', 'list', 'help'],
    'collectors' => [
        'mailer' => false,
        // ... переключатели для отдельных коллекторов ...
    ],
];
```

Каждый аксессор `AdpConfig::*()` при значении `null` (по умолчанию) делает откат на
переменную окружения `APP_DEV_PANEL_*`, поэтому приложения без
`app/config/app-dev-panel.php` продолжают работать:

| Переменная | Соответствует | По умолчанию |
|------------|---------------|--------------|
| `APP_DEV_PANEL_STORAGE_PATH` | `storage.path` | `sys_get_temp_dir()/app-dev-panel` |
| `APP_DEV_PANEL_STATIC_URL` | `panel.static_url` | CDN GitHub Pages для статики панели |
| `APP_DEV_PANEL_ROOT_PATH` | корень для path resolver | задаётся entry-point'ом playground'а |
| `APP_DEV_PANEL_RUNTIME_PATH` | каталог runtime | производный от root + `runtime/` |

## Коллекторы

Bootloader регистрирует все framework-agnostic коллекторы Kernel:

`LogCollector`, `EventCollector`, `ExceptionCollector`, `HttpClientCollector`,
`VarDumperCollector`, `TimelineCollector`, `RequestCollector`, `WebAppInfoCollector`,
`FilesystemStreamCollector`, `CacheCollector`, `RouterCollector`, `ValidatorCollector`,
`TranslatorCollector`, `TemplateCollector`, `MailerCollector`, `QueueCollector`,
`CommandCollector`, `ConsoleAppInfoCollector`.

Если в контейнере приложения присутствует соответствующий интерфейс, описанные ниже
**инжекторы контейнера** прозрачно оборачивают binding, и коллектор получает данные
автоматически — никаких ручных вызовов `collect()` не требуется.

## Архитектура

Адаптер построен на двух first-class точках расширения Spiral 3 вместо императивного
переподключения сервисов в `boot()`:

- **`Container::bindInjector(string $type, InjectorInterface $injector)`** — каноничный
  механизм Spiral для оборачивания любого binding'а типа `$type` декоратором.
  Используется для авто-декорирования каждого PSR / Spiral сервиса, из которого Kernel
  собирает данные.
- **`InterceptorInterface`** — перехватчики Spiral для отдельных доменов (консольные
  команды, обработчики очереди, обработчики маршрутов). Используются для управления
  жизненным циклом `Debugger::startup()` / `Debugger::shutdown()` вне HTTP-конвейера.

Подвижные части:

- `AppDevPanelBootloader` — регистрирует ADP-сервисы как singleton'ы, устанавливает
  восемь инжекторов контейнера и привязывает inspector-провайдеры к duck-typed-алиасам,
  которые ожидают inspector-контроллеры (`'config'`, `'router'`, `'urlMatcher'`).
- `AdpInterceptorBootloader` — зависит от `AppDevPanelBootloader` и подключает три
  перехватчика к их host-реестрам (`ConsoleBootloader::addInterceptor()`,
  `QueueRegistry::addConsumeInterceptor()`).
- `DebugMiddleware` — PSR-15 middleware, оборачивает остальной конвейер в
  `Debugger::startup()` / `Debugger::shutdown()`. При исключении возвращает
  синтетический `500`-ответ с заголовком `X-Debug-Id`, чтобы запись всё равно
  отображалась в панели.
- `AdpApiMiddleware` — PSR-15 middleware, перехватывает `/debug`, `/debug/api/*`,
  `/inspect/api/*` и передаёт их в `ApiApplication`.

## Инжекторы контейнера

Контейнер Spiral 3 предоставляет `bindInjector(string $type, InjectorInterface $injector)` —
каноничный способ обернуть любой binding типа `$type` декоратором. Адаптер Spiral
поставляет восемь инжекторов, которые автоматически подключают коллекторы ADP, когда
соответствующий интерфейс присутствует в контейнере приложения:

| Интерфейс | Инжектор | Оборачивается в | Питает коллектор |
|-----------|----------|-----------------|------------------|
| `Psr\Log\LoggerInterface` | `LoggerProxyInjector` | `LoggerInterfaceProxy` (Kernel) | `LogCollector` |
| `Psr\EventDispatcher\EventDispatcherInterface` | `EventDispatcherProxyInjector` | `EventDispatcherInterfaceProxy` (Kernel) | `EventCollector` |
| `Psr\Http\Client\ClientInterface` | `HttpClientProxyInjector` | `HttpClientInterfaceProxy` (Kernel) | `HttpClientCollector` |
| `Psr\SimpleCache\CacheInterface` | `CacheProxyInjector` | `Psr16CacheProxy` | `CacheCollector` |
| `Spiral\Mailer\MailerInterface` | `MailerProxyInjector` | `TracingMailer` | `MailerCollector` |
| `Spiral\Queue\QueueInterface` | `QueueProxyInjector` | `TracingQueue` (на стороне push) | `QueueCollector` |
| `Spiral\Translator\TranslatorInterface` | `TranslatorProxyInjector` | `TracingTranslator` | `TranslatorCollector` |
| `Spiral\Views\ViewsInterface` | `ViewsProxyInjector` | `TracingViews` | `TemplateCollector` |

Каждый инжектор сначала достаёт исходный binding (чтобы `bindInjector` не «потерял» его),
освобождает слот и затем привязывает класс инжектора. Последующие вызовы
`$container->get($iface)` попадают в `createInjection()`, который возвращает нужную
обёртку поверх захваченного исходного сервиса. Дополнительный ленивый
`resolveUnderlying()` срабатывает, если приложение перепривязывает интерфейс уже после
запуска Bootloader'а.

Все опциональные пакеты Spiral (`spiral/mailer`, `spiral/queue`, `spiral/translator`,
`spiral/views`) и PSR-16 (`psr/simple-cache`) проверяются через `interface_exists`, поэтому
Bootloader безопасен с любой комбинацией установленных пакетов.

## Перехватчики (Interceptors)

`AdpInterceptorBootloader` регистрирует три реализации `InterceptorInterface` Spiral в
их host-реестрах:

| Домен | Перехватчик | Реестр |
|-------|-------------|--------|
| Консольные команды | `DebugConsoleInterceptor` | `Spiral\Console\Bootloader\ConsoleBootloader::addInterceptor()` |
| Задачи очереди (consume) | `DebugQueueInterceptor` | `Spiral\Queue\QueueRegistry::addConsumeInterceptor()` |
| HTTP-обработчики маршрутов | `DebugRouteInterceptor` | `RouteInterface::withInterceptors()` (включается на отдельный маршрут) |

Каждый перехватчик управляет своим жизненным циклом
`Debugger::startup()` / `Debugger::shutdown()` для своего домена — поэтому консольные
команды и обработчики очереди получают собственные debug-записи без написания PSR-15
middleware. Консольный перехватчик дополнительно питает `CommandCollector`,
`ConsoleAppInfoCollector` и при исключении — `ExceptionCollector`.

## Inspector-провайдеры

Пять spiral-aware провайдеров привязаны к duck-typed-алиасам контейнера, которые
ожидают inspector-контроллеры (`'config'`, `'router'`, `'urlMatcher'`,
`AuthorizationConfigProviderInterface`) плюс внутренний
`SpiralEventListenerProvider`. Каждый разблокирует ранее возвращавший 501 эндпоинт:

| Эндпоинт | Провайдер | Источник |
|----------|-----------|----------|
| `/inspect/api/config?group=di` | `SpiralConfigProvider::getServices()` | `Spiral\Core\Container::getBindings()` |
| `/inspect/api/config?group=params` | `SpiralConfigProvider::getParams()` | `EnvironmentInterface` + `DirectoriesInterface` |
| `/inspect/api/config?group=bundles` | `SpiralConfigProvider::getBootloaders()` | `BootloadManager\InitializerInterface` |
| `/inspect/api/events` | `SpiralEventListenerProvider` | `Spiral\Events\ListenerRegistryInterface` |
| `/inspect/api/routes` | `SpiralRouteCollectionAdapter` | `Spiral\Router\RouterInterface::getRoutes()` |
| `/inspect/api/route/check` | `SpiralUrlMatcherAdapter` | `Router::matchRoute()` |
| `/inspect/api/authorization` | `SpiralAuthorizationConfigProvider` | `Spiral\Auth\TokenStorageInterface` + `ActorProviderInterface` |

`/routes`, `/events` и `/authorization` возвращают данные только если соответствующий
компонент Spiral действительно установлен; иначе провайдеры аккуратно возвращают пустой
массив — никаких 501.

## Сравнение с другими адаптерами

| Аспект | Symfony / Laravel | Spiral |
|--------|-------------------|--------|
| Регистрация | Bundle / ServiceProvider | Bootloader |
| HTTP типы | HttpFoundation → PSR-7 конвертация | **PSR-7 нативно** — без конвертации |
| Хук в lifecycle | События ядра (`kernel.request` / `kernel.terminate`) | PSR-15 middleware в HTTP-конвейере |
| Маршруты ADP | Маршруты фреймворка → catch-all контроллер | PSR-15 middleware перехватывает пути ADP |

## Playground

Эталонный Spiral-плейграунд находится в `playground/spiral-app/`. Запускается на
встроенном PHP-сервере (порт `8105`):

```bash
make serve-spiral          # http://127.0.0.1:8105/
make fixtures-spiral       # CLI-фикстуры
make test-fixtures-spiral  # PHPUnit E2E
```
