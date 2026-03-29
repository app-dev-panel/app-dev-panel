# Адаптер Laravel

Адаптер Laravel связывает ADP Kernel и API с Laravel 11.x / 12.x через сервис-провайдер с автообнаружением.

## Установка

```bash
composer require app-dev-panel/adapter-laravel
```

Пакет автоматически обнаруживается через `extra.laravel.providers` в composer.json — ручная регистрация не требуется.

## Конфигурация

Опубликуйте файл конфигурации:

```bash
php artisan vendor:publish --provider="AppDevPanel\Adapter\Laravel\AppDevPanelServiceProvider"
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

## Интеграция с переводчиком

Адаптер автоматически декорирует сервис `Translator` Laravel через `LaravelTranslatorProxy` при помощи `$app->extend('translator')`. Все вызовы `__('key')`, `trans()` и `Lang::get()` перехватываются. Точечная нотация ключей Laravel (`group.key`) разбирается на категорию и сообщение. Подробности на странице [Переводчик](/ru/guide/translator).

## Инспектор базы данных

`LaravelSchemaProvider` предоставляет инспекцию схемы БД через `Illuminate\Database\Connection`. Без настроенной БД используется `NullSchemaProvider`.
