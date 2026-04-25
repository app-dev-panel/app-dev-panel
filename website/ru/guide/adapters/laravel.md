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

`composer require app-dev-panel/adapter-laravel` транзитивно подтягивает <pkg>app-dev-panel/frontend-assets</pkg> — пакет с предсобранной SPA панели и виджетом тулбара. Сервис-провайдер в три шага автодетектит источник и подставляет его в `panel.static_url`:

1. **Опубликованная копия** в `public/vendor/app-dev-panel/bundle.js` (если пользователь выполнил `vendor:publish --tag=app-dev-panel-assets` для отдачи статикой через веб-сервер).
2. **Composer-инсталляция** в `vendor/app-dev-panel/frontend-assets/dist/` — отдаётся по запросу через <class>AppDevPanel\Adapter\Laravel\Controller\FrontendAssetsController</class> по `GET /vendor/app-dev-panel/{file}`. Без `vendor:publish`, без симлинков, без дополнительных шагов сборки.
3. **CDN-fallback**: `https://app-dev-panel.github.io/app-dev-panel` (используется только если ни первый, ни второй вариант не доступны — например, пакет не установлен).

Поведение можно переопределить через `panel.static_url` (и опционально `toolbar.static_url`) в `config/app-dev-panel.php`:

| Значение | Эффект |
|----------|--------|
| `''` (по умолчанию) | Авто-детект, как описано выше |
| `'/my/path'` | Отдавать с собственного статического пути |
| `'http://localhost:3000'` | Vite dev-сервер с HMR |
| `'https://my-cdn.example/adp'` | Внешний CDN |

### Обновление сборки

Два канала:

```bash
# Composer (рекомендуется) — подтянет последний тег split-репозитория
composer update app-dev-panel/frontend-assets

# Либо для PHAR / non-Composer установок
php vendor/bin/adp frontend:update download --path=public/vendor/app-dev-panel
```

### Ручная работа с API через curl

Корень debug-API расположен на `/debug/api`, **не** `/debug/api/debug`:

```bash
curl http://127.0.0.1:8000/debug/api                  # список последних debug-записей
curl http://127.0.0.1:8000/debug/api/summary/{id}     # краткое описание записи
curl http://127.0.0.1:8000/debug/api/view/{id}        # полные данные записи
curl http://127.0.0.1:8000/debug/api/event-stream     # SSE-поток обновлений
```
