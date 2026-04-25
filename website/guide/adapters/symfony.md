---
description: "Install and configure ADP for Symfony 6.4+/7.x/8.x. Bundle setup, collector wiring, and profiler integration."
---

# Symfony Adapter

The Symfony adapter bridges ADP Kernel and API into Symfony 6.4+ / 7.x / 8.x via a Symfony Bundle.

## Installation

```bash
composer require app-dev-panel/adapter-symfony
```

::: info Package
<pkg>app-dev-panel/adapter-symfony</pkg>
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
        assets: true           # requires symfony/asset-mapper
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

Additionally:

- **Asset bundles** — <class>AppDevPanel\Adapter\Symfony\EventSubscriber\AssetMapperSubscriber</class> collects mapped assets from `AssetMapperInterface` at the end of each request (requires `symfony/asset-mapper`).

## Translator Integration

The adapter automatically decorates Symfony's <class>Symfony\Contracts\Translation\TranslatorInterface</class> with <class>AppDevPanel\Adapter\Symfony\Proxy\SymfonyTranslatorProxy</class> via the compiler pass. All `trans()` calls are intercepted and logged to <class>AppDevPanel\Kernel\Collector\TranslatorCollector</class> — no code changes needed. See [Translator](/guide/translator) for details.

## Database Inspector

When `doctrine/dbal` is available, <class>AppDevPanel\Adapter\Symfony\Inspector\DoctrineSchemaProvider</class> provides database schema inspection. Falls back to <class>AppDevPanel\Adapter\Symfony\Inspector\NullSchemaProvider</class> otherwise.

## Frontend Assets

`composer require app-dev-panel/adapter-symfony` transitively pulls <pkg>app-dev-panel/frontend-assets</pkg>, which ships the prebuilt panel SPA and toolbar widget. `AppDevPanelExtension` auto-detects the source in three steps and resolves `panel.static_url` accordingly:

1. **`assets:install` copy** in `Resources/public/bundle.js` — webserver serves it directly via `try_files`.
2. **Composer-installed bundle** in `vendor/app-dev-panel/frontend-assets/dist/` — served on demand by <class>AppDevPanel\Adapter\Symfony\Controller\FrontendAssetsController</class> under `GET /bundles/appdevpanel/{file}`. The URL matches the existing `assets:install` convention, so a webserver fallback (`try_files $uri /index.php`) still wins when files are present.
3. **CDN fallback**: `https://app-dev-panel.github.io/app-dev-panel`.

Override via `app_dev_panel.panel.static_url` (and `app_dev_panel.toolbar.static_url`) in `app_dev_panel.yaml`. Update the bundle with `composer update app-dev-panel/frontend-assets`.

## Manual API exploration (curl)

The debug API root is at `/debug/api`, **not** `/debug/api/debug`:

```bash
curl http://127.0.0.1:8000/debug/api                  # list recent debug entries
curl http://127.0.0.1:8000/debug/api/summary/{id}
curl http://127.0.0.1:8000/debug/api/view/{id}
curl http://127.0.0.1:8000/debug/api/event-stream     # SSE
```
