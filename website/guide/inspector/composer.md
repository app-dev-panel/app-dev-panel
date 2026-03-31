---
title: Composer Inspector
---

# Composer Inspector

Browse installed packages, inspect package details, and install new dependencies from the panel.

![Composer Inspector](/images/inspector/composer.png)

## Tabs

### Packages

Lists all installed Composer packages with version info. Shows both `require` and `require-dev` dependencies. Click **Switch** to toggle between production and development packages.

### composer.json

View the raw `composer.json` contents.

### composer.lock

View the raw `composer.lock` contents (if present).

## Package Inspection

Click a package to see detailed info via `composer show --all --format=json`:
- Description, homepage, license
- All available versions
- Dependencies and conflicts
- Installation source

## Install Packages

Install new packages directly from the panel:
- Specify package name and optional version constraint
- Choose between `--dev` and production dependency
- Runs `composer require` in non-interactive mode

## API Endpoints

| Method | Path | Description |
|--------|------|-------------|
| GET | `/inspect/api/composer` | Get composer.json and composer.lock |
| GET | `/inspect/api/composer/inspect?package=vendor/name` | Package details |
| POST | `/inspect/api/composer/require` | Install a package |

**Install request body:**
```json
{
    "package": "vendor/package-name",
    "version": "^2.0",
    "isDev": false
}
```

::: warning
Package installation modifies `composer.json` and `composer.lock`. This runs `composer require` on the server.
:::
