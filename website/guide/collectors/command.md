---
title: Command Collector
description: "ADP Command Collector captures console command executions with name, arguments, exit code, and timing."
---

# Command Collector

Captures console command executions — command name, input/output, arguments, options, exit code, and errors.

## What It Captures

| Field | Description |
|-------|-------------|
| `name` | Command name |
| `command` | Command object |
| `input` | Command input string |
| `output` | Command output |
| `exitCode` | Process exit code |
| `error` | Error message if command failed |
| `arguments` | Command arguments |
| `options` | Command options |

## Data Schema

```json
{
    "command": {
        "name": "app:import-users",
        "class": "App\\Command\\ImportUsersCommand",
        "input": "app:import-users --force",
        "output": "Imported 42 users.",
        "exitCode": 0,
        "error": null,
        "arguments": {},
        "options": {"force": true}
    }
}
```

**Summary** (shown in debug entry list):

```json
{
    "command": {
        "name": "app:import-users",
        "class": "App\\Command\\ImportUsersCommand",
        "input": "app:import-users --force",
        "exitCode": 0
    }
}
```

## Contract

```php
use AppDevPanel\Kernel\Collector\Console\CommandCollector;

// Collect from Symfony Console events
$collector->collect(event: $consoleEvent);

// Or collect raw command data
$collector->collectCommandData([
    'name' => 'app:import-users',
    'input' => 'app:import-users --force',
    'exitCode' => 0,
]);
```

::: info
<class>\AppDevPanel\Kernel\Collector\Console\CommandCollector</class> implements <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class> and depends on <class>\AppDevPanel\Kernel\Collector\TimelineCollector</class>. Located in the `Console` sub-namespace.
:::

## How It Works

Framework adapters hook into console event lifecycle:
- **Symfony**: `ConsoleCommandEvent`, `ConsoleTerminateEvent`, `ConsoleErrorEvent`
- **Laravel**: Artisan command events
- **Yii 3**: Console application events

## Debug Panel

- **Command details** — name, class, input, and exit code
- **Output capture** — full command output
- **Error display** — error message and trace for failed commands
