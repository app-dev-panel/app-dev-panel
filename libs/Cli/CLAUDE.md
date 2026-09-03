# CLI Module

Provides console commands for managing the ADP debug system.

## Package

- Composer: `app-dev-panel/cli`
- Namespace: `AppDevPanel\Cli\`
- PHP: 8.4+
- Dependencies: `app-dev-panel/kernel`, `app-dev-panel/api`, `app-dev-panel/mcp-server`, `app-dev-panel/frontend-assets`, Symfony Console, Symfony Process

## Directory Structure

```
src/
├── Command/
│   ├── DebugServerCommand.php          # Start debug socket server (dev)
│   ├── DebugResetCommand.php           # Clear debug data (debug:reset)
│   ├── DebugServerBroadcastCommand.php # Broadcast test messages (dev:broadcast)
│   ├── DebugQueryCommand.php           # Query stored debug data (debug:query)
│   ├── DebugSummaryCommand.php         # Show brief summary of debug entry (debug:summary)
│   ├── DebugDumpCommand.php            # View dumped objects (debug:dump)
│   ├── DebugTailCommand.php            # Watch entries in real-time (debug:tail)
│   ├── ServeCommand.php                # Start HTTP debug server (serve)
│   ├── McpServeCommand.php             # Start MCP server for AI integration (mcp:serve)
│   ├── FrontendUpdateCommand.php       # Download latest frontend build (frontend:update)
│   ├── ArchiveExtractor.php            # zip / tar.gz extraction with zip-slip guard (used by frontend:update)
│   ├── InspectConfigCommand.php        # Inspect application config (inspect:config)
│   ├── InspectDatabaseCommand.php      # Inspect database schema/data (inspect:db)
│   └── InspectRoutesCommand.php        # Inspect application routes (inspect:routes)
└── Server/
    ├── ServerSecurityConfig.php        # ADP_ALLOWED_IPS / ADP_AUTH_TOKEN resolution, non-loopback bind guard
    └── server-router.php               # Router for built-in PHP server (bootstraps API, serves index.html)
tests/
└── Unit/
    ├── Command/
    │   ├── ArchiveExtractorTest.php
    │   ├── DebugDumpCommandTest.php
    │   ├── DebugQueryCommandTest.php
    │   ├── DebugServerBroadcastCommandTest.php
    │   ├── DebugServerCommandTest.php
    │   ├── DebugSummaryCommandTest.php
    │   ├── DebugTailCommandTest.php
    │   ├── FrontendUpdateCommandTest.php
    │   ├── InspectConfigCommandTest.php
    │   ├── InspectDatabaseCommandTest.php
    │   ├── InspectRoutesCommandTest.php
    │   ├── McpServeCommandTest.php
    │   ├── ResetCommandTest.php
    │   └── ServeCommandTest.php
    └── Server/
        └── ServerSecurityConfigTest.php
