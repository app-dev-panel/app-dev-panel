# P3 — Documentation

CLAUDE.md files drifted from actual code.

## Tasks

### [x] B1 — Kernel PHP version consistency
- `libs/Kernel/CLAUDE.md:32` says `PHP: 8.4+`, `libs/Kernel/composer.json:5` says `"php": "^8.2"`.
- Fix: decide canonical version:
  - If we ship PHP 8.4 everywhere — bump `libs/Kernel/composer.json` to `"php": "^8.4"`.
  - Otherwise fix the CLAUDE.md line to `PHP: 8.2+`.
- Expected: bump composer (since all other libs are 8.4).

### [x] B2 — Kernel Storage docs completeness
- `libs/Kernel/CLAUDE.md` Directory Structure and Storage sections miss:
  - `SqliteStorage.php` (full new implementation).
  - `BroadcastingStorage.php` (decorator that pushes to DebugServer socket).
  - `StorageFactory.php` (factory picking storage by config).
- Fix: add entries to the tree + describe in the Storage section (use cases, how to configure).

### [x] B3 — API CLAUDE.md: HttpMock section missing
- `libs/API/CLAUDE.md` has no mention of:
  - `libs/API/src/Inspector/Controller/HttpMockController.php`
  - `libs/API/src/Inspector/HttpMock/HttpMockProviderInterface.php` + `NullHttpMockProvider`, `PhiremockProvider`.
- `libs/API/src/ApiRoutes.php` registers `/inspect/api/http-mock/status`, `/expectations`, `/expectations/create`, etc.
- Fix: add HttpMock to Directory Structure and Inspector API endpoints table.

### [x] B4 — Laravel adapter: missing listeners
- `libs/Adapter/Laravel/CLAUDE.md:20-28` lists only 8 listeners; actual directory has 11:
  - Missing: `GateListener.php`, `RedisListener.php`, `ValidatorListener.php`.
  - Missing middleware: `DebugCollectors.php`, `Psr7Converter.php`.
- Fix: extend the tree + document what each one captures.

### [x] B5 — Strip "Overview" filler heading
- `libs/McpServer/CLAUDE.md:3`: `## Overview` is filler per review-docs skill policy (LLM-optimized docs, no boilerplate headings).
- Fix: delete the `## Overview` heading, keep the prose.

### [~] E2 — Endpoints table vs ApiRoutes.php audit — PARTIAL
- `libs/API/CLAUDE.md` endpoint tables vs `libs/API/src/ApiRoutes.php` — 46 `Route(...)` registrations, docs have 80+ rows.
- Done as part of B3: HttpMock endpoints (7 rows) added.
- Still open: run the full diff, remove/adjust any stale rows, cross-check LLM/MCP/Services tables.

## Acceptance
- `git grep -c "HttpMock" libs/API/CLAUDE.md` ≥ 1.
- All `libs/**/CLAUDE.md` file listings match `ls libs/*/src/<section>` output.
- Composer PHP requirement matches CLAUDE.md in every module.
