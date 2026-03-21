# Getting Started

## Prerequisites

- PHP 8.4+
- Composer 2.x
- Node.js 18+ and npm 9+
- Docker (optional, for containerized development)

## Installation

### 1. Clone and Install Dependencies

```bash
git clone <repository-url>
cd app-dev-panel

# Install everything (PHP + frontend + playgrounds)
make install

# Or install selectively:
make install-php           # PHP dependencies only
make install-frontend      # Frontend dependencies only
make install-playgrounds   # Playground dependencies only
```

### 2. Start the Demo Application

```bash
# Start all playground servers in background
make serve

# Or start individually (separate terminals)
make serve-yiisoft    # Yii 3 on port 8101
make serve-symfony    # Symfony on port 8102
make serve-yii2       # Yii 2 on port 8103

# Start the frontend dev server
cd libs/frontend
npm start
```

### 3. Using Docker

```bash
cd libs/frontend
docker-compose up
```

## Integrating ADP into Your Application

### Yii 3 (Yiisoft)

```bash
composer require app-dev-panel/kernel app-dev-panel/api app-dev-panel/adapter-yiisoft
```

The Yii config plugin auto-registers the debug panel. No manual configuration needed.
Access the debug API at `http://your-app/debug/api/`.

### Symfony

```bash
composer require app-dev-panel/kernel app-dev-panel/api app-dev-panel/adapter-symfony
```

Register the bundle in `config/bundles.php` and configure in `config/packages/app_dev_panel.yaml`.
See `libs/Adapter/Symfony/CLAUDE.md` for full configuration reference.

### Yii 2

```bash
composer require app-dev-panel/kernel app-dev-panel/api app-dev-panel/adapter-yii2
```

The adapter auto-registers via `BootstrapInterface`. Configure the module in application config `modules` section.
See `libs/Adapter/Yii2/CLAUDE.md` for full configuration reference.

### Cycle ORM (Database Schema Only)

```bash
composer require app-dev-panel/api app-dev-panel/adapter-cycle
```

Provides `CycleSchemaProvider` for database schema inspection. No Kernel dependency — only wires into the API's `SchemaProviderInterface`.

### Other Frameworks

To integrate ADP with a different framework, create an adapter that:

1. Registers Kernel proxy classes as service decorators in your DI container
2. Hooks into application lifecycle events (startup/shutdown)
3. Configures which collectors are active
4. Explicitly registers all inspector controllers in DI
5. Registers API routes for `/debug/api/*` and `/inspect/api/*`
6. Optionally depends on `app-dev-panel/cli` for CLI commands (`debug:reset`, `debug:server`, etc.)

See `libs/Adapter/Symfony/` and `libs/Adapter/Yiisoft/` for reference implementations.
See `docs/architectural-constraints.md` for dependency rules.

## PHP Built-in Server

When using PHP's built-in server (`php -S`), always set `PHP_CLI_SERVER_WORKERS=3` (or higher).
ADP's frontend makes concurrent API requests (SSE + data fetching); the default single-worker
mode cannot handle them in parallel, causing timeouts.

```bash
PHP_CLI_SERVER_WORKERS=3 php -S 127.0.0.1:8080 -t public
```

## Project Structure

| Directory | Description |
|-----------|-------------|
| `playground/yiisoft-app/` | Yii 3 demo app (port 8101) |
| `playground/symfony-basic-app/` | Symfony demo app (port 8102) |
| `playground/yii2-basic-app/` | Yii 2 demo app (port 8103) |
| `libs/Kernel/` | Core debugging engine |
| `libs/API/` | REST API + SSE endpoints |
| `libs/Cli/` | CLI commands (debug server, reset, broadcast, query) |
| `libs/Testing/` | Test fixture definitions, runner, CLI command |
| `libs/Adapter/Yiisoft/` | Yii 3 framework adapter |
| `libs/Adapter/Symfony/` | Symfony framework adapter |
| `libs/Adapter/Yii2/` | Yii 2 framework adapter |
| `libs/Adapter/Cycle/` | Cycle ORM adapter (database schema only) |
| `libs/frontend/` | Frontend monorepo (panel, toolbar, SDK) |
| `docs/` | Global documentation |

## Running Tests

```bash
# Run ALL tests in parallel (PHP + frontend)
make test

# PHP tests only
make test-php

# Frontend tests only
make test-frontend

# PHP coverage (requires PCOV extension)
php vendor/bin/phpunit --coverage-text
php vendor/bin/phpunit --coverage-html=coverage
```

## Code Quality (Mago)

ADP uses [Mago](https://mago.carthage.software/) — a blazing-fast PHP toolchain written in Rust that
combines a linter, formatter, and static analyzer in one binary.

### Available Commands

```bash
# Fix all code (PHP core + playgrounds + frontend)
make fix

# Check all code (PHP core + playgrounds + frontend)
make check

# Granular — core libs only
make mago                # Check formatting + lint + analyze
make mago-fix            # Fix formatting, then lint + analyze

# Granular — playgrounds only
make mago-playgrounds      # Check all playgrounds
make mago-playgrounds-fix  # Fix all playgrounds

# Granular — frontend only
make frontend-check      # Prettier + ESLint
make frontend-fix        # Fix frontend issues
```

### Development Workflow

After completing any feature or bugfix:

```bash
make fix       # Fix all code
make test      # Run all tests
```

All checks must pass before the feature is considered complete. CI enforces this on PRs.

## CI/CD

GitHub Actions runs on every push and PR:

- **Test matrix**: PHP 8.4 and 8.5 on Linux and Windows
- **Mago checks**: Format, lint, and analyze as separate jobs
- **PR comments**: Code coverage report and Mago results posted directly to the PR

## Frontend Tests

```bash
cd libs/frontend && npm test
```
