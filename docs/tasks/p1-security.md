# P1 — Security

Secure-by-default hardening for the HTTP API. Today multiple endpoints allow full RCE (composer require, ACP agent spawn, arbitrary SQL) but defaults ship with `allowedIps = []`, `token = ''`, and `Access-Control-Allow-Origin: *`.

## Tasks

### [x] C1 — CORS configurable, not wildcard by default
- File: `libs/API/src/Middleware/CorsMiddleware.php:28`
- Problem: hardcoded `Access-Control-Allow-Origin: *`.
- Fix:
  - Accept `array $allowedOrigins` in the constructor.
  - Default to `[]` meaning "no CORS headers set".
  - If configured, echo back the request `Origin` only when it matches; reject otherwise with no ACAO header.
- Wire-up in each adapter config (Yii3/Symfony/Laravel/Yii2) — default empty.

### [x] C2 — IpFilterMiddleware: drop by default, support X-Forwarded-For
- File: `libs/API/src/Middleware/IpFilterMiddleware.php:28`
- Problem: empty `allowedIps` = everyone passes; `REMOTE_ADDR` ignores proxy headers.
- Fix:
  - Keep "empty = allow" semantics (so existing installs still work) but add a `strict` flag that flips it to "empty = deny-all".
  - Optional `trustedProxies` list; when set, resolve client IP from `X-Forwarded-For` chain.

### [x] C3 — TokenAuth: log/flag missing token on production
- File: `libs/API/src/Debug/Middleware/TokenAuthMiddleware.php:25`
- Problem: empty token silently allows everything.
- Fix: if `APP_ENV`/`debug=false` and token is empty, emit warning via PSR-3 logger (no exception — don't break existing dev setups).

### [x] C4 — ACP command allowlist
- File: `libs/API/src/Llm/Controller/LlmController.php:84-101` (`connectAcp`)
- Problem: user supplies `acpCommand` → spawned via `proc_open`. `escapeshellarg` blocks shell injection but not arbitrary binary execution.
- Fix:
  - Introduce `AcpCommandAllowlist` (default: `['npx', 'claude', 'gemini', 'node']`, plus `PHP_BINARY`).
  - Compare `basename($command)` against allowlist; reject with 400 otherwise.
  - Make the allowlist injectable for adapters to extend.

### [x] C5 — Guard destructive Composer/Command endpoints
- File: `libs/API/src/Inspector/Controller/ComposerController.php:73-103`, `CommandController.php`
- Problem: `composer require` runs arbitrary package; post-install scripts = RCE.
- Fix:
  - Add `ApiSecurityConfig::$allowDestructiveOperations` (default `false`).
  - `ComposerController::require` + command/exec endpoints: return 403 when flag is off.
  - Document the flag in `libs/API/CLAUDE.md`.

### [~] C6 — FileController::resolveClassFile sandbox check — WONT FIX
- File: `libs/API/src/Inspector/Controller/FileController.php:37-58`, `readClassFile:169-193`
- Verdict: intentional. The method already separates "class-sourced" reads from path-sourced reads and exposes `insideRoot: bool` in the response so the UI can distinguish vendor files from project files. The comment on `readClassFile` ("No root-path restriction — if the class is loaded by PHP, its source is trusted and always readable.") documents the design. Attack surface is limited to files PHP can autoload, which are the project's own PHP sources anyway.
- Follow-up: none required here; the broader protection is P1 C3/C5 — require auth / disable on production.

### [x] C7 — SqliteStorage: remove unsafe `default => $type`
- File: `libs/Kernel/src/Storage/SqliteStorage.php:129-134` and `:149-154`
- Problem: unknown `$type` falls through into `"SELECT {$type} FROM entries"` — column-name SQL injection if ever called with external data.
- Fix: replace `default => $type` with `default => throw new \InvalidArgumentException("Unknown storage type: {$type}")`.

## Acceptance
- `make test-php` passes.
- New unit tests for `CorsMiddleware`, `AcpCommandAllowlist`, and `SqliteStorage` exception path.
- Security defaults documented in `libs/API/CLAUDE.md`.
