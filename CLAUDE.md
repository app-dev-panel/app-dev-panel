# ADP — Application Development Panel

## Zero Tolerance — Skipped, Risky, Deprecated, Warning Tests Are FAILURES

A passing `.` is the only acceptable result. **Every** other PHPUnit marker — `S` (skipped),
`I` (incomplete), `R` (risky), `D` (deprecation), `N` (notice), `W` (warning) — is a FAIL.
All `phpunit.xml.dist` files set these attributes to `true` and they **must never be flipped off**:

```
failOnRisky, failOnWarning, failOnNotice, failOnDeprecation,
failOnPhpunitDeprecation, failOnPhpunitWarning,
failOnIncomplete, failOnSkipped, failOnEmptyTestSuite,
beStrictAboutOutputDuringTests,
beStrictAboutTestsThatDoNotTestAnything,
beStrictAboutChangesToGlobalState
```

Rules:
- **Never** call `markTestSkipped()`, `markTestIncomplete()`, or `$this->expectDeprecation*()` as a
  way to make a red test green. If the dependency/extension/environment is missing, either (a) mock
  it, (b) make it a required dev-dep, or (c) delete the test. Skipping is not an option.
- **Never** downgrade the fail flags above in any `phpunit.xml*` file. Not even temporarily.
- **Never** write tests that depend on ambient state (network, env vars, filesystem) without a mock.
  Ambient-state tests are the reason we used to see `S` markers — they are banned.
- **Any** PHP warning, notice, or deprecation raised during a test must fail the test. The listed
  attributes enforce this for the PHPUnit process. For HTTP E2E tests against playground servers,
  the playground entry points install a strict error handler that converts warnings/notices into
  `ErrorException`, turning them into HTTP 500s that fail the fixture assertion.
- CI runs the suite **once**, with coverage on the primary matrix cell (ubuntu-latest / PHP 8.4)
  and without coverage on the rest. Never add a second "run tests without coverage" step.

If you see yourself reaching for `markTestSkipped`, stop and ask for help writing a proper mock.

## Hard Timeouts — NEVER RAISE

The test infrastructure is aggressively time-boxed so that nothing — PHPUnit, Vitest, fixtures,
network calls, socket reads — can hang a Claude Code hook or a CI run. These limits are the
**ceiling, not a target**. You are **forbidden** from raising any of them. If a test or command
breaches a limit, fix the slow code (mock I/O, reduce fixture data, split the test, skip a broken
service) — do not edit the ceiling.

| Scope | Limit | Where |
|-------|------:|-------|
| Full test suite (PHPUnit / Vitest) | `180s` | `Makefile` — `TEST_TIMEOUT` |
| Single playground fixture / scenario run | `120s` | `Makefile` — `FIXTURE_TIMEOUT` |
| Helper poll / daemon ping | `15s` | `Makefile` — `HELPER_TIMEOUT` |
| PHPUnit — small / medium / large / default test | `2s / 5s / 10s / 10s` | all `phpunit.xml.dist` |
| PHPUnit — PHP `max_execution_time` / `default_socket_timeout` | `15s / 10s` | all `phpunit.xml.dist` `<ini>` |
| Vitest — `testTimeout` / `hookTimeout` (jsdom) | `10s / 10s` | `libs/frontend/vitest.config.ts` |
| Vitest — `testTimeout` / `hookTimeout` (browser) | `15s / 15s` | `libs/frontend/vitest.browser.config.ts` |
| `FixtureRunner` HTTP timeout | `15s` hard cap | `libs/Testing/src/Runner/FixtureRunner.php` |
| `DebugDataFetcher` retry deadline | `15s` | `libs/Testing/src/Runner/DebugDataFetcher.php` |
| `InspectorClient` HTTP timeout | `15s` hard cap | `libs/McpServer/src/Inspector/InspectorClient.php` |
| `AcpDaemonManager` session-start / prompt | `30s / 30s` hard cap | `libs/API/src/Llm/Acp/AcpDaemonManager.php` |
| `RequestController::request` re-execute client | `15s` (connect 5s) | `libs/API/src/Inspector/Controller/RequestController.php` |
| `FrontendUpdateCommand` GitHub release check | `10s` (connect 5s) | `libs/Cli/src/Command/FrontendUpdateCommand.php` |
| `FrontendUpdateCommand` ZIP download | `30s` (connect 5s) | `libs/Cli/src/Command/FrontendUpdateCommand.php` |

Rules:
- **Never** bump a number in the table above to make something pass. Fix the underlying code.
- **Never** add a new network/socket/subprocess call without an explicit timeout ≤ the matching limit.
- New tests must finish well under `defaultTimeLimit` (10s). If a test legitimately takes longer,
  it belongs in `@group e2e` or `@group playground`, which are excluded from the default suite.
- The `timeout` wrapper in the Makefile (`$(call with_timeout,…)`) is mandatory for any Make target
  that invokes PHPUnit, Vitest, or a playground fixture run.

## Project Overview

