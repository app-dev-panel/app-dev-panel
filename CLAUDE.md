# ADP — Application Development Panel

## Project Overview

ADP (Application Development Panel) is a **framework-agnostic, language-agnostic** debugging and development panel.
It collects runtime data (logs, events, requests, exceptions, database queries, etc.) from applications and provides
a web UI to inspect, analyze, and debug them.

The project is currently a fork/consolidation from Yii Debug into a single monorepo, with the goal of becoming
fully framework-independent. Adapters exist for Yii 3, Symfony, Laravel, Yii 2, and Cycle ORM (database schema only).

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
│   ├── laravel-app/              # Laravel 12 minimal demo
│   └── yii2-basic-app/          # Yii 2 minimal demo
├── libs/
│   ├── Kernel/                   # Core: debugger lifecycle, collectors, storage, proxies
│   ├── API/                      # HTTP API: debug endpoints, inspector endpoints, SSE
│   ├── McpServer/                # MCP server: AI assistant integration (stdio + HTTP)
│   ├── Cli/                      # CLI commands: debug server, reset, broadcast, query, serve, mcp
│   ├── TaskBus/                  # Task queue: command execution, tests, analyzers, scheduling
│   ├── Testing/                  # Test fixtures: definitions, runner, CLI command
│   ├── Adapter/
│   │   ├── Yiisoft/              # Yii 3 framework adapter
│   │   ├── Symfony/              # Symfony framework adapter
│   │   ├── Laravel/              # Laravel framework adapter
│   │   ├── Yii2/                 # Yii 2 framework adapter
│   │   └── Cycle/                # Cycle ORM adapter (database schema only)
│   └── frontend/                 # Frontend monorepo
│       └── packages/
│           ├── panel/                # Main SPA (debug panel)
│           ├── toolbar/              # Embeddable toolbar widget
│           └── sdk/                  # Shared SDK (components, API clients, helpers)
├── website/                       # VitePress documentation site (source of truth for user-facing docs)
│   ├── .vitepress/
│   │   ├── config.ts             # VitePress config (sidebar, nav, locales, vitepress-plugin-llms)
│   │   └── theme/                # Custom theme (blog components, styles)
│   ├── guide/                    # User-facing guides (EN)
│   ├── api/                      # API reference docs (EN)
│   ├── blog/                     # Blog posts (EN)
│   └── ru/                       # Russian translations (guide/, api/, blog/)
├── CLAUDE.md                     # This file
└── docs/
    ├── mcp-server-plan.md        # MCP server design plan (phases, tools, resources)
    └── ...                       # Internal design documents
```

## Architecture

ADP follows a **layered architecture**:

1. **Kernel** — Core engine. Manages debugger lifecycle, data collectors, storage, and proxy system. Framework-independent.
2. **API** — HTTP layer. Exposes debug data, inspector, ingestion, MCP, and LLM endpoints via REST + SSE. Framework-independent.
3. **McpServer** — MCP (Model Context Protocol) server. Exposes debug data tools to AI assistants via stdio and HTTP transports.
4. **TaskBus** — Task queue engine. Runs commands, tests, analyzers, scheduled tasks, and LLM agent jobs. Symfony Messenger + SQLite. JSON-RPC 2.0 exposed via API inspector endpoint.
5. **Adapter** — Framework bridge. Wires Kernel collectors into a specific framework's DI, events, and middleware.
5. **Frontend** — React SPA. Consumes the API and renders debug/inspector UI.

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

## Prerequisites

Before running tests or code quality checks, install all dependencies:

```bash
make install                        # Install ALL deps (PHP + frontend + playgrounds)
```

Or install selectively:

```bash
make install-php                    # Composer install (root) — required for PHP tests and Mago
make install-frontend               # npm install (libs/frontend) — required for frontend tests
make install-playgrounds            # Composer install for each playground — required for fixture tests
```

### PHP Coverage Driver

Line coverage reports require the **PCOV** extension (recommended) or Xdebug:

```bash
pecl install pcov                   # Install PCOV
echo "extension=pcov.so" >> "$(php -i | grep 'Scan this dir' | awk '{print $NF}')/pcov.ini"
php -m | grep pcov                  # Verify: should print "pcov"
```

Without PCOV, `--coverage-text` / `--coverage-html` will fail with "No code coverage driver available".

### E2E Tests

E2E tests have additional requirements:

- **PHP E2E** (`make test-fixtures`): playground servers must be running (`make serve`)
- **Frontend E2E** (`make test-frontend-e2e`): Chrome/Chromium + matching ChromeDriver version

### Quick Start (run everything)

```bash
make install                        # 1. Install all dependencies
make all                            # 2. Run all checks + all tests
```

## Key Commands

The project uses a **top-level Makefile** as the single entry point for all tasks. Run `make help` for a full list.

```bash
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

# Testing fixtures (requires running playground servers)
make fixtures             # Run CLI fixtures against all playgrounds
make fixtures-yiisoft     # CLI fixtures against Yiisoft (port 8101)
make fixtures-symfony     # CLI fixtures against Symfony (port 8102)
make fixtures-yii2        # CLI fixtures against Yii2 (port 8103)
make fixtures-laravel     # CLI fixtures against Laravel (port 8104)
make test-fixtures        # PHPUnit E2E scenarios against all playgrounds
make test-fixtures-yiisoft  # PHPUnit E2E against Yiisoft
make test-fixtures-symfony  # PHPUnit E2E against Symfony
make test-fixtures-yii2     # PHPUnit E2E against Yii2
make test-fixtures-laravel  # PHPUnit E2E against Laravel

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
| Adapter-Laravel | `libs/Adapter/Laravel` | 80 | 0 | 0.1s | — |
| Adapter-Yii2 | `libs/Adapter/Yii2` | 95 | 0 | 0.1s | **57.3%** (373/651) |
| Adapter-Cycle | `libs/Adapter/Cycle` | 10 | 0 | 0.02s | — |
| Cli | `libs/Cli` | 6 | 0 | 0.02s | **41.1%** (30/73) |
| McpServer | `libs/McpServer` | 48 | 0 | 0.02s | — |
| McpServer (API) | `libs/API` (Mcp controller) | 6 | 0 | 0.02s | — |
| **Total** | **all libs** | **755** | **16** | **~1m 22s** | — |

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
| `yii2-basic-app` | pass | pass | pass (9 baselined) | 9 |
| `laravel-app` | pass | pass | pass | 0 |

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
All checks must be green. Fix any failures before proceeding — **including pre-existing test failures from other branches**. If tests were broken before your branch, fix them anyway.

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

