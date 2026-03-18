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

# PHP dependencies
composer install

# Frontend dependencies
cd libs/app-dev-panel
npm install
```

### 2. Start the Demo Application

```bash
# Start the PHP backend (from project root)
cd app
PHP_CLI_SERVER_WORKERS=3 php -S 0.0.0.0:8080 -t public

# Start the frontend dev server (from libs/app-dev-panel)
cd libs/app-dev-panel
npm run dev
```

### 3. Using Docker

```bash
cd libs/app-dev-panel
docker-compose up
```

## Integrating ADP into Your Application

### Yii 3 (First-Party Adapter)

1. Install the packages:

```bash
composer require app-dev-panel/kernel app-dev-panel/api app-dev-panel/adapter-yiisoft
```

2. The Yii config plugin will auto-register the debug panel. No manual configuration needed.

3. Access the debug panel at `http://your-app/debug/api/` (API) or via the frontend SPA.

### Symfony

```bash
composer require app-dev-panel/kernel app-dev-panel/api app-dev-panel/adapter-symfony
```

Register the bundle in `config/bundles.php` and configure in `config/packages/app_dev_panel.yaml`.

### Yii 2

```bash
composer require app-dev-panel/kernel app-dev-panel/api app-dev-panel/adapter-yii2
```

The adapter auto-registers via `BootstrapInterface`. Configure in application config `modules` section.

### Other Frameworks

To integrate ADP with a different framework, you need to create an adapter that:

1. Registers Kernel proxy classes as service decorators in your DI container
2. Hooks into application lifecycle events (startup/shutdown)
3. Configures which collectors are active
4. Explicitly registers all inspector controllers in DI (see existing adapters)

See `libs/Adapter/Yiisoft/`, `libs/Adapter/Symfony/`, `libs/Adapter/Yii2/` for reference implementations.

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
| `app/` | Demo PHP application |
| `libs/Kernel/` | Core debugging engine |
| `libs/API/` | REST API + SSE endpoints |
| `libs/Cli/` | CLI commands |
| `libs/Adapter/Yiisoft/` | Yii 3 framework adapter |
| `libs/app-dev-panel/` | Frontend (React SPA + toolbar + SDK) |
| `docs/` | Global documentation |

## Running Tests

```bash
# Run all backend tests from project root
composer test

# Run with code coverage
composer test:coverage
```

## Code Quality (Mago)

ADP uses [Mago](https://mago.carthage.software/) — a blazing-fast PHP toolchain written in Rust that
combines a linter, formatter, and static analyzer in one binary.

### Available Commands

```bash
# Check code formatting (dry-run, no changes)
composer format:check

# Fix code formatting
composer format:fix

# Run linter (find code smells, inconsistencies)
composer lint

# Run static analyzer (find type errors, logic bugs)
composer analyze

# Run all checks at once
composer check

# Fix formatting, then run lint + analyze
composer fix
```

### Configuration

Mago is configured via `mago.toml` in the project root. Key settings:

- **Source paths**: `libs/Kernel/src`, `libs/API/src`, `libs/Cli/src`, `libs/Adapter/Yiisoft/src` + their tests
- **Formatter preset**: PSR-12
- **Vendor included**: For type information resolution

### Development Workflow

After completing any feature or bugfix:

```bash
composer fix       # Fix formatting + run linter + analyzer
composer test      # Run all tests
```

All checks must pass before the feature is considered complete. CI enforces this on PRs.

## CI/CD

GitHub Actions automatically runs on every PR:

- **Test matrix**: PHP 8.4 and 8.5 on Linux and Windows
- **Mago checks**: Format, lint, and analyze as separate jobs
- **PR comments**: Code coverage report and Mago results posted directly to the PR

## Frontend Tests

```bash
cd libs/app-dev-panel && npm test
```
