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

## Installing Assets

The panel and toolbar bundles are shipped by the Composer package <pkg>app-dev-panel/frontend-assets</pkg> (installed transitively when you `composer require app-dev-panel/adapter-symfony`). The adapter exposes them in two complementary ways — pick whichever matches your deployment:

### Runtime route — `/_adp-assets/*` (default, zero setup)

`AdpAssetsController` streams files directly from `vendor/app-dev-panel/frontend-assets/dist/`. When `app_dev_panel.panel.static_url` is left empty, the bundle automatically points the panel at `/_adp-assets`, so nothing else is required. Path-traversal requests are rejected with 404.

This is fine for dev environments and small installs; each static file costs one PHP request.

### Prebake — `public/bundles/appdevpanel/` (for prod)

Run the bundled command once to copy (or symlink) the assets into your public directory so nginx/Apache serves them directly:

```bash
# copy (safe on all platforms)
php bin/console app-dev-panel:assets:install

# or symlink for zero-cost updates when FrontendAssets changes
php bin/console app-dev-panel:assets:install --symlink
php bin/console app-dev-panel:assets:install --relative

# override the public dir (defaults to %kernel.project_dir%/public)
php bin/console app-dev-panel:assets:install --public-dir=/var/www/html
```

After prebaking, set `app_dev_panel.panel.static_url: /bundles/appdevpanel` (or leave it empty — the bundle auto-detects the local copy and prefers it over the runtime route).

| `static_url` resolution order | When it kicks in |
|-------------------------------|------------------|
| 1. `Resources/public/bundle.js` exists → `/bundles/appdevpanel` | Legacy `make build-panel` flow, or after `app-dev-panel:assets:install` when using `--symlink` into `Resources/public/` |
| 2. `FrontendAssets::exists()` → `/_adp-assets` | Default — works right after `composer require` |
| 3. `PanelConfig::DEFAULT_STATIC_URL` (GitHub Pages) | Last resort |

## Translator Integration

The adapter automatically decorates Symfony's <class>Symfony\Contracts\Translation\TranslatorInterface</class> with <class>AppDevPanel\Adapter\Symfony\Proxy\SymfonyTranslatorProxy</class> via the compiler pass. All `trans()` calls are intercepted and logged to <class>AppDevPanel\Kernel\Collector\TranslatorCollector</class> — no code changes needed. See [Translator](/guide/translator) for details.

## Database Inspector

When `doctrine/dbal` is available, <class>AppDevPanel\Adapter\Symfony\Inspector\DoctrineSchemaProvider</class> provides database schema inspection. Falls back to <class>AppDevPanel\Adapter\Symfony\Inspector\NullSchemaProvider</class> otherwise.
