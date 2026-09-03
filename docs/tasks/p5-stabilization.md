# P5 — Stabilization: bugs, security, automation

Backlog from the full-project review (2026-09-03). Inputs: three read-only audits (CI/automation,
PHP backend, frontend), `make review-checks`, `mago lint`/`analyze`, a full PHPUnit + Vitest run,
the four open GitHub issues (#108, #111, #113, #114) and the last red CI run on `master`.

Legend: `[x]` done in this branch, `[ ]` open, `[~]` partially done / deferred with reason.

## 0. State before this branch

| Signal | Result |
|--------|--------|
| CI on `master` (run #943) | red — `apt-get install php8.x-redis` fails on `ubuntu-latest`, both PHP cells die before tests |
| `composer test:unit` | 3114 tests, 3 risky (`DebugServerBroadcastCommandTest` hangs 10s each, Broadcaster `fwrite` notices), 147s wall time (ceiling 180s) |
| `php tools/check-test-strictness.php` | 60 violations: 52 `markTestSkipped()` calls + 8 missing strict attrs in playground `phpunit.xml.dist` |
| `php tools/check-docs-tree.php` | 1 error (RU sidebar link to missing page), 4 missing RU translations |
| `mago lint` | 55 issues outside the baseline (29 `@` operators, 8 `isset`, complexity) — exit 0, so CI never notices |
| `mago analyze` | 21 issues (13 redundant casts, 3 possibly-undefined index, …) — exit 0 |
| Vitest | 985 tests / 102 files green; 261s under load (ceiling 180s) |
| Browser e2e (`npm run test:e2e`) | cannot pass: `mockServiceWorker.js` missing from both `public/` dirs |
| Open issues | #108 double bootstrap (Yii2), #111 toolbar metrics not clickable, #113 asset paths under sub-path, #114 JsonException in shutdown |

## 1. CI is red / automation gaps (Critical)

### [x] A1 — Fix the `tests` job on `master`
- `.github/workflows/ci.yml`: install `redis` through `shivammathur/setup-php` `extensions:` instead of `apt-get` from a PPA that is not on the runner; drop the dead `env: fail-fast` key; keep the extension verification loop.

### [x] A2 — Stop depending on ext-redis in unit tests
- `libs/API/tests/Unit/Inspector/Controller/RedisControllerTest.php` only uses `createMock(\Redis::class)`; ship a test stub for `\Redis` loaded from `tests/bootstrap.php` when the extension is absent and delete the 20 guards.

### [x] A3 — Run the checks that exist but were never wired
- `review-checks` (`check-timeouts`, `check-strict`, `check-docs-tree`) added to `make check`, `make check-ci` and a CI job.
- `composer validate --strict` for root and `libs/*/composer.json`.
- `frontend-build` job (`tsc --build` + `vite build`) — the only type-check of shipped code; `libs/frontend/package.json` `build` no longer masks failures (`& … & wait` always exited 0).
- `website` job (`vitepress build`) on PRs, not only on the deploy workflow.
- Aggregate `ci-ok` job so branch protection needs a single required check.
- `composer test:unit` / `test:coverage` now include the `Testing` and `Adapter-Spiral` suites (they were never executed anywhere).
- `mago-playgrounds` job installs playground vendors before `analyze`.
- `deploy-docs.yml` waits for the Modulite job too; `split.yml` publishes `adapter-spiral`.
- `.github/dependabot.yml` committed with grouped updates (illuminate/laravel, symfony, yiisoft, @mui, github-actions).

### [x] A4 — Claude Code hooks were silently no-ops
- `run-tests.sh`: wrong lerna scope (`@adp/*`), missing suite map for FrontendAssets/Spiral, no timeout wrapper.
- `lint-file.sh` / `require-tests-for-pr.sh`: stale `mago format` subcommand, `| tail` swallowing exit codes (the pre-PR gate always passed), no PATH fallback for `mago`.
- `settings.json`: one PostToolUse block instead of three; PR gate also covers `gh pr create` via Bash.

### [ ] A5 — Still not covered by CI (needs infra decision)
- Playground E2E (`make test-fixtures`, `test-scenario`, `test-mcp`, `test-pages`) — requires 5 running playground servers with installed vendors in CI. Proposal: one `playground-e2e` job per adapter with `composer install` + `php -S`, gated on `libs/**` and `playground/**` paths.
- `composer test:e2e` (`tests/E2E`, Selenium against a live panel) — needs the same plus ChromeDriver. Either add it next to the Playwright job or delete the suite; it has had no execution path since the Playwright migration.
- OpenAPI lint (`openapi/*.yaml`) and client-generation drift (`scripts/generate-clients.sh` vs `clients/`).
- `npm audit` / `composer audit` (advisory only at first).

## 2. Zero-tolerance test policy (Critical)

### [x] B1 — Remove every `markTestSkipped()` (52 calls)
- ext-redis (20), OPcache state (1), Windows guards (6, CI is Linux-only), optional packages that are in `require-dev` anyway (11), playground-dependent integration tests (moved to `#[Group('playground')]` with hard failures instead of skips), `tests/E2E` WebDriver skips (hard failure + seeded entries), network-dependent `HttpStreamCollectorTest` (rewritten against local streams), `illuminate/foundation` (added as dev dependency).
- `tools/check-test-strictness.php` is now green and enforced in CI.

### [x] B2 — Playground `phpunit.xml.dist` files get the two missing `beStrictAbout*` attributes

### [x] B3 — Risky tests / stray output
- `DebugServerBroadcastCommandTest` no longer hits a real socket; `Broadcaster` never blocks or emits notices.
- LLM settings migration no longer prints to stdout during tests.

### [ ] B4 — Suite duration is at the ceiling
- PHPUnit: 147s for 3114 tests on a 4-core box under load. Largest offenders to profile: Kernel integration-style tests (temp dirs, storage), Cli process tests. Target < 90s.
- Vitest: 261s under load / ~50s idle; `import` phase dominates (408s cumulative). Consider `pool: 'threads'`, `isolate: false` for pure helper tests, and splitting MUI-heavy suites.

## 3. Open GitHub issues (High)

### [x] C1 — #114 JsonException "Type is not supported" in `Debugger::shutdown()`
- `DumpContext::getResourceDescription()` returned raw `stream_get_meta_data()` including `wrapper_data` objects with live resources.
- `Dumper::encodeJson()` now degrades instead of throwing; `asJsonObjectsMap()` has depth/cycle protection for arrays.
- `Debugger` never lets a `Throwable` escape the shutdown handler (logged instead).
- `RequestCollector` caps body materialisation and tolerates non-seekable streams.

### [x] C2 — #108 Yii2 `Module::bootstrap()` executed twice
- Idempotence guard; `registerCollectors()` resets instead of appending; no duplicate URL-rule proxies / handlers.

### [x] C3 — #113 Relative asset paths under `/debug`
- Every server that emits the panel HTML injects `<base href="<mount>/">` (`PanelHtml` helper shared by `PanelController`, the `adp serve` router and adapter controllers); service worker resolves against `registration.scope`; router basename defaults to the mount; favicon/manifest links are relative.
- `PanelController` no longer collapses `staticUrl` `'/'` → `''`; Symfony extension precedence bug (`A && B || C`) fixed.

### [x] C4 — #111 Toolbar metric buttons open the panel
- `FloatMetrics` / `SideMetrics` chips navigate to the collector; the double mount-prefix (`/debug/debug/debug?…`) is gone; fallback to `window.open` when the iframe is disabled.

## 4. Security (High)

### [x] D1 — Ingestion `debugId` path traversal → validated id format before `FileStorage::write()`
### [x] D2 — `adp serve` shipped with allow-all IP filter and empty token → loopback-only by default, token required off-loopback
### [x] D3 — `inspectorUrl` SSRF → scheme/host policy at registration and before proxying
### [x] D4 — Environment collector leaked every env var → secret-like keys masked
### [x] D5 — Path containment used `str_starts_with` without a separator (`/srv/app-backup` passed for `/srv/app`)
### [x] D6 — `class_exists()` with autoload on user input
### [x] D7 — ACP control socket under world-writable temp dir
### [~] D8 — Exception traces returned to the client (`ResponseDataWrapper`) — now behind a debug flag; default stays on for local dev

## 5. Timeouts (High — CLAUDE.md hard rule)

### [x] E1 — Ten inspector commands used `setTimeout(null)` → 120s shared cap
### [x] E2 — `EnvironmentCollector` git subprocesses had no timeout and could deadlock on stderr
### [x] E3 — `Broadcaster` sockets without timeout; `MagoCommand` `@shell_exec` per listing

## 6. Correctness (Medium)

### [x] F1 — `BroadcastingStorage` broadcast the oldest entry id (readAll is ascending)
### [x] F2 — `CollectorRepository` matched object ids by substring (`#12` matched `#123`)
### [x] F3 — `CodeCoverageController` started and stopped the driver immediately (always 0%)
### [x] F4 — `RequestController` 500 on entries without the request collector
### [x] F5 — GC lock file unlinked while held; duplicate `GarbageCollector` class
### [x] F6 — OpenAPI spec double-encoded; `JsonResponseFactory` 500 on invalid UTF-8; multi-value headers joined with `, ` (breaks `Set-Cookie`); `FrontendUpdateCommand` zip-slip + temp file leak
### [x] F7 — Yii2 `WebListener` hard-coded `/debug/api`; `timeline` collector toggle ignored

## 7. Frontend runtime bugs (Medium)

### [x] G1 — Null collector data crashed the Debug layout (`typeof null === 'object'`)
### [x] G2 — Auto-latest toggle was ignored by the SSE handler
### [x] G3 — `MemoryItem` crashed without `web`/`console` timing
### [x] G4 — Side-rail icons rendered `⏱` literally
### [x] G5 — Project-config SSE pinned to the initial base URL
### [x] G6 — `IFrameWrapper` listener leak; `ServerSentEventsObserver` double connection on reconnect
### [x] G7 — Unguarded `navigator.clipboard.writeText` (7 sites) → shared `copyText()` helper
### [x] G8 — Stale effect deps in `DebugToolbar`; `as any` preloaded state
### [x] G9 — Dead duplicate `packages/panel/src/service-worker.ts`
### [x] G10 — `mockServiceWorker.js` missing → browser e2e could never pass

## 8. Deferred / needs a decision

- `strictNullChecks` / `noImplicitAny` are off repo-wide (`libs/frontend/tsconfig.json`). Enabling them is the single biggest quality lever for the frontend; error counts per package are recorded in the branch summary. Do it package by package, sdk first.
- Frontend unit coverage: toolbar has 3 test files for 46 sources; `onConsoleLog: () => false` hides React warnings. Add a coverage threshold once the toolbar has baseline tests.
- `mago lint` has 55 issues outside the baseline and the baseline contains stale entries; `mago analyze` has 21. Fix them file by file, then regenerate the baseline (`composer lint:baseline`) — bulk regeneration re-adds ~1380 entries.
- Spiral bootloader wires 17 collectors vs 29 in the other adapters with no documented reason.
- Per-lib `composer.json` scripts are inconsistent and only 4 of 12 libs ship a `phpunit.xml.dist`.
- `docs/tasks/p4-debt.md` "E2E browser tests skip gracefully" describes a `markTestSkipped` workaround that contradicts CLAUDE.md; superseded by B1.

## Acceptance
- `make check` (incl. `review-checks`) and `make test` green locally.
- CI green on the branch with the new `ci-ok` aggregate.
- Issues #108, #111, #113, #114 reproducible-by-test and fixed.
