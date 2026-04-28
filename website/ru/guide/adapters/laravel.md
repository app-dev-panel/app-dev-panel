---
description: "Установка и настройка ADP для Laravel 11.x/12.x/13.x. Автообнаружение сервис-провайдера и настройка коллекторов."
---

# Адаптер Laravel

Адаптер Laravel связывает ADP Kernel и API с Laravel 11.x / 12.x / 13.x через сервис-провайдер с автообнаружением.

## Установка

```bash
composer require app-dev-panel/adapter-laravel
```

Пакет автоматически обнаруживается через `extra.laravel.providers` в composer.json — ручная регистрация не требуется.

## Конфигурация

Опубликуйте файл конфигурации:

```bash
php artisan vendor:publish --tag=app-dev-panel-config
```

Будет создан `config/app-dev-panel.php`:

```php
return [
    'enabled' => env('APP_DEV_PANEL_ENABLED', env('APP_DEBUG', true)),
    'storage' => [
        'path' => storage_path('debug'),
        'history_size' => 50,
    ],
    'collectors' => [
        'request' => true,
        'exception' => true,
        'log' => true,
        'event' => true,
        'database' => true,
        'cache' => true,
        'mailer' => true,
        'queue' => true,
        'assets' => true,
        'template' => true,
        'opentelemetry' => true,
        'code_coverage' => false,  // opt-in; требуется pcov или xdebug
        // ... все коллекторы включены по умолчанию
    ],
    'ignored_requests' => ['/debug/api/**', '/inspect/api/**'],
    'ignored_commands' => ['completion', 'help', 'list', 'debug:*', 'cache:*'],
    'api' => [
        'enabled' => true,
        'allowed_ips' => ['127.0.0.1', '::1'],
        'auth_token' => env('APP_DEV_PANEL_TOKEN', ''),
    ],
];
```

## Коллекторы

Поддерживает все коллекторы Kernel, а также сбор данных через слушатели событий Laravel: запросы Eloquent, операции с кешем, почта, задачи очереди, запросы HTTP-клиента и обращения к переводчику.

Дополнительно:

- **Шаблоны Blade** — <class>AppDevPanel\Adapter\Laravel\Collector\TemplateCollectorCompilerEngine</class> оборачивает `CompilerEngine` Blade для автоматического замера времени рендеринга и глубины вложенности.
- **Asset bundles** — <class>AppDevPanel\Adapter\Laravel\EventListener\ViteAssetListener</class> собирает отрендеренные Vite-ассеты (`preloadedAssets()`) после каждого ответа.
- **OpenTelemetry** — <class>AppDevPanel\Kernel\Collector\SpanProcessorInterfaceProxy</class> декорирует `SpanProcessorInterface` через `$app->extend()` при установленном `open-telemetry/sdk`.

## Интеграция с переводчиком

Адаптер автоматически декорирует сервис `Translator` Laravel через <class>AppDevPanel\Adapter\Laravel\Proxy\LaravelTranslatorProxy</class> при помощи `$app->extend('translator')`. Все вызовы `__('key')`, `trans()` и `Lang::get()` перехватываются. Точечная нотация ключей Laravel (`group.key`) разбирается на категорию и сообщение. Подробности на странице [Переводчик](/ru/guide/translator).

## Инспектор базы данных

<class>AppDevPanel\Adapter\Laravel\Inspector\LaravelSchemaProvider</class> предоставляет инспекцию схемы БД через `Illuminate\Database\Connection`. Без настроенной БД используется <class>AppDevPanel\Adapter\Laravel\Inspector\NullSchemaProvider</class>.

## Фронтенд-ассеты

`composer require app-dev-panel/adapter-laravel` транзитивно подтягивает <pkg>app-dev-panel/frontend-assets</pkg> — пакет с предсобранной SPA панели и виджетом тулбара. <class>AppDevPanel\Adapter\Laravel\AppDevPanelServiceProvider</class> резолвит `panel.static_url` в таком порядке:

1. **Опубликованная копия** — `public/vendor/app-dev-panel/bundle.js` существует (после `php artisan vendor:publish --tag=app-dev-panel-assets`). Веб-сервер отдаёт файлы напрямую.
2. **Composer-инсталляция** — `vendor/app-dev-panel/frontend-assets/dist/` существует. Адаптер выставляет URL `/vendor/app-dev-panel`.
3. **CDN-fallback** — `https://app-dev-panel.github.io/app-dev-panel`. Используется если ни одно из выше не доступно.

Поведение переопределяется через `config/app-dev-panel.php`:

```php
'panel' => [
    'static_url' => '',                        // '' = автодетект (рекомендуется)
    // 'static_url' => 'http://localhost:3000', // Vite dev-сервер с HMR
    // 'static_url' => 'https://my-cdn/adp',    // ваш собственный CDN
],
'toolbar' => [
    'enabled' => true,
    'static_url' => '',                        // '' = derive от panel.static_url + '/toolbar'
],
```

Тулбар грузится из `{panel.static_url}/toolbar/bundle.js` — держите оба URL рядом, если только не зеркалите их раздельно.

### Обновление сборки

```bash
composer update app-dev-panel/frontend-assets
```

Для PHAR / non-Composer-установок artisan-команда `frontend:update` качает релизные тарболлы с GitHub:

```bash
php artisan frontend:update download --path=public/vendor/app-dev-panel
```

## Ручная работа с API

Корень debug-API расположен на `/debug/api`, **не** `/debug/api/debug`:

```bash
curl http://127.0.0.1:8000/debug/api                 # список последних debug-записей
curl http://127.0.0.1:8000/debug/api/summary/{id}    # краткое описание записи
curl http://127.0.0.1:8000/debug/api/view/{id}       # полные данные записи
curl http://127.0.0.1:8000/debug/api/event-stream    # SSE-поток обновлений
```
