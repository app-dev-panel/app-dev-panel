# Configuration Reference

## Installation

```bash
composer require app-dev-panel/adapter-symfony
```

## Bundle Registration

```php
// config/bundles.php
return [
    // ...
    AppDevPanel\Adapter\Symfony\AppDevPanelBundle::class => ['dev' => true, 'test' => true],
];
```

## Full Configuration

```yaml
# config/packages/app_dev_panel.yaml
app_dev_panel:

    # Master switch. When false, no services are registered.
    enabled: true

    storage:
        # Directory for JSON debug data files.
        path: '%kernel.project_dir%/var/debug'

        # Maximum number of debug entries to retain.
        # Oldest entries are garbage-collected on flush.
        history_size: 50

    collectors:
        # Kernel collectors (framework-agnostic, PSR-based)
        request: true              # RequestCollector (Kernel, PSR-7)
        exception: true            # ExceptionCollector (Kernel)
        log: true                  # LogCollector (Kernel, via LoggerInterfaceProxy)
        event: true                # EventCollector (Kernel, via SymfonyEventDispatcherProxy)
        service: true              # ServiceCollector
        http_client: true          # HttpClientCollector (via HttpClientInterfaceProxy)
        timeline: true             # TimelineCollector
        var_dumper: true           # VarDumperCollector
        filesystem_stream: true    # FilesystemStreamCollector
        http_stream: true          # HttpStreamCollector
        command: true              # CommandCollector (console commands)

        # Symfony-specific collectors
        doctrine: true             # DatabaseCollector (requires doctrine/dbal)
        twig: true                 # TwigCollector (requires twig/twig)
        security: true             # SecurityCollector (requires symfony/security-bundle)
        cache: true                # CacheCollector
        mailer: true               # MailerCollector (requires symfony/mailer)
        messenger: true            # MessengerCollector (requires symfony/messenger)

    # URL patterns to skip (wildcard matching).
    # Matching requests will not generate debug entries.
    ignored_requests:
        - '/_wdt/*'
        - '/_profiler/*'
        - '/debug/api/*'

    # Command name patterns to skip (wildcard matching).
    ignored_commands:
        - 'completion'
        - 'help'
        - 'list'
        - 'debug:*'
        - 'cache:*'

    dumper:
        # Fully-qualified class names to exclude from object dumps.
        excluded_classes: []

    api:
        # Mount the ADP API bridge controller.
        enabled: true

        # IP addresses allowed to access the debug API.
        allowed_ips: ['127.0.0.1', '::1']

        # Authentication token. Empty string = no auth.
        auth_token: ''
```

## Environment-Specific Configuration

```yaml
# config/packages/dev/app_dev_panel.yaml
app_dev_panel:
    enabled: true

# config/packages/prod/app_dev_panel.yaml
app_dev_panel:
    enabled: false

# config/packages/test/app_dev_panel.yaml
app_dev_panel:
    enabled: true
    storage:
        history_size: 10
```

## Required Dependencies per Collector

| Collector | Required Package | Auto-detected |
|-----------|-----------------|---------------|
| `doctrine` | `doctrine/dbal` | No — set `doctrine: false` if not installed |
| `twig` | `twig/twig` | No — set `twig: false` if not installed |
| `security` | `symfony/security-bundle` | No — set `security: false` if not installed |
| `mailer` | `symfony/mailer` | No — set `mailer: false` if not installed |
| `messenger` | `symfony/messenger` | No — set `messenger: false` if not installed |
| All others | Included with adapter | Always available |

## Database Inspector

When `doctrine/dbal` is installed and `doctrine.dbal.default_connection` is available in the container, `DoctrineSchemaProvider` is auto-registered. It provides table listing and record browsing via the `/inspect/api/table` endpoint. Otherwise, `NullSchemaProvider` is used (returns empty data).

## Storage Directory

The adapter stores debug data as JSON files in the configured `storage.path`:

```
var/debug/
├── 2026-03-17/
│   ├── abc123def456/
│   │   ├── summary.json    # Debug ID, collector names, request/command metadata
│   │   ├── data.json       # Full collector payloads (keyed by collector FQCN)
│   │   └── objects.json    # Serialized PHP objects
│   └── ghi789jkl012/
│       └── ...
```

Ensure the directory is writable by the web server. Add `var/debug/` to `.gitignore`.

## API Routing

The `AdpApiController` routes all `/debug/api/*` and `/inspect/api/*` requests to `ApiApplication`. Register routes via:

```php
// config/routes/app_dev_panel.php
use AppDevPanel\Adapter\Symfony\Controller\AdpApiController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->add('app_dev_panel', '/{path}')
        ->controller([AdpApiController::class, 'handle'])
        ->requirements(['path' => '(?:debug|inspect)/api/.*']);
};
```
