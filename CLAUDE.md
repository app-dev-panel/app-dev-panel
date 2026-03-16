# ADP — Application Development Panel

## Core Concept

ADP is a **universal application inspection tool** — framework-agnostic, language-agnostic. It intercepts runtime data from any application and provides a web UI for debugging and inspection.

Two fundamental modes:

- **Debugger** — Collects data per request or command execution. Every intercepted action (logs, SQL queries, HTTP calls, events, exceptions) is stored permanently. All historical entries remain accessible — 100 API requests means 100 inspectable debug entries. Storage: JSON files per entry (summary + data + objects).
- **Inspector** — Real-time application viewer. Browse files, configs, cache, routes, translations. Run tests, scripts, composer commands. State exists only for the current view — no history. Inspector operates on live application state. Supports multi-app proxying: external services register via the Service Registry API and inspector requests are proxied to them via `InspectorProxyMiddleware`.

Debugger and Inspector integrate: e.g., "execute SQL query" action next to DB connection logs, "view source file" next to exception traces.

### Data Ingestion

Two ways to feed data into ADP:

1. **PHP Adapters** — Proxies wrap PSR interfaces (Logger, EventDispatcher, HttpClient, Container) inside the target app. Collectors capture data transparently. Works for PHP frameworks (Yii 3 adapter exists, Symfony/Laravel planned).
2. **Ingestion API** — Language-agnostic HTTP endpoints. External apps (Python, Node.js, Go, etc.) send debug data via REST. Defined by OpenAPI 3.1 spec at `openapi/ingestion.yaml`. Pre-built clients: Python (`clients/python/`), TypeScript (`clients/typescript/`).

Origin: fork/consolidation from Yii Debug into a monorepo, evolving to be fully framework-independent.

## Tech Stack

