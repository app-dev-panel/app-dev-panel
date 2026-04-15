# P2 — Code Quality

Refactor recent additions and clear mago / phpunit warnings.

## Tasks

### [x] D1 — `prependBrowserContext` helper extraction
- File: `libs/API/src/Llm/Controller/LlmController.php:508-595`
- Problem: 87 LOC, seven copies of `isset($context['X']) && is_string($context['X']) && $context['X'] !== ''`.
- Fix: extract `private function appendStringLine(array &$lines, array $context, string $key, string $label): void` and use for `title`, `userAgent`, `language`, `timezone`, `theme`, `referrer`.

### [x] D2 — Deduplicate provider system-prompt injection
- File: `libs/API/src/Llm/Controller/LlmController.php:583` and `:607`
- Problem: identical "anthropic/openai → array_unshift, else merge into first user" logic in `prependBrowserContext` and `prependCustomPrompt`.
- Fix:
  - `private function supportsSystemRole(string $provider): bool` (returns true for `anthropic`, `openai`, anything else that gets added).
  - `private function injectPromptPrefix(array $messages, string $provider, string $prompt, string $userWrap = '[{}]'): array`.

### [x] D3 — Extract `LlmContextBuilder` from `LlmController`
- File: `libs/API/src/Llm/Controller/LlmController.php` → `libs/API/src/Llm/LlmContextBuilder.php`
- Full "split into three controllers" turned out to be the wrong hammer: it would force DI-config changes in all four adapters (Yii3/Symfony/Laravel/Yii2/Cycle) for zero architectural win.
- Better fix applied: extract `LlmContextBuilder` — `prependBrowserContext`, `prependCustomPrompt`, `injectPromptPrefix`, `supportsSystemRole`, `stringField`, `appendStringLine`, `appendSizeLine`, `parseUrlQueryContext`. 179 LOC moved out, controller down from 660 → 550 LOC.
- New arg on `LlmController::__construct` has a default (`new LlmContextBuilder()`) so every adapter's DI wiring keeps working untouched.
- `LlmContextBuilder` now has a dedicated test file (`libs/API/tests/Unit/Llm/LlmContextBuilderTest.php`, 10 tests).
- Existing `LlmControllerTest` cases that used reflection on `prependBrowserContext` were rewritten to target the builder directly.

### [x] D4 — Fix unreachable match arms in `DebugTailControllerTest`
- File: `libs/Adapter/Yii2/tests/Unit/Controller/DebugTailControllerTest.php:435-438`
- Mago: `unreachable-match-arm` (arms `2` and `default`).
- Fix: subject is mis-typed; verify `$callCount` increments or switch to `match(true)` with explicit conditions.

### [x] D5 — Guard `$pipes[1]`/`$pipes[2]` in Symfony integration test
- File: `libs/Adapter/Symfony/tests/Integration/ConsoleProcessIntegrationTest.php:214-217`
- Mago: 4× `possibly-undefined-int-array-index`.
- Fix: after `proc_open`, assert `array_key_exists(1, $pipes)` / `(2, $pipes)` or `self::assertIsResource($pipes[1] ?? null)`.

### [x] D6 — Remove redundant string cast
- File: `libs/Adapter/Yii2/src/Controller/DebugDumpController.php:103`
- Mago: `redundant-cast`.
- Fix: drop `(string)` around `$data[0]`.

### [x] D7 — Migrate Yii2 tests to PHPUnit attributes
- Files (8): `libs/Adapter/Yii2/tests/Unit/Controller/*.php`, `libs/Adapter/Yii2/tests/Unit/Inspector/Yii2UrlMatcherAdapterTest.php`, plus `tests/E2E/InspectorPageTest::testInspectorPageLoads`.
- Problem: 97 `Metadata in doc-comment` deprecations; PHPUnit 12 drops support.
- Fix: replace `@dataProvider`, `@covers`, etc. with `#[DataProvider]`, `#[CoversClass]`.

## Acceptance
- `make mago` baseline warnings count reduced by the new fixes (regenerate baseline at the end).
- `make test-php` deprecation count = 0 for PHPUnit deprecations (Yii2 tests and E2E tests fixed).
- `LlmController` LOC < 250, no single method > 40 LOC.