### Step 5: Update VitePress Documentation & llms.txt
If changes affect user-facing behavior (new features, API changes, new collectors, adapter changes):
```bash
/docs-expert <describe what changed>
```
Update VitePress pages in `website/`. Then verify llms.txt generation:
```bash
/review-llms-txt
```
Skip this step for internal-only changes (refactoring, test fixes, CI tweaks).

### Step 6: Iterate
If any step produces changes, go back to Step 2 and re-run checks. Continue until stable.

### Step 7: Final Verification
```bash
make all                            # Run everything: all checks + all tests
```
This must pass cleanly before pushing. Equivalent to `make check && make test`.

### Baselines

Mago uses a baseline file to suppress existing lint issues in legacy code:
- `mago-lint-baseline.php` — Lint baseline

The analyzer has **no baseline** — all analyzer rules that produce false positives for the codebase
are suppressed via `ignore` in `mago.toml`. New code must not introduce new issues.

To regenerate the lint baseline after fixing existing issues:
```bash
composer lint:baseline
```

## Custom Skills

| Skill | Command | Purpose |
|-------|---------|---------|
| Test Writer | `/test <file or class>` | Write tests in consistent style, inline mocks, no test environment |
| Doc Reviewer | `/review-docs [module]` | Review/update CLAUDE.md and docs/ for LLM consumption |
| Arch Reviewer | `/review-arch [module]` | Check dependency rules, abstraction leaks, circular deps |
| Docs Expert | `/docs-expert [page or task]` | Write/update VitePress pages, i18n, blog posts, config |
| llms.txt Reviewer | `/review-llms-txt` | Verify llms.txt/llms-full.txt generation after doc changes |
| Frontend Dev | `/frontend-dev [component, page, or feature]` | Implement frontend features with React 19, strict TypeScript, semantic HTML, a11y |
| Frontend Designer | `/frontend-designer [component or page]` | Design and implement React/MUI frontend components, pages, modules |

Skill definitions in `.claude/skills/*/SKILL.md`.

## Module-Level Documentation

Each module under `libs/` has its own `CLAUDE.md` and `docs/` directory:

- `libs/Kernel/CLAUDE.md` — Core engine internals
- `libs/API/CLAUDE.md` — HTTP API endpoints and middleware
- `libs/McpServer/CLAUDE.md` — MCP server (AI assistant integration)
- `libs/Cli/CLAUDE.md` — CLI commands
- `libs/Testing/CLAUDE.md` — Test fixtures and runner
- `libs/Adapter/Yiisoft/CLAUDE.md` — Yii 3 adapter integration
- `libs/Adapter/Symfony/CLAUDE.md` — Symfony adapter integration
- `libs/Adapter/Laravel/CLAUDE.md` — Laravel adapter integration
- `libs/Adapter/Yii2/CLAUDE.md` — Yii 2 adapter integration
- `libs/TaskBus/CLAUDE.md` — Task queue engine (Messenger + SQLite + JSON-RPC)
- `libs/Adapter/Cycle/CLAUDE.md` — Cycle ORM adapter (database schema only)
- `libs/frontend/CLAUDE.md` — Frontend architecture

## Documentation Site

VitePress site in `website/`. Single source of truth for user-facing docs.

```bash
cd website
npm run dev                         # Local dev server
npm run build                       # Build site + generate llms.txt, llms-full.txt
```

### llms.txt Generation

[`vitepress-plugin-llms`](https://github.com/okineadev/vitepress-plugin-llms) auto-generates files in `dist/` at build time:

| File | Content |
|------|---------|
| `llms.txt` | Concise TOC with links to per-page `.md` files |
| `llms-full.txt` | All docs concatenated (frontmatter/Vue/HTML stripped via remark AST) |
| `*.md` (per-page) | Clean markdown copy alongside each `.html` page |

Configured in `website/.vitepress/config.ts` under `vite.plugins`. Russian pages excluded via `ignoreFiles: ['ru/**']`.

### Documentation Scope

| Location | Audience | Content |
|----------|----------|---------|
| `website/` | Users, LLM agents (via llms.txt) | Guides, API reference, blog, adapters |
| `libs/*/CLAUDE.md` | Claude Code (local dev) | Internal architecture, dependency rules, test commands |
| `docs/` | Internal | Design documents, plans, roadmaps |

## Coding Conventions

- Documentation: **English only** — all docs, comments, commit messages, and markdown files must be written in English
- PHP: PER-CS (PER-2) via Mago, strict types, final classes where possible
- TypeScript: Prettier 3.8+ (single quotes, trailing commas, 120 width, objectWrap: collapse), ESLint 9 with @typescript-eslint
- TypeScript: strict mode, functional components, Redux Toolkit patterns
- All collector classes implement `CollectorInterface`
- New adapters implement proxy wiring for the target framework's PSR interfaces
- API responses wrapped in `{id, data, error, success, status}` format
