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

## Routes

Create `config/routes/app_dev_panel.php` to mount `/debug`, `/debug/api/*`, and `/inspect/api/*`:

```php
<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->import('@AppDevPanelBundle/config/routes/adp.php');
};
```

Without this file the panel routes are not registered and `/debug` returns 404. (Once a Flex recipe lands, this file will be created automatically by `composer require`.)

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
        queue: true            # requires symfony/messenger
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

The panel and toolbar bundles are shipped by the Composer package <pkg>app-dev-panel/frontend-assets</pkg> (installed transitively when you `composer require app-dev-panel/adapter-symfony`). Static files are served by the **web server** — PHP never proxies them. Run the bundled command once after install to publish them under `public/`:

```bash
# copy (safe on all platforms)
php bin/console app-dev-panel:assets:install

# or symlink for zero-cost updates when FrontendAssets changes
php bin/console app-dev-panel:assets:install --symlink
php bin/console app-dev-panel:assets:install --relative

# override the public dir (defaults to %kernel.project_dir%/public)
php bin/console app-dev-panel:assets:install --public-dir=/var/www/html
```

The bundle auto-detects the published copy and points the panel at `/bundles/appdevpanel`. Until you run the command, the panel falls back to the GitHub Pages CDN.

| `static_url` resolution order | When it kicks in |
|-------------------------------|------------------|
| 1. `<projectDir>/public/bundles/appdevpanel/bundle.js` exists → `/bundles/appdevpanel` | After `bin/console app-dev-panel:assets:install` |
| 2. `Resources/public/bundle.js` exists → `/bundles/appdevpanel` | Legacy `make build-panel` flow before Symfony's `assets:install` |
| 3. `PanelConfig::DEFAULT_STATIC_URL` (GitHub Pages) | Default — works right after `composer require`, no commands required |

## Translator Integration

The adapter automatically decorates Symfony's <class>Symfony\Contracts\Translation\TranslatorInterface</class> with <class>AppDevPanel\Adapter\Symfony\Proxy\SymfonyTranslatorProxy</class> via the compiler pass. All `trans()` calls are intercepted and logged to <class>AppDevPanel\Kernel\Collector\TranslatorCollector</class> — no code changes needed. See [Translator](/guide/translator) for details.

## Database Inspector

When `doctrine/dbal` is available, <class>AppDevPanel\Adapter\Symfony\Inspector\DoctrineSchemaProvider</class> provides database schema inspection. Falls back to <class>AppDevPanel\Adapter\Symfony\Inspector\NullSchemaProvider</class> otherwise.
