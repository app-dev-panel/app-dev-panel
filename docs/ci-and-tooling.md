# CI/CD and Tooling

## Overview

ADP uses GitHub Actions for continuous integration and [Mago](https://mago.carthage.software/)
for code quality enforcement.

## Mago — PHP Toolchain

Mago is a Rust-powered toolchain that replaces PHPStan, PHP-CS-Fixer, and Psalm with a single
binary. It provides three tools:

| Tool | Purpose | Command |
|------|---------|---------|
| **Formatter** | Enforces PSR-12 code style | `composer format:check` / `composer format:fix` |
| **Linter** | Finds code smells, inconsistencies, anti-patterns | `composer lint` |
| **Analyzer** | Static analysis: type errors, null safety, logic bugs | `composer analyze` |

### Configuration

File: `mago.toml` (project root)

```toml
[source]
paths = ["libs/Kernel/src", "libs/API/src", ...]
includes = ["vendor"]     # Parsed for type info only

[formatter]
preset = "psr-12"

[linter]
default-level = "warning"
```

### Composer Scripts

| Script | Description |
|--------|-------------|
| `composer format:check` | Check formatting without modifying files |
| `composer format:fix` | Auto-fix formatting |
| `composer lint` | Run linter |
| `composer analyze` | Run static analyzer |
| `composer check` | Run all three checks |
| `composer fix` | Fix formatting, then run lint + analyze |

## PHPUnit — Testing

Tests are organized per-module with a unified root `phpunit.xml.dist`:

```bash
composer test              # Run all tests
composer test:coverage     # Run with coverage (clover XML)
```

### Test Suites

| Suite | Directory |
|-------|-----------|
| Kernel | `libs/Kernel/tests` |
| API | `libs/API/tests` |
| Cli | `libs/Cli/tests` |

Coverage report is generated as `coverage.xml` (Clover format).

## GitHub Actions Workflows

### CI (`ci.yml`)

Runs on every push to `main`/`master` and on PRs.

**Test matrix:**

| OS | PHP 8.4 | PHP 8.5 |
|----|---------|---------|
| Linux (ubuntu-latest) | Yes | Yes |
| Windows (windows-latest) | Yes | Yes |

**Mago checks** run in parallel as separate jobs:
- `mago fmt --check`
- `mago lint --reporting-format=github` (annotates PR files)
- `mago analyze --reporting-format=github` (annotates PR files)

### PR Report (`pr-report.yml`)

Runs on PRs only. Posts two comments:

1. **Coverage report** — Code coverage summary from PHPUnit
2. **Mago report** — Table showing pass/fail status for format, lint, analyze
   with expandable output details on failure

## Development Workflow

### Required checks before completing a feature

```bash
# 1. Fix code style
composer format:fix

# 2. Run linter and analyzer
composer lint
composer analyze

# 3. Run tests
composer test

# Or use shortcuts:
composer fix     # Steps 1+2
composer test    # Step 3
```

All checks must be green. CI will block merging if any check fails.

### Adding new source paths

If you add a new library under `libs/`:

1. Add its `src/` and `tests/` paths to `mago.toml` under `[source] paths`
2. Add its test directory to `phpunit.xml.dist` as a new `<testsuite>`
3. Add its `src/` directory to the `<source><include>` section of `phpunit.xml.dist`
