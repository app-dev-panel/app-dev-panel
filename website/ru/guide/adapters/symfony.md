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

## Маршруты

Создайте `config/routes/app_dev_panel.php`, чтобы подключить панель и API:

```php
<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->import('@AppDevPanelBundle/config/routes/adp.php');
};
```

Это подключает `/debug` (панель SPA), `/debug/api/**` (debug data) и `/inspect/api/**` (inspector).

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
        doctrine: true         # требуется doctrine/dbal
        twig: true             # требуется twig/twig
        security: true         # требуется symfony/security-bundle
        cache: true
        mailer: true           # требуется symfony/mailer
        queue: true            # требуется symfony/messenger
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
