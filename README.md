<p align="center">
  <strong>ADP — Application Development Panel</strong><br>
  The Universal PHP Debug Panel
</p>

<p align="center">
  <a href="https://github.com/app-dev-panel/app-dev-panel/actions/workflows/ci.yml"><img src="https://github.com/app-dev-panel/app-dev-panel/actions/workflows/ci.yml/badge.svg" alt="CI"></a>
  <a href="https://packagist.org/packages/app-dev-panel/kernel"><img src="https://img.shields.io/packagist/dt/app-dev-panel/kernel.svg" alt="Packagist Downloads"></a>
  <a href="https://packagist.org/packages/app-dev-panel/kernel"><img src="https://img.shields.io/packagist/v/app-dev-panel/kernel.svg" alt="Latest Stable Version"></a>
  <a href="https://github.com/app-dev-panel/app-dev-panel/blob/main/LICENSE.md"><img src="https://img.shields.io/badge/license-BSD--3--Clause-blue.svg" alt="License"></a>
  <img src="https://img.shields.io/badge/php-8.4+-8892BF.svg" alt="PHP 8.4+">
</p>

---

**One panel for all your PHP apps. And beyond.**

ADP is an open-source debug panel that works with **Laravel, Symfony, Yii 3, Yii 2** — and any PSR-compatible application. Unlike Telescope, Symfony Profiler, or Clockwork, ADP provides a unified interface across frameworks with features none of them offer.

## Quick Start

### Laravel

```bash
composer require app-dev-panel/adapter-laravel --dev
```

That's it. ADP auto-registers via Laravel package discovery. Open `/debug/api/` in your browser.

### Symfony

```bash
composer require app-dev-panel/adapter-symfony --dev
```

Register the bundle in `config/bundles.php`:

```php
AppDevPanel\Adapter\Symfony\AppDevPanelBundle::class => ['dev' => true, 'test' => true],
```

### Yii 3

```bash
composer require app-dev-panel/adapter-yiisoft --dev
```

Auto-registered via Yii config plugin. No manual setup needed.

### Yii 2

```bash
composer require app-dev-panel/adapter-yii2 --dev
```

Auto-registered via bootstrap. Auto-enables when `YII_DEBUG` is true.

### Standalone Server

```bash
composer require app-dev-panel/cli --dev
php vendor/bin/adp serve
```

## Why ADP?

| | ADP | Telescope | Symfony Profiler | Clockwork | Ray |
|---|:---:|:---------:|:----------------:|:---------:|:---:|
| Frameworks | **4+** | 1 | 1 | 3 | 2 |
| Auto-collectors | **28** | 14 | ~12 | ~10 | 0 |
| Live Inspector (20+ pages) | **yes** | — | — | — | — |
| Real-time SSE + UDP | **yes** | — | — | — | yes |
| Code generation | **yes** | — | — | — | — |
| Git integration | **yes** | — | — | — | — |
| DB browser | **yes** | — | — | — | — |
| Multi-app debugging | **yes** | — | — | — | — |
| Language-agnostic API | **yes** | — | — | — | — |
| Open source & free | **yes** | yes | yes | yes | no |

## Features

### 28 Data Collectors

Logs, events, HTTP requests, SQL queries, exceptions, cache, mail, queues, validation, routing, middleware, templates, security, filesystem, and more — all captured automatically.

### Inspector — Live Application Introspection

20+ pages to inspect your running application without CLI:

- **Routes** — browse and test route matching
- **Database** — schema browser with pagination
- **Git** — status, log, checkout from the panel
- **Composer** — package management
- **Cache** — view and clear cache
- **Config** — DI container parameters
- **Commands** — run CLI commands from the UI
- **File Explorer** — navigate project files
- **Translations** — view and edit translations
- **OPcache** — statistics and configuration
- **PHPInfo** — full PHP configuration

### Developer Toolkit

- **GenCode** — code generation with preview and diff
- **cURL Builder** — generate cURL commands from any request
- **Request Replay** — re-execute requests with one click
- **Command Palette** (Ctrl+K) — navigate like in VS Code
- **Dark Mode** — full dark theme support
- **PWA** — install as a desktop/mobile app, works offline

### Multi-App & Polyglot

- **Service Registry** — debug multiple applications from one panel
- **Ingestion API** (OpenAPI 3.1) — send debug data from Python, Node.js, or any language

## Architecture

```
Any PHP App → Adapter → Kernel (28 collectors) → API (40+ endpoints) → React SPA
```

| Layer | Package | Purpose |
|-------|---------|---------|
| **Kernel** | `app-dev-panel/kernel` | Core engine: debugger lifecycle, collectors, storage, PSR proxies |
| **API** | `app-dev-panel/api` | REST + SSE endpoints for debug data and live inspection |
| **CLI** | `app-dev-panel/cli` | Console commands: debug server, query, reset, serve |
| **Adapters** | `app-dev-panel/adapter-*` | Framework bridges (Laravel, Symfony, Yii 3, Yii 2, Cycle) |
| **Frontend** | `@app-dev-panel/*` | React SPA + embeddable toolbar |

## Repository Structure

```
libs/
├── Kernel/              # Core engine
├── API/                 # HTTP API
├── Cli/                 # CLI commands
├── Testing/             # Test fixtures
├── Adapter/
│   ├── Laravel/         # Laravel adapter
│   ├── Symfony/         # Symfony adapter
│   ├── Yiisoft/         # Yii 3 adapter
│   ├── Yii2/            # Yii 2 adapter
│   └── Cycle/           # Cycle ORM (DB schema only)
└── frontend/            # React/TypeScript frontend
    └── packages/
        ├── panel/       # Main SPA
        ├── toolbar/     # Embeddable toolbar
        └── sdk/         # Shared SDK
playground/              # Demo apps per framework
```

## Development

```bash
make install             # Install all dependencies (PHP + frontend + playgrounds)
make all                 # Run all checks + all tests
make test                # Run all tests (PHP + frontend)
make check               # Run all code quality checks
make fix                 # Auto-fix all code quality issues
```

See [CLAUDE.md](CLAUDE.md) for full development documentation.

## Contributing

Contributions are welcome! Please see the development section above for setup instructions.

## License

BSD-3-Clause. See [LICENSE.md](LICENSE.md) for details.
