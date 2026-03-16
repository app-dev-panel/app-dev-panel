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
        # Supports Symfony parameter references.
        path: '%kernel.project_dir%/var/debug'

        # Maximum number of debug entries to retain.
        # Oldest entries are garbage-collected on flush.
        history_size: 50

    collectors:
        # Kernel collectors (framework-agnostic)
        request: true              # HTTP request/response
        exception: true            # Uncaught exceptions
        log: true                  # PSR-3 log messages
        event: true                # PSR-14 dispatched events
        service: true              # DI service method calls
        http_client: true          # PSR-18 HTTP client requests
        timeline: true             # Cross-collector timeline
        var_dumper: true           # dump() calls
        filesystem_stream: true    # File I/O operations
        http_stream: true          # Stream HTTP operations
        command: true              # Console commands

        # Symfony-specific collectors
        doctrine: true             # Doctrine DBAL queries (requires doctrine/dbal)
        twig: true                 # Twig template renders (requires twig/twig)
        security: true             # Security component (requires symfony/security-bundle)
        cache: true                # Cache operations
        mailer: true               # Sent emails (requires symfony/mailer)
        messenger: true            # Message bus (requires symfony/messenger)

    # URL patterns to skip (wildcard matching).
    # Matching requests will not generate debug entries.
    ignored_requests:
        - '/_wdt/*'
        - '/_profiler/*'
        - '/debug/api/*'

    # Command name patterns to skip.
    ignored_commands:
        - 'completion'
        - 'help'
        - 'list'
        - 'debug:*'

    dumper:
        # Fully-qualified class names to exclude from object dumps.
        # Useful for large/circular objects that slow down serialization.
        excluded_classes: []
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

## Storage Directory

The adapter stores debug data as JSON files in the configured `storage.path`:

```
var/debug/
├── 2026-03-16/
│   ├── abc123/
│   │   ├── summary.json    # Request metadata, collector names
│   │   ├── data.json       # Full collector payloads
│   │   └── objects.json    # Serialized PHP objects
│   └── def456/
│       ├── summary.json
│       ├── data.json
│       └── objects.json
```

Ensure the directory is writable by the web server. Add `var/debug/` to `.gitignore`.
