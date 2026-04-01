# Symfony Adapter

The Symfony adapter bridges ADP Kernel and API into Symfony 6.4+ / 7.x / 8.x via a Symfony Bundle.

## Installation

```bash
composer require app-dev-panel/adapter-symfony
```

::: info Package
[`app-dev-panel/adapter-symfony`](https://packagist.org/packages/app-dev-panel/adapter-symfony)
:::

## Bundle Registration

Register the bundle in `config/bundles.php`:

```php
return [
    // ...
    AppDevPanel\Adapter\Symfony\AppDevPanelBundle::class => ['dev' => true, 'test' => true],
];
```

## Configuration

Create `config/packages/app_dev_panel.yaml`:

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
        doctrine: true        # requires doctrine/dbal
        twig: true             # requires twig/twig
        security: true         # requires symfony/security-bundle
        cache: true
        mailer: true           # requires symfony/mailer
        messenger: true        # requires symfony/messenger
        code_coverage: false   # opt-in; requires pcov or xdebug
    ignored_requests:
        - '/_wdt/*'
        - '/_profiler/*'
        - '/debug/api/**'
    api:
        enabled: true
        allowed_ips: ['127.0.0.1', '::1']
```

## Collectors

Supports all Kernel collectors plus Symfony-specific ones: Twig templates, Security (user/roles/firewall), Cache, Messenger, Translator, Doctrine database queries, and [Redis commands](/guide/collectors/redis) (via Predis plugin or phpredis decorator).

## Translator Integration

The adapter automatically decorates Symfony's `TranslatorInterface` with <class>AppDevPanel\Adapter\Symfony\Proxy\SymfonyTranslatorProxy</class> via the compiler pass. All `trans()` calls are intercepted and logged to <class>AppDevPanel\Kernel\Collector\TranslatorCollector</class> — no code changes needed. See [Translator](/guide/translator) for details.

## Database Inspector

When `doctrine/dbal` is available, <class>AppDevPanel\Adapter\Symfony\Inspector\DoctrineSchemaProvider</class> provides database schema inspection. Falls back to <class>AppDevPanel\Adapter\Symfony\Inspector\NullSchemaProvider</class> otherwise.
