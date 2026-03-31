---
title: Environment Collector
---

# Environment Collector

Collects runtime environment information — PHP version, extensions, OS details, Git branch, server parameters, and environment variables.

![Environment Collector panel](/images/collectors/environment.png)

## What It Captures

| Section | Fields |
|---------|--------|
| **PHP** | version, SAPI, binary path, extensions, xdebug/opcache/pcov status, INI settings |
| **OS** | family, name, uname, hostname |
| **Git** | branch, commit hash (short and full) |
| **Server** | `$_SERVER` parameters |
| **Env** | Environment variables |

## Data Schema

```json
{
    "php": {
        "version": "8.4.1",
        "sapi": "cli-server",
        "binary": "/usr/bin/php",
        "os": "Linux",
        "extensions": ["pdo", "mbstring", "json", "..."],
        "xdebug": false,
        "opcache": "8.4.1",
        "pcov": false,
        "ini": {
            "memory_limit": "256M",
            "max_execution_time": "30",
            "display_errors": "1",
            "error_reporting": 32767
        },
        "zend_extensions": ["Zend OPcache"]
    },
    "os": {
        "family": "Linux",
        "name": "Ubuntu 24.04",
        "uname": "Linux hostname 6.5.0 ...",
        "hostname": "app-server"
    },
    "git": {
        "branch": "main",
        "commit": "a1b2c3d",
        "commitFull": "a1b2c3d4e5f6..."
    },
    "server": {...},
    "env": {...}
}
```

**Summary** (shown in debug entry list):

```json
{
    "environment": {
        "php": {"version": "8.4.1", "sapi": "cli-server"},
        "os": "Linux",
        "git": {"branch": "main", "commit": "a1b2c3d"}
    }
}
```

## Contract

```php
use AppDevPanel\Kernel\Collector\EnvironmentCollector;

// Collect from PSR-7 request
$collector->collectFromRequest(request: $serverRequest);

// Or collect from PHP globals
$collector->collectFromGlobals();
```

::: info
`EnvironmentCollector` implements `SummaryCollectorInterface`. It has no dependencies on other collectors.
:::

## How It Works

The collector reads PHP runtime information via `phpversion()`, `php_sapi_name()`, `get_loaded_extensions()`, INI values, and `php_uname()`. Git information is obtained via shell commands (`git rev-parse`, `git branch`). Server parameters come from the PSR-7 request or `$_SERVER`.

## Debug Panel

- **PHP info** — version, SAPI, extensions, INI settings in a structured view
- **OS info** — operating system family and version
- **Git info** — current branch and commit hash
- **Server/Env tabs** — filterable key-value tables for server params and env vars
