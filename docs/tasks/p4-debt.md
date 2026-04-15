# P4 — Technical Debt

Lower priority cleanups.

## Tasks

### [~] Regenerate mago lint baseline — SKIPPED
- Tried `composer lint:baseline` after P2 fixes; the command generates a *fresh* baseline from every currently-known issue, so the file grew by ~1380 entries (the pre-existing baseline was curated).
- Action needed instead: go file-by-file, fix the underlying lint issues, then regenerate. Not batched here — track separately.

### [x] Raise `libs/Cli` coverage above 60%
- Coverage banner in the root `CLAUDE.md` was stale (claimed 6 tests / 41.1%). Actual suite ran 188 tests across 12 command-test files.
- Gaps found + filled:
  - `McpServeCommand` had no test at all → added `libs/Cli/tests/Unit/Command/McpServeCommandTest.php` (7 tests: missing-path failure, command registration, option metadata, defaults, help text).
  - `DebugResetCommand` had a single no-op test → expanded to 4 tests (success exit code, constant matches attribute, help text, exception pass-through).
- Updated `libs/Cli/CLAUDE.md` test listing and the stats table in `CLAUDE.md`.
- Final: 198 tests, all green.

### [x] Revisit `yiisoft/*` runtime deps in Kernel
- Audit completed. Drop verdict:
  - `yiisoft/json` — **removed**. Thin wrapper over `json_encode/decode`; Kernel only encodes arrays/scalars so none of `Yiisoft\Json\Json`'s iterable-flattening paths were exercised. Replaced with `AppDevPanel\Kernel\Helper\Json` which calls `json_encode($v, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)` and `json_decode($j, true, 512, JSON_THROW_ON_ERROR)`. `libs/Kernel/composer.json` no longer depends on `yiisoft/json`.
  - `yiisoft/strings` — **keep**. `CombinedRegexp` (used in `FilesystemStreamProxy`, `HttpStreamProxy`, `BacktraceIgnoreMatcher`) compiles many regexes into a single alternation — this is a hot path, replacing it by hand would regress performance. `WildcardPattern` implements shell-glob matching used for ignore patterns.
  - `yiisoft/files` — **keep**. `FileHelper::removeDirectory` is recursive and handles symlinks safely; reimplementing it inline adds ~25 LOC and duplicates existing battle-tested code.
  - `yiisoft/var-dumper` — **keep**. `ClosureExporter` + `VarDumper` are the heart of `Dumper` / `DumpContext`; swapping them would be a much bigger refactor than justified here.
- CLAUDE.md updated with the decision + justifications for the remaining three deps.

### [x] E2E browser tests skip-gracefully
- Running bare `phpunit` surfaced 15 errors because `tests/E2E/BrowserTestCase.php:276` throws `RuntimeException` when Chrome is missing.
- Fix applied in `tests/E2E/BrowserTestCase.php:setUpBeforeClass()`: wrap ChromeDriver startup + driver creation in a `try { ... } catch (RuntimeException) { self::$driver = null; }`. The per-test `setUp()` already calls `markTestSkipped('WebDriver not available.')` when `$driver` is null.
- Verified: `phpunit --testsuite E2E` without Chrome prints 54 skipped / 0 errors.

## Acceptance
- Per-task acceptance inside the items.
