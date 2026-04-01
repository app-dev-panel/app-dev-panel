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

## Интеграция с переводчиком

Адаптер автоматически декорирует <class>Symfony\Contracts\Translation\TranslatorInterface</class> Symfony через <class>AppDevPanel\Adapter\Symfony\Proxy\SymfonyTranslatorProxy</class> в compiler pass. Все вызовы `trans()` перехватываются и записываются в <class>AppDevPanel\Kernel\Collector\TranslatorCollector</class> — изменения кода не требуются. Подробности на странице [Переводчик](/ru/guide/translator).

## Инспектор базы данных

При наличии `doctrine/dbal` инспекция схемы БД осуществляется через <class>AppDevPanel\Adapter\Symfony\Inspector\DoctrineSchemaProvider</class>. Без Doctrine используется <class>AppDevPanel\Adapter\Symfony\Inspector\NullSchemaProvider</class>.
