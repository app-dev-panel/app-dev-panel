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
- **Testing**: PHPUnit (backend), Vitest (frontend), PHPStan + Psalm (static analysis)

## Repository Structure

```
/
├── app/                          # Demo/reference PHP application
├── libs/
│   ├── Kernel/                   # Core: debugger lifecycle, collectors, storage, proxies
│   ├── API/                      # HTTP API: debug endpoints, inspector endpoints, SSE
│   ├── Cli/                      # CLI commands: debug server, reset, broadcast
│   ├── Adapter/
│   │   └── Yiisoft/              # Yii 3 framework adapter (first adapter)
│   └── yii-dev-panel/            # Frontend monorepo
│       └── packages/
│           ├── yii-dev-panel/        # Main SPA (debug panel)
│           ├── yii-dev-toolbar/      # Embeddable toolbar widget
│           └── yii-dev-panel-sdk/    # Shared SDK (components, API clients, helpers)
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

```bash
# PHP backend
composer install                    # Install PHP dependencies
composer test                       # Run PHPUnit tests

# Frontend
cd libs/yii-dev-panel
npm install                         # Install JS dependencies
npm run dev                         # Start Vite dev server
npm run build                       # Production build

# CLI
php yii dev                         # Start debug server
php yii debug:reset                 # Clear debug data
```

## Module-Level Documentation

Each module under `libs/` has its own `CLAUDE.md` and `docs/` directory:

- `libs/Kernel/CLAUDE.md` — Core engine internals
- `libs/API/CLAUDE.md` — HTTP API endpoints and middleware
- `libs/Cli/CLAUDE.md` — CLI commands
- `libs/Adapter/Yiisoft/CLAUDE.md` — Yii 3 adapter integration
- `libs/yii-dev-panel/CLAUDE.md` — Frontend architecture

## Coding Conventions

- PHP: PSR-12, strict types, final classes where possible
- TypeScript: strict mode, functional components, Redux Toolkit patterns
- All collector classes implement `CollectorInterface`
- New adapters implement proxy wiring for the target framework's PSR interfaces
- API responses wrapped in `{id, data, error, success, status}` format
