---
title: Contributing
---

# Contributing

ADP is a monorepo containing PHP backend libraries and a React/TypeScript frontend. This guide covers setting up your development environment, code conventions, and how to add new components.

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

### Mago Baselines

Existing lint issues in legacy code are suppressed via a baseline file. The analyzer has no baseline — rules that produce false positives are suppressed via `ignore` in `mago.toml`. New code must not introduce new issues.

```bash
composer lint:baseline    # Regenerate lint baseline after fixing existing issues
```

## Code Style

### PHP

- **PER-CS (PER-2)** via [Mago](https://mago.carthage.software/)
- `declare(strict_types=1)` in every file
- `final class` by default
- PSR interfaces for all abstractions

### TypeScript

- **Prettier 3.8+**: single quotes, trailing commas, 120 char width, 4-space indent, `objectWrap: "collapse"`
- **ESLint 9** with `@typescript-eslint`
- `type` over `interface` (`consistent-type-definitions: "type"`)
- Functional React components, Redux Toolkit patterns

## Module Dependencies

Strict dependency rules ensure framework-agnosticism:

```
Adapter → API → Kernel
  │               ↑
  └───────────────┘
Cli → Kernel
Frontend → API (HTTP only)
```

| Module | Can depend on | Cannot depend on |
|--------|--------------|-----------------|
| Kernel | PSR interfaces, generic PHP libs | API, Cli, Adapter |
| API | Kernel, PSR interfaces | Adapter, Cli |
| Cli | Kernel, Symfony Console | API, Adapter |
| Adapter | Kernel, API, framework packages | Other adapters |

## Testing Conventions

- One test class per source class: `src/Foo/Bar.php` → `tests/Unit/Foo/BarTest.php`
- Inline mocks only (`$this->createMock()`, anonymous classes)
- No shared test utilities, no test environment classes
- `assertSame()` over `assertEquals()`
- Data providers via `#[DataProvider('name')]` attribute
- Collectors extend `AbstractCollectorTestCase`

## Development Workflow

1. Create a feature branch
2. Write code and tests for your changes
3. Run checks: `make fix && make test`
4. Verify everything: `make all` (checks + tests combined)
5. Submit a pull request

All checks must pass before merging.

## Adding a Collector

1. Create a class implementing `CollectorInterface` in `libs/Kernel/src/Collector/`
2. Implement `startup()`, `shutdown()`, `getCollected()`
3. Optionally implement `SummaryCollectorInterface` for entry list metadata
4. Write a test extending `AbstractCollectorTestCase`
5. Register in adapter configs (e.g., `libs/Adapter/Yiisoft/config/params.php`)

See [Collectors](/guide/collectors) for the interface contract.

## Adding an Inspector Page

### Backend

1. Create a controller in `libs/API/src/Inspector/Controller/`
2. Add a route in `libs/API/config/routes.php`
3. Write a controller test extending `ControllerTestCase`

### Frontend

1. Create a page component in `packages/panel/src/Module/Inspector/Pages/`
2. Add an RTK Query endpoint in `packages/panel/src/Module/Inspector/API/`
3. Add a route to the inspector module's route config

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
