---
description: "Установка и настройка ADP для Symfony 6.4+/7.x/8.x. Настройка бандла, коллекторов и интеграция с профайлером."
---

# Адаптер Symfony

Адаптер Symfony связывает ADP Kernel и API с Symfony 6.4+ / 7.x / 8.x через Symfony Bundle.

## Установка

```bash
composer require app-dev-panel/adapter-symfony
```

## Регистрация бандла

Зарегистрируйте бандл в `config/bundles.php`:

```php
return [
    // ...
    AppDevPanel\Adapter\Symfony\AppDevPanelBundle::class => ['dev' => true, 'test' => true],
];
```

## Конфигурация

Создайте `config/packages/app_dev_panel.yaml`:

```yaml
app_dev_panel:
    enabled: true
    storage:
        path: '%kernel.project_dir%/var/debug'
        history_size: 50
    collectors:
        request: true
        exception: true
        log: true
        event: true
        doctrine: true        # требуется doctrine/dbal
        twig: true             # требуется twig/twig
        security: true         # требуется symfony/security-bundle
        cache: true
        mailer: true           # требуется symfony/mailer
        messenger: true        # требуется symfony/messenger
        assets: true           # требуется symfony/asset-mapper
        code_coverage: false   # opt-in; требуется pcov или xdebug
    ignored_requests:
        - '/_wdt/*'
        - '/_profiler/*'
        - '/debug/api/**'
    api:
        enabled: true
        allowed_ips: ['127.0.0.1', '::1']
```

## Коллекторы

Поддерживает все коллекторы Kernel, а также специфичные для Symfony: шаблоны Twig, Security (пользователь/роли/файрвол), кеш, Messenger, переводчик и запросы к БД через Doctrine.

Дополнительно:

- **Asset bundles** — <class>AppDevPanel\Adapter\Symfony\EventSubscriber\AssetMapperSubscriber</class> собирает mapped-ассеты из `AssetMapperInterface` в конце каждого запроса (требуется `symfony/asset-mapper`).

## Интеграция с переводчиком

Адаптер автоматически декорирует <class>Symfony\Contracts\Translation\TranslatorInterface</class> Symfony через <class>AppDevPanel\Adapter\Symfony\Proxy\SymfonyTranslatorProxy</class> в compiler pass. Все вызовы `trans()` перехватываются и записываются в <class>AppDevPanel\Kernel\Collector\TranslatorCollector</class> — изменения кода не требуются. Подробности на странице [Переводчик](/ru/guide/translator).

## Инспектор базы данных

При наличии `doctrine/dbal` инспекция схемы БД осуществляется через <class>AppDevPanel\Adapter\Symfony\Inspector\DoctrineSchemaProvider</class>. Без Doctrine используется <class>AppDevPanel\Adapter\Symfony\Inspector\NullSchemaProvider</class>.

## Фронтенд-ассеты

`composer require app-dev-panel/adapter-symfony` транзитивно подтягивает <pkg>app-dev-panel/frontend-assets</pkg>. `AppDevPanelExtension` автодетектит источник в три шага и подставляет его в `panel.static_url`:

1. **Копия после `assets:install`** в `Resources/public/bundle.js` — отдаётся напрямую веб-сервером через `try_files`.
2. **Composer-инсталляция** в `vendor/app-dev-panel/frontend-assets/dist/` — отдаётся по запросу через <class>AppDevPanel\Adapter\Symfony\Controller\FrontendAssetsController</class> по `GET /bundles/appdevpanel/{file}`. URL совпадает с конвенцией `assets:install`, поэтому при наличии скопированных файлов веб-сервер всё ещё перехватывает их через `try_files`.
3. **CDN-fallback**: `https://app-dev-panel.github.io/app-dev-panel`.

Поведение переопределяется через `app_dev_panel.panel.static_url` (и `app_dev_panel.toolbar.static_url`) в `app_dev_panel.yaml`. Обновление сборки: `composer update app-dev-panel/frontend-assets`.

## Ручная работа с API через curl

Корень debug-API — `/debug/api`, **не** `/debug/api/debug`:

```bash
curl http://127.0.0.1:8000/debug/api                  # список последних debug-записей
curl http://127.0.0.1:8000/debug/api/summary/{id}
curl http://127.0.0.1:8000/debug/api/view/{id}
curl http://127.0.0.1:8000/debug/api/event-stream     # SSE
```