- **Backend**: PHP 8.4, PSR standards (PSR-3, PSR-7, PSR-11, PSR-14, PSR-15, PSR-16, PSR-17, PSR-18)
- **Frontend**: React 18, TypeScript 5.5, Vite, Material-UI 5, Redux Toolkit
- **Build**: Composer (PHP), npm workspaces + Lerna (JS), Docker
- **Testing**: PHPUnit 11 (backend), Vitest (frontend)
- **Code Quality**: [Mago](https://mago.carthage.software/) (linter + formatter + static analyzer, written in Rust)

## Repository Structure

```
/
├── app/                          # Demo/reference PHP application
├── libs/
│   ├── Kernel/                   # Core: debugger lifecycle, collectors, storage, proxies
│   ├── API/                      # HTTP API: debug, inspector, ingestion endpoints, SSE
│   ├── Cli/                      # CLI commands: debug server, reset, broadcast
│   ├── Adapter/
│   │   └── Yiisoft/              # Yii 3 framework adapter
│   └── yii-dev-panel/            # Frontend monorepo
│       └── packages/
│           ├── yii-dev-panel/        # Main SPA (debug panel)
│           ├── yii-dev-toolbar/      # Embeddable toolbar widget
│           └── yii-dev-panel-sdk/    # Shared SDK (components, API clients, helpers)
├── clients/
│   ├── python/                   # Python ingestion client (adp-client)
│   └── typescript/               # TypeScript ingestion client (@app-dev-panel/client)
├── openapi/
│   ├── ingestion.yaml            # OpenAPI 3.1 spec for ingestion endpoints
│   └── inspector.yaml            # OpenAPI 3.1 spec for inspector contract (external apps)
├── scripts/
│   └── generate-clients.sh       # Client code generation from OpenAPI spec
├── CLAUDE.md
└── docs/
```

## Architecture

Layered architecture:

1. **Kernel** — Core engine. Debugger lifecycle, collectors, storage, proxy system. Framework-independent.
2. **API** — HTTP layer. Debug, inspector, and ingestion endpoints via REST + SSE. Framework-independent.
3. **Adapter** — Framework bridge. Wires Kernel collectors into a framework's DI, events, and middleware.
4. **Frontend** — React SPA. Consumes the API and renders debug/inspector UI.

```
                                     ┌───────────────────┐
                                     │  External Apps    │
                                     │  (Python/Node/Go) │
                                     └────────┬──────────┘
                                              │ Ingestion API
┌──────────────┐     ┌──────────────┐     ┌───┴──────────┐
│   Frontend   │────▶│     API      │────▶│    Kernel     │
│  (React SPA) │ HTTP│  (REST+SSE)  │     │  (Storage)    │
└──────────────┘     └──────────────┘     └───────┬───────┘
                                                  │ Proxies
                                          ┌───────┴───────┐
                                          │    Adapter     │
                                          │  (Yii/Symfony) │
                                          └───────┬───────┘
                                                  │
                                          ┌───────┴───────┐
                                          │  PHP App      │
                                          │  (User's App) │
                                          └───────────────┘
```

## Data Flow

### PHP Adapter Flow
1. Target app runs with an Adapter installed
2. Adapter registers proxies that intercept PSR interfaces (logger, event dispatcher, HTTP client, container)
3. Proxies feed intercepted data to Collectors
4. On request/command completion, Debugger flushes collector data to Storage (JSON files)
5. API serves stored data via REST; SSE notifies frontend of new entries

### Ingestion API Flow (any language)
1. External app sends HTTP POST with debug data (logs, traces, metrics) to `/debug/api/ingest`
2. IngestionController validates and writes directly to FileStorage
3. Data appears in the debugger UI alongside PHP debug entries

## Key Commands

```bash
# Install
composer install                    # Install PHP dependencies

# Tests
composer test                       # Run PHPUnit tests
composer test:coverage              # Run tests with coverage report

# Code quality — PHP (Mago, PER-CS / PER-2 preset)
composer format:check               # Check PHP code formatting (dry-run)
composer format:fix                 # Fix PHP code formatting
composer lint                       # Run PHP linter
composer analyze                    # Run PHP static analyzer
composer check                      # Run all PHP checks (format + lint + analyze)
composer fix                        # Fix formatting, then run lint + analyze

# Frontend
cd libs/yii-dev-panel
npm install                         # Install JS dependencies
npm start                           # Start all Vite dev servers (via Lerna)
npm run build                       # Production build all packages

# Code quality — JS/TS (Prettier 3.8+, ESLint 9)
npm run format                      # Format JS/TS/CSS/JSON with Prettier
npm run format:check                # Check formatting (CI)
npm run lint                        # ESLint check
npm run lint:fix                    # ESLint auto-fix
npm run check                       # Run all JS checks (format + lint)
```

## CI/CD

GitHub Actions runs on every push and PR:

- **Tests**: Matrix of PHP 8.4/8.5 on Linux and Windows
- **Mago**: Format check, lint, and static analysis
- **PR Reports**: Coverage report and Mago analysis posted as PR comments

## Mandatory Post-Feature Pipeline

After implementing any feature or fix, you **must** run the full pipeline. Repeat until all steps pass.

### Step 1: Write Tests
```bash
/test <changed files>
```
Write tests for all new/modified code. Follow test conventions from `.claude/commands/test.md`.

### Step 2: Run Code Quality
```bash
composer fix                        # PHP: fix formatting + lint + analyze
composer test                       # PHP: run all tests
cd libs/yii-dev-panel && npm run check  # JS: format check + lint (if frontend changed)
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

Skill definitions: `.claude/skills/test/SKILL.md`, `.claude/skills/review-docs/SKILL.md`, `.claude/skills/review-arch/SKILL.md`.

## Module-Level Documentation

Each module under `libs/` has its own `CLAUDE.md` and `docs/` directory:

- `libs/Kernel/CLAUDE.md` — Core engine internals
- `libs/API/CLAUDE.md` — HTTP API endpoints and middleware
- `libs/Cli/CLAUDE.md` — CLI commands
- `libs/Adapter/Yiisoft/CLAUDE.md` — Yii 3 adapter integration
- `libs/yii-dev-panel/CLAUDE.md` — Frontend architecture

## Coding Conventions

- PHP: PER-CS (PER-2) via Mago, strict types, final classes where possible
- TypeScript: Prettier 3.8+ (single quotes, trailing commas, 120 width, objectWrap: collapse), ESLint 9 with @typescript-eslint
- TypeScript: strict mode, functional components, Redux Toolkit patterns
- All collector classes implement `CollectorInterface`
- New adapters implement proxy wiring for the target framework's PSR interfaces
- API responses wrapped in `{id, data, error, success, status}` format
