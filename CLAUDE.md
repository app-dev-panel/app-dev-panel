# ADP — Application Development Panel

## Project Overview

ADP (Application Development Panel) is a **framework-agnostic, language-agnostic** debugging and development panel.
It collects runtime data (logs, events, requests, exceptions, database queries, etc.) from applications and provides
a web UI to inspect, analyze, and debug them.

The project is currently a fork/consolidation from Yii Debug into a single monorepo, with the goal of becoming
fully framework-independent. The first adapter targets Yii 3; additional adapters (Symfony, Laravel, etc.) will follow.

## Tech Stack

- **Backend**: PHP 8.4, PSR standards (PSR-3, PSR-7, PSR-11, PSR-14, PSR-15, PSR-16, PSR-17, PSR-18)
- **Frontend**: React 18, TypeScript 5.5, Vite, Material-UI 5, Redux Toolkit
- **Build**: Composer (PHP), npm workspaces + Lerna (JS), Docker
- **Testing**: PHPUnit 11 (backend), Vitest (frontend)
- **Code Quality**: [Mago](https://mago.carthage.software/) (linter + formatter + static analyzer, written in Rust)

## Repository Structure

```
/
├── playground/                    # Demo/reference applications per framework
│   ├── yiisoft-app/              # Yii 3 (Yiisoft) reference application
│   ├── symfony-basic-app/        # Symfony 7 minimal demo
│   └── yii2-basic-app/          # Yii 2 minimal demo
├── libs/
│   ├── Kernel/                   # Core: debugger lifecycle, collectors, storage, proxies
│   ├── API/                      # HTTP API: debug endpoints, inspector endpoints, SSE
│   ├── Cli/                      # CLI commands: debug server, reset, broadcast, query
│   ├── Testing/                  # Test scenarios: definitions, runner, CLI command
│   ├── Adapter/
│   │   ├── Yiisoft/              # Yii 3 framework adapter
│   │   ├── Symfony/              # Symfony framework adapter
│   │   ├── Yii2/                 # Yii 2 framework adapter
│   │   └── Cycle/                # Cycle ORM adapter (database schema only)
│   └── frontend/                 # Frontend monorepo
│       └── packages/
│           ├── panel/                # Main SPA (debug panel)
│           ├── toolbar/              # Embeddable toolbar widget
│           └── sdk/                  # Shared SDK (components, API clients, helpers)
├── CLAUDE.md                     # This file
└── docs/                         # Global documentation
```

## Architecture

ADP follows a **layered architecture**:

1. **Kernel** — Core engine. Manages debugger lifecycle, data collectors, storage, and proxy system. Framework-independent.
2. **API** — HTTP layer. Exposes debug data and inspector endpoints via REST + SSE. Framework-independent.
3. **Adapter** — Framework bridge. Wires Kernel collectors into a specific framework's DI, events, and middleware.
4. **Frontend** — React SPA. Consumes the API and renders debug/inspector UI.

```
┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│   Frontend   │────▶│     API      │────▶│    Kernel     │
│  (React SPA) │ HTTP│  (REST+SSE)  │     │ (Collectors)  │
└──────────────┘     └──────────────┘     └───────┬───────┘
                                                  │
                                          ┌───────┴───────┐
                                          │    Adapter     │
                                          │  (Yii/Symfony) │
                                          └───────┬───────┘
                                                  │
                                          ┌───────┴───────┐
                                          │  Target App   │
                                          │  (User's App) │
                                          └───────────────┘
```

## Data Flow

1. **Target app** runs with an Adapter installed (e.g., Yii adapter)
2. Adapter registers **proxies** that intercept PSR interfaces (logger, event dispatcher, HTTP client, DI container)
3. Proxies feed intercepted data to **Collectors** (LogCollector, EventCollector, etc.)
4. On request completion (or console command end), **Debugger** flushes all collector data to **Storage** (JSON files)
5. **API** serves stored data via REST endpoints; SSE notifies the frontend of new entries
6. **Frontend** fetches and renders the data in a web UI

## Key Commands

The project uses a **top-level Makefile** as the single entry point for all tasks. Run `make help` for a full list.

```bash
# Install
make install                        # Install ALL deps (PHP + frontend + playgrounds)
make install-php                    # Install PHP dependencies only
make install-frontend               # Install frontend dependencies only
make install-playgrounds            # Install playground dependencies only

# Tests
make test                           # Run ALL tests in parallel (PHP + frontend)
make test-php                       # Run PHP unit tests (PHPUnit)
make test-frontend                  # Run frontend unit tests (Vitest)
make test-frontend-e2e              # Run frontend browser tests (Vitest + Playwright)

# Code quality — PHP (Mago)
make mago                           # Run all Mago checks on core (format + lint + analyze)
make mago-fix                       # Fix core formatting, then lint + analyze
make mago-format                    # Check core code formatting
make mago-lint                      # Run core linter
make mago-analyze                   # Run core static analyzer

# Code quality — Playgrounds
make mago-playgrounds               # Run Mago checks on all playgrounds (parallel)
make mago-playgrounds-fix           # Fix formatting in all playgrounds (parallel)

# Code quality — Frontend
make frontend-check                 # Run frontend checks (Prettier + ESLint)
make frontend-fix                   # Fix frontend code quality issues

# Combined
make check                          # Run ALL code quality checks (core + playgrounds + frontend)
make fix                            # Fix all code (core + playgrounds + frontend)
make all                            # Run everything: checks + tests

# CI
make ci                             # Full CI pipeline: all checks + all tests
make check-ci                       # CI checks only
make test-ci                        # CI tests only

# Testing scenarios (requires running playground servers)
make scenarios             # Run CLI scenarios against all playgrounds
make scenarios-yiisoft     # CLI scenarios against Yiisoft (port 8101)
make scenarios-symfony     # CLI scenarios against Symfony (port 8102)
make scenarios-yii2        # CLI scenarios against Yii2 (port 8103)
make test-scenarios        # PHPUnit E2E scenarios against all playgrounds
make test-scenarios-yiisoft  # PHPUnit E2E against Yiisoft
make test-scenarios-symfony  # PHPUnit E2E against Symfony
make test-scenarios-yii2     # PHPUnit E2E against Yii2

# Frontend dev (still via npm)
cd libs/frontend
npm start                           # Start all Vite dev servers (via Lerna)
npm run build                       # Production build all packages
```

### Legacy composer/npm commands

The Makefile wraps these — use them directly only when needed:

```bash
composer test                       # PHPUnit (same as make test-php)
composer fix                        # PHP fix (same as make mago-fix)
cd libs/frontend && npm run check   # Frontend check (same as make frontend-check)
```

## CI/CD

GitHub Actions runs on every push and PR:

- **Tests**: Matrix of PHP 8.4/8.5 on Linux and Windows
- **Mago**: Format check, lint, and static analysis
- **PR Reports**: Coverage report and Mago analysis posted as PR comments

## Test Coverage Summary

### PHP (PHPUnit) — `make test-php`

| Suite | Library | Tests | Skipped | Time | Line Coverage |
|-------|---------|------:|--------:|-----:|--------------:|
| Kernel | `libs/Kernel` | 276 | 7 | 1m 21s | **85.2%** (1073/1259) |
| API | `libs/API` | 174 | 0 | 0.1s | **76.2%** (754/990) |
| Adapter-Symfony | `libs/Adapter/Symfony` | 150 | 9 | 0.2s | **98.9%** (905/915) |
| Adapter-Yii2 | `libs/Adapter/Yii2` | 95 | 0 | 0.1s | **57.3%** (373/651) |
| Cli | `libs/Cli` | 6 | 0 | 0.02s | **41.1%** (30/73) |
| **Total** | **all libs** | **701** | **16** | **~1m 22s** | **66.7%** (3135/4702) |

E2E suite (54 tests) requires Chrome + ChromeDriver and runs separately via `make test-frontend-e2e`.

### Frontend (Vitest) — `make test-frontend`

| Package | Tests | Suites | Time |
|---------|------:|-------:|-----:|
| `packages/sdk` | 209 | 25 | ~51s |
| `packages/panel` | 119 | 16 | ~51s |
| **Total** | **328** | **41** | **~51s** |

Browser e2e tests (4 suites) run separately via `make test-frontend-e2e`.

### Playgrounds — `make mago-playgrounds`

Playgrounds are demo/reference apps — they have **no unit tests**. Quality is ensured via Mago only.

| Playground | Format | Lint | Analyze | Baseline (suppressed) |
|------------|:------:|:----:|:-------:|----------------------:|
| `yiisoft-app` | pass | pass (3 baselined) | pass (96 baselined) | 99 |
| `symfony-basic-app` | pass | pass | pass (11 baselined) | 11 |
| `yii2-basic-app` | pass | pass | pass (10 baselined) | 10 |

### Running Coverage Locally

```bash
# PHP coverage (requires PCOV extension)
php vendor/bin/phpunit --coverage-text          # Text summary
php vendor/bin/phpunit --coverage-html=coverage  # HTML report in coverage/

# Frontend coverage
cd libs/frontend && npx vitest run --coverage    # Vitest with c8/istanbul
```

## Mandatory Post-Feature Pipeline

After implementing any feature or fix, you **must** run the full pipeline. Repeat until all steps pass.

### Step 1: Write Tests
```bash
/test <changed files>
```
Write tests for all new/modified code. Follow test conventions from `.claude/commands/test.md`.

### Step 2: Run Code Quality
```bash
make fix                            # Fix all code (PHP core + playgrounds + frontend)
make test                           # Run all tests (PHP + frontend, parallel)
```
Or granularly:
```bash
make mago-fix                       # PHP core only
make mago-playgrounds-fix           # Playgrounds only
make frontend-fix                   # Frontend only
make test-php                       # PHP tests only
make test-frontend                  # Frontend tests only
```
All checks must be green. Fix any failures before proceeding.

### Step 3: Review Documentation
```bash
/review-docs <changed modules>
```
Update CLAUDE.md and docs/ for any changed modules. Documentation is LLM-optimized — no filler, only facts.

### Step 4: Review Architecture
```bash
/review-arch <changed modules>
```
Verify no dependency violations introduced. Modules must follow the dependency graph strictly.

### Step 5: Iterate
If any step produces changes, go back to Step 2 and re-run checks. Continue until stable.

### Step 6: Final Verification
```bash
make all                            # Run everything: all checks + all tests
```
This must pass cleanly before pushing. Equivalent to `make check && make test`.

### Baselines

Mago uses baseline files to suppress existing issues in legacy code:
- `mago-lint-baseline.php` — Lint baseline
- `mago-analyze-baseline.php` — Analyzer baseline

New code must not introduce new issues. To regenerate baselines after fixing existing issues:
```bash
composer lint:baseline
composer analyze:baseline
```

## Custom Skills

| Skill | Command | Purpose |
|-------|---------|---------|
| Test Writer | `/test <file or class>` | Write tests in consistent style, inline mocks, no test environment |
| Doc Reviewer | `/review-docs [module]` | Review/update docs for LLM consumption, remove fluff |
| Arch Reviewer | `/review-arch [module]` | Check dependency rules, abstraction leaks, circular deps |
| Frontend Designer | `/frontend-designer [component or page]` | Design and implement React/MUI frontend components, pages, modules |

Skill definitions: `.claude/skills/test/SKILL.md`, `.claude/skills/review-docs/SKILL.md`, `.claude/skills/review-arch/SKILL.md`, `.claude/skills/frontend-designer/SKILL.md`.

## Module-Level Documentation

Each module under `libs/` has its own `CLAUDE.md` and `docs/` directory:

- `libs/Kernel/CLAUDE.md` — Core engine internals
- `libs/API/CLAUDE.md` — HTTP API endpoints and middleware
- `libs/Cli/CLAUDE.md` — CLI commands
- `libs/Testing/CLAUDE.md` — Test scenarios and runner
- `libs/Adapter/Yiisoft/CLAUDE.md` — Yii 3 adapter integration
- `libs/Adapter/Symfony/CLAUDE.md` — Symfony adapter integration
- `libs/Adapter/Yii2/CLAUDE.md` — Yii 2 adapter integration
- `libs/frontend/CLAUDE.md` — Frontend architecture

## Coding Conventions

- PHP: PER-CS (PER-2) via Mago, strict types, final classes where possible
- TypeScript: Prettier 3.8+ (single quotes, trailing commas, 120 width, objectWrap: collapse), ESLint 9 with @typescript-eslint
- TypeScript: strict mode, functional components, Redux Toolkit patterns
- All collector classes implement `CollectorInterface`
- New adapters implement proxy wiring for the target framework's PSR interfaces
- API responses wrapped in `{id, data, error, success, status}` format
