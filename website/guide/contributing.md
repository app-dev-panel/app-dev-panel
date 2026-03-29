---
title: Contributing
---

# Contributing

ADP is a monorepo containing PHP backend libraries and a React/TypeScript frontend. This guide covers setting up your development environment and running checks.

## Prerequisites

- PHP 8.4+
- Node.js 18+ and npm
- Composer

## Installation

```bash
make install              # Install ALL dependencies (PHP + frontend + playgrounds)
```

Or install selectively:

```bash
make install-php          # Composer install (root)
make install-frontend     # npm install (libs/frontend)
make install-playgrounds  # Composer install for each playground app
```

## Running Tests

```bash
make test                 # Run ALL tests in parallel (PHP + frontend)
make test-php             # PHP unit tests (PHPUnit)
make test-frontend        # Frontend unit tests (Vitest)
make test-frontend-e2e    # Frontend browser tests (requires Chrome)
```

For PHP coverage reports, install the PCOV extension:

```bash
pecl install pcov
php vendor/bin/phpunit --coverage-text
```

## Code Quality

ADP uses [Mago](https://mago.carthage.software/) for PHP (formatting, linting, static analysis) and Prettier + ESLint for TypeScript.

```bash
make check                # Run ALL code quality checks
make fix                  # Fix all auto-fixable issues

# PHP only
make mago                 # Check formatting + lint + analyze
make mago-fix             # Fix formatting, then lint + analyze

# Frontend only
make frontend-check       # Prettier + ESLint
make frontend-fix         # Fix frontend issues
```

## Development Workflow

1. **Write code** and tests for your changes
2. **Run checks**: `make fix && make test`
3. **Verify everything**: `make all` (checks + tests combined)

All checks must pass before submitting a pull request.

## Project Structure

| Directory | Contents |
|-----------|----------|
| `libs/Kernel/` | Core engine: debugger, collectors, storage, proxies |
| `libs/API/` | HTTP API: REST endpoints, SSE, middleware |
| `libs/McpServer/` | MCP server for AI assistant integration |
| `libs/Cli/` | CLI commands |
| `libs/Adapter/` | Framework adapters (Yii 3, Symfony, Laravel, Yii 2, Cycle) |
| `libs/frontend/` | React frontend (panel, toolbar, SDK packages) |
| `playground/` | Demo applications per framework |

## CI Pipeline

GitHub Actions runs on every push and PR:

- PHP tests on PHP 8.4 and 8.5 (Linux + Windows)
- Mago format, lint, and static analysis
- Frontend checks and tests
- Coverage reports posted as PR comments

Run the full CI pipeline locally:

```bash
make ci
```
