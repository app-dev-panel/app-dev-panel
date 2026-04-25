---
description: "Установка и настройка ADP для Yii 3. Конфигурационные плагины, подключение коллекторов и middleware."
---

# Адаптер Yii 3

Адаптер Yii 3 — эталонный адаптер ADP. Он связывает ADP Kernel и API с Yii 3 через систему конфигурационных плагинов.

## Установка

```bash
composer require app-dev-panel/adapter-yii3
```

Пакет автоматически регистрируется через систему config-плагинов Yii 3 — ручная настройка не требуется.

## Конфигурация

Все настройки управляются в `config/params.php`:

```php
'app-dev-panel/yii3' => [
    'enabled' => true,
    'collectors' => [...],
    'trackedServices' => [...],
    'ignoredRequests' => [],
    'ignoredCommands' => [],
    'dumper' => [
        'excludedClasses' => [],
    ],
    'logLevel' => [
        'AppDevPanel\\' => 0,
    ],
    'storage' => [
        'path' => '@runtime/debug',
        'historySize' => 50,
        'exclude' => [],
    ],
],
```

## Middleware

Добавьте следующие middleware в стек вашего веб-приложения (порядок важен):

```
DebugHeaders → ErrorCatcher → YiiApiMiddleware → ... → Router
```

- **DebugHeaders** — должен быть внешним, чтобы добавлять `X-Debug-Id` даже при ошибках
- **YiiApiMiddleware** — перехватывает запросы `/debug/api/*` до роутера

## Коллекторы

Включает специфичные для Yii коллекторы: запросы к БД, почта, очереди, роутер, валидатор, переводчик и представления — в дополнение ко всем коллекторам Kernel (логи, события, исключения, HTTP-клиент и др.).

Дополнительно:

- **Asset bundles** — <class>AppDevPanel\Adapter\Yii3\Collector\Asset\AssetLoaderInterfaceProxy</class> оборачивает `AssetLoaderInterface` для сбора загруженных бандлов с CSS/JS файлами, зависимостями и опциями.
- **Code coverage** — <class>AppDevPanel\Kernel\Collector\CodeCoverageCollector</class> зарегистрирован и собирает покрытие кода построчно за каждый запрос (требуется pcov или xdebug).

## Интеграция с переводчиком

При установленном пакете `yiisoft/translator` адаптер регистрирует <class>AppDevPanel\Adapter\Yii3\Collector\Translator\TranslatorInterfaceProxy</class> в `trackedServices`. Все вызовы `translate()` на `Yiisoft\Translator\TranslatorInterface` перехватываются автоматически. Подробности на странице [Переводчик](/ru/guide/translator).

## Инспектор базы данных

Инспекция схемы базы данных осуществляется через `Yiisoft\Db` с помощью <class>AppDevPanel\Adapter\Yii3\Inspector\DbSchemaProvider</class>.

## Фронтенд-ассеты

`composer require app-dev-panel/adapter-yii3` транзитивно подтягивает <pkg>app-dev-panel/frontend-assets</pkg>. DI-фабрика в `config/di-api.php` автодетектит источник:

1. Если `FrontendAssets::exists()` — создаётся симлинк `vendor/app-dev-panel/frontend-assets/dist/` → `@public/app-dev-panel`, `panel.staticUrl = '/app-dev-panel'`.
2. Иначе fallback на `libs/Adapter/Yii3/resources/dist` (для разработки в монорепе).
3. Иначе — CDN (`https://app-dev-panel.github.io/app-dev-panel`).

Поведение переопределяется параметром `app-dev-panel/yii3.panel.staticUrl`. Обновление сборки: `composer update app-dev-panel/frontend-assets`.
