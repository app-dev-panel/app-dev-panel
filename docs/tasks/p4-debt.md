# P4 — Technical Debt

Lower priority cleanups.

## Tasks

### [ ] Regenerate mago lint baseline
- After P2 fixes, run `composer lint:baseline` to drop stale suppressions.
- Verify: `diff` on `mago-lint-baseline.php` shows only removed entries (no additions).

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

### [ ] E2E browser tests skip-gracefully
- Running bare `phpunit` surfaces 15 errors because `tests/E2E/BrowserTestCase.php:276` throws `RuntimeException` when Chrome is missing.
- Fix: `markTestSkipped(...)` instead of throwing when `CHROME_BINARY` is not detected, so raw phpunit runs cleanly in CI without Chrome.

## Acceptance
- Per-task acceptance inside the items.
