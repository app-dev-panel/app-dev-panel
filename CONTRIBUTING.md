# Contributing to ADP

Thank you for considering contributing to ADP (Application Development Panel)! This guide will help you get started.

## Code of Conduct

Be respectful, constructive, and inclusive. We're building developer tools together — keep discussions focused on the work.

## Getting Started

### Prerequisites

- **PHP 8.4+**
- **Node.js 18+** (for frontend)
- **Composer 2**
- **npm** (comes with Node.js)

### Setup

```bash
git clone https://github.com/app-dev-panel/app-dev-panel.git
cd app-dev-panel
make install          # Install all deps (PHP + frontend + playgrounds)
make all              # Run checks + tests — verify everything works
```

## Development Workflow

1. **Fork** the repo and create a branch from `master`
2. **Name your branch** descriptively: `fix/log-collector-crash`, `feature/redis-storage`, `docs/api-endpoints`
3. **Make changes** — keep commits focused and atomic
4. **Run the full pipeline** before pushing:

```bash
make fix              # Auto-fix formatting (PHP + frontend)
make all              # Run all checks + all tests
```

5. **Open a Pull Request** against `master`

## Running Tests

```bash
# All tests (recommended before PR)
make test                     # PHP + frontend in parallel

# Granular
make test-php                 # PHPUnit tests
make test-frontend            # Vitest tests
make test-frontend-e2e        # Browser tests (requires Chrome)
```

### PHP Coverage

Line coverage requires the PCOV extension:

```bash
pecl install pcov
php vendor/bin/phpunit --coverage-text
```

### E2E / Fixture Tests

Fixture tests run against playground servers:

```bash
make serve                    # Start all playground servers (background)
make test-fixtures            # PHPUnit E2E against all playgrounds
```

## Code Quality

### PHP — Mago

ADP uses [Mago](https://mago.carthage.software/) for formatting, linting, and static analysis:

```bash
make mago-fix                 # Fix formatting + run lint + analyze
make mago                     # Check only (no changes)
```

All new PHP code must pass `make mago` with zero issues. The linter has a baseline for legacy code (`mago-lint-baseline.php`), but the analyzer has no baseline — new code must be clean.

### Frontend — Prettier + ESLint

```bash
make frontend-fix             # Fix Prettier + ESLint issues
make frontend-check           # Check only
```

### Playgrounds

```bash
make mago-playgrounds-fix     # Fix all playground formatting
make mago-playgrounds         # Check all playgrounds
```

## Project Structure

```
libs/
├── Kernel/             # Core engine (collectors, storage, debugger lifecycle)
├── API/                # HTTP REST + SSE endpoints
├── Cli/                # CLI commands
├── Testing/            # Test fixtures and runner
├── Adapter/
│   ├── Yii3/           # Yii 3 adapter
│   ├── Symfony/        # Symfony adapter
│   ├── Laravel/        # Laravel adapter
│   ├── Yii2/           # Yii 2 adapter
│   └── Cycle/          # Cycle ORM adapter
└── frontend/
    └── packages/
        ├── panel/      # Main SPA (debug panel)
        ├── toolbar/    # Embeddable toolbar widget
        └── sdk/        # Shared SDK (components, API clients)
```

Each module under `libs/` has its own `CLAUDE.md` with detailed internals.

## Architecture Rules

ADP follows a strict layered architecture: **Frontend → API → Kernel → Adapter → Target App**

- **Kernel** has zero framework dependencies — it must stay framework-agnostic
- **API** depends on Kernel only, never on Adapters
- **Adapters** wire Kernel collectors into a specific framework via proxies
- **Frontend** communicates only via the HTTP API

Do not introduce cross-layer dependencies. Run `/review-arch` if unsure.

## Coding Conventions

### PHP

- **PER-CS (PER-2)** coding style, enforced by Mago
- `declare(strict_types=1)` in every file
- `final` classes where possible
- All collector classes implement `CollectorInterface`
- PSR standards: PSR-3 (logging), PSR-7 (HTTP messages), PSR-11 (containers), PSR-15 (middleware)

### TypeScript

- **Prettier** 3.8+ — single quotes, trailing commas, 120 char width, `objectWrap: collapse`
- **ESLint 9** with `@typescript-eslint`
- Strict TypeScript mode
- Functional React components
- Redux Toolkit for state management

### General

- All docs, comments, and commit messages in **English**
- API responses follow `{id, data, error, success, status}` format
- No unnecessary abstractions — three similar lines beat a premature helper

## What to Contribute

### Good First Issues

Look for issues labeled `good first issue` on GitHub.

### High-Impact Areas

- **New framework adapters** — Wire ADP into frameworks beyond Yii/Symfony/Laravel
- **New collectors** — Add data collection for cache, queue, mail, filesystem, etc.
- **Frontend improvements** — Better visualizations, UX enhancements, accessibility
- **Documentation** — Usage guides, examples, API docs
- **Test coverage** — Kernel is at 85%, API at 76%, other modules lower

### Adding a New Collector

1. Create a class implementing `CollectorInterface` in `libs/Kernel`
2. Add corresponding API endpoint in `libs/API` if needed
3. Add frontend page/component in `libs/frontend/packages/panel`
4. Wire it into each adapter that supports it
5. Add tests at each layer

### Adding a New Adapter

1. Create a new directory under `libs/Adapter/`
2. Implement proxy wiring for the framework's PSR interfaces
3. Register collectors via the framework's DI container
4. Add a playground app under `playground/` for manual testing
5. Add `CLAUDE.md` documenting the adapter internals

## Pull Request Guidelines

- **Keep PRs focused** — one feature or fix per PR
- **Write tests** for all new/modified code
- **Update docs** if behavior changes
- **All CI checks must pass** — the PR pipeline runs Mago, ESLint, Prettier, PHPUnit, and Vitest
- **Describe the "why"** in your PR description, not just the "what"
- **Link related issues** using `Fixes #123` or `Closes #123`

## CI Pipeline

GitHub Actions runs automatically on every PR:

- PHP tests (matrix: PHP 8.4 + 8.5, Linux + Windows)
- Mago (format + lint + analyze)
- Frontend checks (Prettier + ESLint + Vitest)
- Coverage report posted as PR comment

## License

By contributing, you agree that your contributions will be licensed under the [MIT License](https://opensource.org/licenses/MIT).

## Questions?

Open a [GitHub Discussion](https://github.com/app-dev-panel/app-dev-panel/discussions) or an issue. We're happy to help!