```

## Commands

### `dev` — Debug Server

Starts a UDP socket server that listens for real-time debug messages from the application.

```bash
php yii dev                         # Default: 0.0.0.0:8890
php yii dev -a 127.0.0.1 -p 9000   # Custom address and port
```

The server receives and categorizes messages:
- `MESSAGE_TYPE_VAR_DUMPER` — Variable dumps
- `MESSAGE_TYPE_LOGGER` — Log messages
- Plain text messages

Handles `SIGINT` (Ctrl+C) for graceful shutdown.

### `debug:reset` — Clear Debug Data

Stops the debugger and clears all stored debug data.

```bash
php yii debug:reset
```

Calls `Debugger::stop()` and `StorageInterface::clear()`.

### `dev:broadcast` — Broadcast Test Messages

Sends test messages to all connected debug server clients. Useful for verifying connectivity.

```bash
php yii dev:broadcast                    # Default: "Test message"
php yii dev:broadcast -m "Hello world"   # Custom message
```

Broadcasts in both `MESSAGE_TYPE_LOGGER` and `MESSAGE_TYPE_VAR_DUMPER` formats through `AppDevPanel\Kernel\DebugServer\BroadcasterInterface` (constructor arg, default `Broadcaster`; inject a stub in tests). Every error returned by `broadcast()` is printed as a warning and the command exits with `Command::FAILURE`; `--env=test` short-circuits to `SUCCESS` without sending.

### `debug:query` — Query Debug Data

Query stored debug data from the CLI. Subcommands: `list`, `view`.

```bash
debug:query list                          # List recent entries (default 20)
debug:query list --limit=5                # Limit entries
debug:query list --json                   # Raw JSON output
debug:query view <id>                     # Full entry data
debug:query view <id> -c <CollectorFQCN>  # Specific collector data
```

Uses `CollectorRepositoryInterface` to read from storage.

### `serve` — Standalone ADP API Server

Starts a standalone HTTP server using PHP built-in server, serving the ADP API directly. When `--frontend-path` is omitted, the command auto-resolves the bundle via `AppDevPanel\FrontendAssets\FrontendAssets::path()` (from `app-dev-panel/frontend-assets`), so the full panel SPA is served at `/` out of the box. When a frontend path is available, the process is launched with `php -S host:port -t <frontendPath>` so the built-in server resolves static files from the bundle directory.

```bash
serve                                              # Default: 127.0.0.1:8888, panel auto-resolved from FrontendAssets
serve --host=0.0.0.0 --port=9000 --auth-token=s3cret   # Non-loopback bind — token is mandatory
serve --allowed-ips=10.0.0.5,10.0.0.6              # Restrict API clients (default: loopback only)
serve --storage-path=/path/to/debug/data           # Custom storage
serve --frontend-path=/path/to/built/assets        # Override bundle path (e.g. local dev build)
```

#### Network security (`Server/ServerSecurityConfig.php`)

The API behind `server-router.php` can read files, run commands, `composer require` and execute raw SQL, so the server is deny-by-default:

| Setting | CLI option | Env var | Default | Notes |
|---------|-----------|---------|---------|-------|
| Allowed client IPs | `--allowed-ips` | `ADP_ALLOWED_IPS` | `127.0.0.1, ::1` (`LOOPBACK_IPS`) | Comma separated. Literal `*` (`ALLOW_ALL`) opens the server to everyone; empty/blank input falls back to loopback |
| Auth token | `--auth-token` | `ADP_AUTH_TOKEN` | `''` | Sent by clients as `X-Debug-Token` (`TokenAuthMiddleware`); **required** when `--host` is not loopback |

- CLI options override the env vars (`ServeCommand::resolveSecurity()` merges `array_filter([...], is_string(...)) + getenv()`).
- `ServerSecurityConfig::isLoopbackHost()` treats `localhost`, `::1`, `[::1]` and any `127.*` address as loopback.
- `unsafeBindReason(string $bindHost): ?string` returns an error message when the host is non-loopback and the token is empty. `ServeCommand` calls it **before** spawning `php -S` and exits with `Command::FAILURE` (fail-fast, nothing listens); `server-router.php` re-checks it per request and answers HTTP 500 JSON if the child process was started with an unsafe environment.
- `isStrict()` is `true` unless `*` was given — passed as the strict flag to `IpFilterMiddleware`, so an empty allow-list rejects every client instead of allowing all.
- `toEnvironment(string $bindHost)` returns `ADP_BIND_HOST`, `ADP_ALLOWED_IPS` (`*` when unrestricted) and `ADP_AUTH_TOKEN` — spread into the `Process` env so the router reproduces the same config via `fromEnvironment(getenv())`.
- `describeAllowedIps()` / `describeAuthToken()` feed the startup summary (`(everyone)` / `(set)` / `(none)`); the token value is never printed.

#### Router (`Server/server-router.php`)

- `/debug/api/*` and `/inspect/api/*` go through `ApiApplication` with `IpFilterMiddleware` + `TokenAuthMiddleware` built from `ServerSecurityConfig`.
- Any other path with `ADP_FRONTEND_PATH` set: an existing file under the bundle dir is returned via `return false` (PHP built-in static handler); everything else (`/`, `/index.html`, deep links such as `/debug/logs`) is answered with `index.html` passed through `AppDevPanel\Api\Panel\PanelHtml::injectBaseHref($html, '/')`. The prebuilt `index.html` ships a `<base href="./" data-adp-base />` placeholder; the router rewrites it to `<base href="/" data-adp-base />` so relative asset URLs resolve from the mount root at any depth (issue #113).
- Without a frontend path, non-API requests get a JSON 404.

### `frontend:update` — Download Latest Frontend Build

Fetches `frontend-dist.zip` from the [latest GitHub Release](https://github.com/app-dev-panel/app-dev-panel/releases) and extracts it into `--path`. The archive contains **both** the panel SPA (`index.html`, `bundle.js`, `assets/`) and the toolbar widget (`toolbar/bundle.js`, `toolbar/bundle.css`). Intended for PHAR users or environments where Composer is not the update vehicle; composer-based installs update via `composer update app-dev-panel/frontend-assets`.

```bash
frontend:update check                               # Show current vs latest version
frontend:update check --json                        # Machine-readable output
frontend:update download --path=/path/to/dist       # Install latest panel + toolbar build
```

Writes a `.adp-version` file next to `index.html` so subsequent `check` invocations can compare installed vs latest. Emits a warning if the installed directory has `index.html` but no `toolbar/bundle.js` — typical for archives produced before toolbar was bundled. The GitHub API call is capped at 10s (connect 5s); the asset download at 30s (connect 5s).

Archives are unpacked by `Command/ArchiveExtractor.php` (`extractZip()` / `extractTarGz()`). Before anything is written, `assertSafeEntry()` walks every entry name (backslashes normalised to `/`) and throws `RuntimeException` for empty names, absolute paths (`/...`, `C:...`) or any `..` segment — the zip-slip guard. `.tar.gz` goes through `PharData::decompress()`; the intermediate `.tar` is unlinked in a `finally` block. The download uses `tempnam()` plus a real-extension sibling (`<tmp>.zip` / `<tmp>.tar.gz`, needed by `ZipArchive`/`PharData` sniffing); both files are removed in `finally`, including on failure.

## Standalone Binary — `bin/adp`

`bin/adp` is the standalone CLI entry point (`vendor/bin/adp` after Composer install). Registers `ServeCommand`, `FrontendUpdateCommand`, and (when `app-dev-panel/testing` is installed) `DebugFixturesCommand`.

Autoloader lookup order (first hit wins):
1. `$GLOBALS['_composer_autoload_path']` — set by the Composer-generated `vendor/bin/adp` proxy, points at `vendor/autoload.php`.
2. `__DIR__ . '/../../../autoload.php'` — Composer install layout (`vendor/app-dev-panel/cli/bin/` → `vendor/autoload.php`).
3. `__DIR__ . '/../../../vendor/autoload.php'` — monorepo layout (`libs/Cli/bin/` → root `vendor/autoload.php`).
4. `__DIR__ . '/../vendor/autoload.php'` — standalone checkout with its own `vendor/`.

Symfony Console 8 removed `Application::add()` in favour of `addCommand()`. The binary wraps command registration in a callable that probes `method_exists($app, 'addCommand')` and falls back to `add()` on 6.x/7.x — so the full range declared in `composer.json` (`symfony/console: ^6 | ^7 | ^8`) is supported.

### `mcp:serve` — MCP Server

Starts an MCP (Model Context Protocol) server over stdio, exposing ADP debug data to AI assistants.

```bash
mcp:serve --storage-path=/path/to/debug/data    # Required: path to debug storage
```

Creates `FileStorage` and `McpToolRegistry`, then runs `McpServer` over `StdioTransport`.

## Testing

```bash
composer test                                          # Runs PHPUnit (all suites)
php vendor/bin/phpunit --testsuite Cli --no-coverage   # Cli suite only — 241 tests
```

| Test file | Tests | Covers |
|-----------|------:|--------|
| `Command/ArchiveExtractorTest.php` | 4 | zip-slip rejection, zip/tar.gz extraction, temp cleanup |
| `Command/DebugDumpCommandTest.php` | 12 | `debug:dump` |
| `Command/DebugQueryCommandTest.php` | 16 | `debug:query list/view` |
| `Command/DebugServerBroadcastCommandTest.php` | 9 | `BroadcasterInterface` stub, FAILURE on send errors, `--env=test` |
| `Command/DebugServerCommandTest.php` | 16 | `dev` socket server |
| `Command/DebugSummaryCommandTest.php` | 27 | `debug:summary` |
| `Command/DebugTailCommandTest.php` | 13 | `debug:tail` |
| `Command/FrontendUpdateCommandTest.php` | 22 | release/tag lookup, GitHub token, zip-slip refusal, no temp files left behind |
| `Command/InspectConfigCommandTest.php` | 19 | `inspect:config` |
| `Command/InspectDatabaseCommandTest.php` | 20 | `inspect:db` |
| `Command/InspectRoutesCommandTest.php` | 26 | `inspect:routes` |
| `Command/McpServeCommandTest.php` | 7 | `mcp:serve` |
| `Command/ResetCommandTest.php` | 4 | `debug:reset` |
| `Command/ServeCommandTest.php` | 18 | option defaults, fail-fast on non-loopback bind without token, security summary output |
| `Server/ServerSecurityConfigTest.php` | 8 | IP parsing, `*`, loopback detection, `unsafeBindReason()`, `toEnvironment()` |

No test spawns a real `php -S`; `ServeCommand` accepts an injectable `PhpExecutableFinder` and router script path.
