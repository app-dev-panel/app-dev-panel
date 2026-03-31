---
title: Commands
---

# Commands

Run application commands directly from the debug panel — tests, static analysis, and composer scripts.

![Commands](/images/inspector/commands.png)

## Available Command Types

| Type | Description |
|------|-------------|
| PHPUnit | Run unit tests with JSON-formatted output |
| Codeception | Run Codeception tests with JSON reporter |
| Psalm | Run Psalm static analysis with JSON report |
| Composer scripts | All scripts from `composer.json` (auto-discovered) |
| Bash | Execute arbitrary shell commands |

## How It Works

Commands are automatically discovered from two sources:

1. **Registered commands** — PHPUnit, Codeception, Psalm (if configured in the adapter)
2. **Composer scripts** — All `scripts` entries from `composer.json` are exposed as `composer/{scriptName}` commands

Click a command button to execute it. Output is displayed in real-time.

## API Endpoints

| Method | Path | Description |
|--------|------|-------------|
| GET | `/inspect/api/command` | List available commands |
| POST | `/inspect/api/command?command=composer/test` | Execute a command |

**Response format:**
```json
{
    "status": "ok",
    "result": "PHPUnit 11.0.0 ...\nOK (42 tests, 100 assertions)",
    "error": ""
}
```

::: tip
PHPUnit and Codeception commands use custom JSON reporters for structured output in the panel.
:::