ADP (Application Development Panel) is a **framework-agnostic, language-agnostic** debugging and development panel.
It collects runtime data (logs, events, requests, exceptions, database queries, etc.) from applications and provides
a web UI to inspect, analyze, and debug them.

The project is currently a fork/consolidation from Yii Debug into a single monorepo, with the goal of becoming
fully framework-independent. Adapters exist for Yii 3, Symfony, Laravel, Yii 2, and Cycle ORM (database schema only).

## Tech Stack

- **Backend**: PHP 8.4, PSR standards (PSR-3, PSR-7, PSR-11, PSR-14, PSR-15, PSR-16, PSR-17, PSR-18)
- **Frontend**: React 19, TypeScript 6, Vite 8, Material-UI (MUI) 7, Redux Toolkit 2
- **Build**: Composer (PHP), npm workspaces + Lerna (JS), Docker
- **Testing**: PHPUnit 11 (backend), Vitest (frontend)
- **Code Quality**: [Mago](https://mago.carthage.software/) (linter + formatter + static analyzer, written in Rust), Modulite (module boundary checker, inspired by [VK Modulite](https://github.com/VKCOM/modulite))

## Repository Structure

```
/
├── playground/                    # Demo/reference applications per framework
│   ├── yii3-app/                 # Yii 3 reference application
│   ├── symfony-app/        # Symfony 7 minimal demo
│   ├── laravel-app/              # Laravel 11/12/13 minimal demo
│   └── yii2-basic-app/          # Yii 2 minimal demo
├── libs/
│   ├── Kernel/                   # Core: debugger lifecycle, collectors, storage, proxies
│   ├── API/                      # HTTP API: debug endpoints, inspector endpoints, SSE
│   ├── McpServer/                # MCP server: AI assistant integration (stdio + HTTP)
│   ├── Cli/                      # CLI commands: debug server, reset, broadcast, query, serve, mcp
│   ├── Testing/                  # Test fixtures: definitions, runner, CLI command
│   ├── FrontendAssets/           # Composer package shipping the prebuilt panel SPA (dist/ populated at release time)
│   ├── Adapter/
│   │   ├── Yii3/                 # Yii 3 framework adapter
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
├── modulite.php                   # Module boundary rules (dependency graph)
├── tools/
│   └── modulite-check.php        # Module boundary validator script
├── CLAUDE.md                     # This file
└── docs/
    ├── mcp-server-plan.md        # MCP server remaining phases (3-6)
    ├── strategic-analysis.md     # Competitive position, development vectors
    ├── ideas.md                  # Feature ideas (not committed)
    ├── editor-integration-opportunities.md  # Editor integration remaining work
    └── design/                   # UI design specs (Variant D: Minimal Zen)
```

## Architecture

ADP follows a **layered architecture**:

1. **Kernel** — Core engine. Manages debugger lifecycle, data collectors, storage, and proxy system. Framework-independent.
2. **API** — HTTP layer. Exposes debug data, inspector, ingestion, MCP, and LLM endpoints via REST + SSE. Framework-independent.
3. **McpServer** — MCP (Model Context Protocol) server. Exposes debug data tools to AI assistants via stdio and HTTP transports.
4. **Adapter** — Framework bridge. Wires Kernel collectors into a specific framework's DI, events, and middleware.
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
make test-frontend-e2e              # Run frontend browser tests (Vitest + WebDriverIO + ChromeDriver)

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

# Modulite — Module boundary validation
make modulite                       # Check module boundary violations

# Combined
make check                          # Run ALL code quality checks (core + playgrounds + frontend + modulite)
make fix                            # Fix all code (core + playgrounds + frontend)
make all                            # Run everything: checks + tests

# CI
make ci                             # Full CI pipeline: all checks + all tests
make check-ci                       # CI checks only
make test-ci                        # CI tests only

# Testing fixtures (requires running playground servers)
make fixtures             # Run CLI fixtures against all playgrounds
make fixtures-yii3        # CLI fixtures against Yii 3 (port 8101)
make fixtures-symfony     # CLI fixtures against Symfony (port 8102)
make fixtures-yii2        # CLI fixtures against Yii2 (port 8103)
make fixtures-laravel     # CLI fixtures against Laravel (port 8104)
make test-fixtures        # PHPUnit E2E scenarios against all playgrounds
make test-fixtures-yii3     # PHPUnit E2E against Yii 3
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

## Modulite — Module Boundary Validation

Inspired by [VK Modulite](https://github.com/VKCOM/modulite). Enforces that each module only imports from its declared dependencies.

**Configuration**: `modulite.php` — defines modules, namespaces, paths, and allowed `requires`.
**Validator**: `tools/modulite-check.php` — scans `use` statements, reports violations.

### Dependency Rules

| Module | Requires |
|--------|----------|
| `kernel` | (none — foundation) |
| `mcp-server` | `kernel` |
| `api` | `kernel`, `mcp-server` |
| `cli` | `kernel`, `api`, `mcp-server`, `frontend-assets` |
| `testing` | (none — independent) |
| `frontend-assets` | (none — leaf, ships prebuilt SPA) |
| `adapter-yii3` | `kernel`, `api`, `cli`, `mcp-server`, `frontend-assets` |
| `adapter-symfony` | `kernel`, `api`, `cli`, `mcp-server`, `frontend-assets` |
| `adapter-laravel` | `kernel`, `api`, `cli`, `mcp-server`, `frontend-assets` |
| `adapter-yii2` | `kernel`, `api`, `cli`, `mcp-server`, `frontend-assets` |
| `adapter-cycle` | `api` |

### Rules

- **No circular dependencies**: the graph is strictly acyclic
- **Kernel is the foundation**: all core modules depend on Kernel (directly or transitively)
- **Adapters depend on core**: adapters may use Kernel + API + Cli, never other adapters
- **Testing is independent**: no internal dependencies
- **Cycle is minimal**: only depends on API (for `SchemaProviderInterface`)
- External (vendor) namespaces are not governed by these rules

### Usage

```bash
make modulite                              # Human-readable output
php tools/modulite-check.php --format=github   # GitHub Actions annotations
php tools/modulite-check.php --format=json     # JSON output
```

To add a new dependency: edit `requires` in `modulite.php`. To add a new module: add a new entry with `namespace`, `path`, `requires`.

## CI/CD

GitHub Actions runs on every push and PR:

- **Tests**: Matrix of PHP 8.4/8.5 on Linux and Windows
- **Mago**: Format check, lint, and static analysis
- **Modulite**: Module boundary validation
- **PR Reports**: Coverage report and Mago analysis posted as PR comments

## Test Coverage Summary

### PHP (PHPUnit) — `make test-php`

| Suite | Library | Tests | Skipped | Time | Line Coverage |
|-------|---------|------:|--------:|-----:|--------------:|
| Kernel | `libs/Kernel` | 276 | 7 | 1m 21s | **85.2%** (1073/1259) |
| API | `libs/API` | 174 | 0 | 0.1s | **76.2%** (754/990) |
| Adapter-Symfony | `libs/Adapter/Symfony` | 151 | 9 | 0.2s | **98.9%** (905/915) |
| Adapter-Laravel | `libs/Adapter/Laravel` | 86 | 0 | 0.1s | — |
| Adapter-Yii2 | `libs/Adapter/Yii2` | 95 | 0 | 0.1s | **57.3%** (373/651) |
| Adapter-Cycle | `libs/Adapter/Cycle` | 10 | 0 | 0.02s | — |
| Cli | `libs/Cli` | 198 | 0 | ~14s | — |
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
| `yii3-app` | pass | pass (3 baselined) | pass (96 baselined) | 99 |
| `symfony-app` | pass | pass | pass (11 baselined) | 11 |
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
make modulite                       # Module boundary check only
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
| Screenshot | `/screenshot [URL or page path]` | Take screenshots of frontend pages using Playwright for visual verification |
| Selenium E2E | `/selenium-e2e [test file or feature]` | Write and debug E2E browser tests (Vitest + WebDriverIO, PHPUnit + php-webdriver) |

Skill definitions in `.claude/skills/*/SKILL.md`.

## Module-Level Documentation

Each module under `libs/` has its own `CLAUDE.md` with internal architecture details:

- `libs/Kernel/CLAUDE.md` — Core engine internals
- `libs/API/CLAUDE.md` — HTTP API endpoints and middleware
- `libs/McpServer/CLAUDE.md` — MCP server (AI assistant integration)
- `libs/Cli/CLAUDE.md` — CLI commands
- `libs/Testing/CLAUDE.md` — Test fixtures and runner
- `libs/FrontendAssets/CLAUDE.md` — Prebuilt panel SPA Composer package (dist shipping, split-repo flow)
- `libs/Adapter/Yii3/CLAUDE.md` — Yii 3 adapter integration
- `libs/Adapter/Symfony/CLAUDE.md` — Symfony adapter integration
- `libs/Adapter/Laravel/CLAUDE.md` — Laravel adapter integration
- `libs/Adapter/Yii2/CLAUDE.md` — Yii 2 adapter integration
- `libs/Adapter/Cycle/CLAUDE.md` — Cycle ORM adapter (database schema only)
- `libs/frontend/CLAUDE.md` — Frontend architecture

User-facing documentation lives in `website/` — see Documentation Site below.

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
| `website/` | Users, LLM agents (via llms.txt) | Guides, API reference, blog, adapters (source of truth) |
| `libs/*/CLAUDE.md` | Claude Code (local dev) | Internal architecture, dependency rules, test commands |
| `docs/` | Internal | UI design specs, strategic plans, feature ideas |

## Coding Conventions

- Documentation: **English only** — all docs, comments, commit messages, and markdown files must be written in English
- PHP: PER-CS (PER-2) via Mago, strict types, final classes where possible
- TypeScript: Prettier 3.8+ (single quotes, trailing commas, 120 width, objectWrap: collapse), ESLint 9 with @typescript-eslint
- TypeScript: strict mode, functional components, Redux Toolkit patterns
- All collector classes implement `CollectorInterface`
- New adapters implement proxy wiring for the target framework's PSR interfaces
- API responses wrapped in `{id, data, error, success, status}` format
