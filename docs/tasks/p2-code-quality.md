# P2 — Code Quality

Refactor recent additions and clear mago / phpunit warnings.

## Tasks

### [ ] D1 — `prependBrowserContext` helper extraction
- File: `libs/API/src/Llm/Controller/LlmController.php:508-595`
- Problem: 87 LOC, seven copies of `isset($context['X']) && is_string($context['X']) && $context['X'] !== ''`.
- Fix: extract `private function appendStringLine(array &$lines, array $context, string $key, string $label): void` and use for `title`, `userAgent`, `language`, `timezone`, `theme`, `referrer`.

### [ ] D2 — Deduplicate provider system-prompt injection
- File: `libs/API/src/Llm/Controller/LlmController.php:583` and `:607`
- Problem: identical "anthropic/openai → array_unshift, else merge into first user" logic in `prependBrowserContext` and `prependCustomPrompt`.
- Fix:
  - `private function supportsSystemRole(string $provider): bool` (returns true for `anthropic`, `openai`, anything else that gets added).
  - `private function injectPromptPrefix(array $messages, string $provider, string $prompt, string $userWrap = '[{}]'): array`.

### [ ] D3 — Split `LlmController` (650 LOC, 10+ actions)
- File: `libs/API/src/Llm/Controller/LlmController.php`
- Problem: God-controller: status, connect, OAuth, model, timeout, custom prompt, models, chat, history.
- Fix: split into
  - `LlmConnectionController` — status / connect / disconnect / OAuth.
  - `LlmSettingsController` — model / timeout / customPrompt.
  - `LlmChatController` — chat / history / models (or keep models in Settings).
- Update `ApiRoutes.php` accordingly. Keep DI backwards-compatible.

### [ ] D4 — Fix unreachable match arms in `DebugTailControllerTest`
- File: `libs/Adapter/Yii2/tests/Unit/Controller/DebugTailControllerTest.php:435-438`
- Mago: `unreachable-match-arm` (arms `2` and `default`).
- Fix: subject is mis-typed; verify `$callCount` increments or switch to `match(true)` with explicit conditions.

### [ ] D5 — Guard `$pipes[1]`/`$pipes[2]` in Symfony integration test
- File: `libs/Adapter/Symfony/tests/Integration/ConsoleProcessIntegrationTest.php:214-217`
- Mago: 4× `possibly-undefined-int-array-index`.
- Fix: after `proc_open`, assert `array_key_exists(1, $pipes)` / `(2, $pipes)` or `self::assertIsResource($pipes[1] ?? null)`.

### [ ] D6 — Remove redundant string cast
- File: `libs/Adapter/Yii2/src/Controller/DebugDumpController.php:103`
- Mago: `redundant-cast`.
- Fix: drop `(string)` around `$data[0]`.

### [ ] D7 — Migrate Yii2 tests to PHPUnit attributes
- Files (8): `libs/Adapter/Yii2/tests/Unit/Controller/*.php`, `libs/Adapter/Yii2/tests/Unit/Inspector/Yii2UrlMatcherAdapterTest.php`, plus `tests/E2E/InspectorPageTest::testInspectorPageLoads`.
- Problem: 97 `Metadata in doc-comment` deprecations; PHPUnit 12 drops support.
- Fix: replace `@dataProvider`, `@covers`, etc. with `#[DataProvider]`, `#[CoversClass]`.

## Acceptance
- `make mago` baseline warnings count reduced by the new fixes (regenerate baseline at the end).
- `make test-php` deprecation count = 0 for PHPUnit deprecations (Yii2 tests and E2E tests fixed).
- `LlmController` LOC < 250, no single method > 40 LOC.
