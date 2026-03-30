---
title: CI & Tooling
---

# CI & Tooling

ADP uses GitHub Actions for continuous integration and [Mago](https://mago.carthage.software/) for PHP code quality enforcement.

## Mago — PHP Toolchain

Mago is a Rust-powered toolchain that replaces PHPStan, PHP-CS-Fixer, and Psalm with a single binary. It provides three tools:

| Tool | Purpose | Command |
|------|---------|---------|
| **Formatter** | Enforces PSR-12 code style | `make mago-format` |
| **Linter** | Finds code smells, inconsistencies, anti-patterns | `make mago-lint` |
| **Analyzer** | Static analysis: type errors, null safety, logic bugs | `make mago-analyze` |

### Running Mago

```bash
make mago                 # Run all checks (format + lint + analyze)
make mago-fix             # Fix formatting, then lint + analyze
make mago-playgrounds     # Check all playground apps
make mago-playgrounds-fix # Fix formatting in playground apps
```

### Configuration

Mago is configured via `mago.toml` in the project root:

```toml
[source]
paths = ["libs/Kernel/src", "libs/API/src", ...]
includes = ["vendor"]     # Parsed for type info only

[formatter]
preset = "psr-12"

[linter]
default-level = "warning"
```

### Baselines

Existing lint issues in legacy code are suppressed via `mago-lint-baseline.php`. The analyzer has no baseline — rules that produce false positives are suppressed via `ignore` in `mago.toml`. New code must not introduce new issues.

```bash
composer lint:baseline    # Regenerate lint baseline
```

## PHPUnit — Testing

Tests are organized per-module with a unified root `phpunit.xml.dist`:

```bash
make test-php             # Run all PHP tests
```

### Test Suites

| Suite | Directory |
|-------|-----------|
| Kernel | `libs/Kernel/tests` |
| API | `libs/API/tests` |
| Cli | `libs/Cli/tests` |
| Adapter/Symfony | `libs/Adapter/Symfony/tests` |
| Adapter/Laravel | `libs/Adapter/Laravel/tests` |
| Adapter/Yii2 | `libs/Adapter/Yii2/tests` |
| Adapter/Cycle | `libs/Adapter/Cycle/tests` |
| McpServer | `libs/McpServer/tests` |

### Coverage

Coverage requires the PCOV extension:

```bash
pecl install pcov
php vendor/bin/phpunit --coverage-text          # Text summary
php vendor/bin/phpunit --coverage-html=coverage  # HTML report
```

## Frontend Checks

```bash
make frontend-check       # Prettier + ESLint
make frontend-fix         # Auto-fix issues
make test-frontend        # Vitest unit tests
make test-frontend-e2e    # Browser tests (requires Chrome)
```

## GitHub Actions

### CI Workflow (`ci.yml`)

Runs on every push and PR.

**Test matrix:**

| OS | PHP 8.4 | PHP 8.5 |
|----|:-------:|:-------:|
| Linux | ✅ | ✅ |
| Windows | ✅ | ✅ |

**Mago checks** run as separate parallel jobs:
- `mago fmt --check`
- `mago lint --reporting-format=github` (annotates PR files)
- `mago analyze --reporting-format=github` (annotates PR files)

### PR Report (`pr-report.yml`)

Runs on PRs only. Posts two comments:

1. **Coverage report** — Code coverage summary from PHPUnit
2. **Mago report** — Pass/fail status for format, lint, analyze with expandable output on failure

## Full Pipeline

Run the complete CI pipeline locally:

```bash
make ci                   # Full CI: all checks + all tests
make check                # Checks only (Mago + frontend)
make test                 # Tests only (PHP + frontend)
make all                  # Same as make check && make test
```

## Adding a New Library

When adding a new library under `libs/`:

1. Add its `src/` and `tests/` paths to `mago.toml` under `[source] paths`
2. Add its test directory to `phpunit.xml.dist` as a new `<testsuite>`
3. Add its `src/` directory to the `<source><include>` section of `phpunit.xml.dist`
