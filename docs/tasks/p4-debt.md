# P4 — Technical Debt

Lower priority cleanups.

## Tasks

### [~] Regenerate mago lint baseline — SKIPPED
- Tried `composer lint:baseline` after P2 fixes; the command generates a *fresh* baseline from every currently-known issue, so the file grew by ~1380 entries (the pre-existing baseline was curated).
- Action needed instead: go file-by-file, fix the underlying lint issues, then regenerate. Not batched here — track separately.

### [ ] Raise `libs/Cli` coverage above 60%
- Current: 41.1% (30/73 lines). Lowest in the repo.
- Add unit tests for the main commands (`BroadcastCommand`, `QueryCommand`, `ResetCommand`, `ServeCommand`, `McpCommand`). Mock storage + output.

### [ ] Revisit `yiisoft/*` runtime deps in Kernel
- `libs/Kernel/composer.json` keeps `yiisoft/strings`, `yiisoft/files`, `yiisoft/json`, `yiisoft/var-dumper`.
- Problem: name "yiisoft" in a framework-agnostic kernel is confusing; `libs/Kernel/CLAUDE.md` even dedicates a "Core infra policy" paragraph to explain why it is OK.
- Action: audit the usages:
  - `yiisoft/strings` → `CombinedRegexp`, `WildcardPattern` — small. Could inline or replace with `symfony/string` for consistency.
  - `yiisoft/json` → one-liner wrappers around `json_encode/json_decode`. Replace with direct PHP (throw `JsonException`).
  - `yiisoft/files` → check actual callsites.
  - `yiisoft/var-dumper` — heavier; probably keep, but move the "why" section into a README inside `libs/Kernel/src/DebugServer/`.

### [x] E2E browser tests skip-gracefully
- Running bare `phpunit` surfaced 15 errors because `tests/E2E/BrowserTestCase.php:276` throws `RuntimeException` when Chrome is missing.
- Fix applied in `tests/E2E/BrowserTestCase.php:setUpBeforeClass()`: wrap ChromeDriver startup + driver creation in a `try { ... } catch (RuntimeException) { self::$driver = null; }`. The per-test `setUp()` already calls `markTestSkipped('WebDriver not available.')` when `$driver` is null.
- Verified: `phpunit --testsuite E2E` without Chrome prints 54 skipped / 0 errors.

## Acceptance
- Per-task acceptance inside the items.
