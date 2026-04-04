<div align="center">

# Application Development Panel

### The Universal PHP Debug Panel

**One panel for Symfony, Laravel, Yii — and beyond.**

<a href="https://github.com/app-dev-panel/app-dev-panel/actions/workflows/ci.yml"><img src="https://github.com/app-dev-panel/app-dev-panel/actions/workflows/ci.yml/badge.svg" alt="CI"></a>
<a href="https://packagist.org/packages/app-dev-panel/kernel"><img src="https://img.shields.io/packagist/dt/app-dev-panel/kernel.svg" alt="Packagist Downloads"></a>
<a href="https://packagist.org/packages/app-dev-panel/kernel"><img src="https://img.shields.io/packagist/v/app-dev-panel/kernel.svg" alt="Latest Stable Version"></a>
<a href="https://github.com/app-dev-panel/app-dev-panel/blob/main/LICENSE.md"><img src="https://img.shields.io/badge/license-BSD--3--Clause-blue.svg" alt="License"></a>
<img src="https://img.shields.io/badge/php-8.4+-8892BF.svg" alt="PHP 8.4+">

<br/>

**30 collectors &middot; 28 live inspector pages &middot; 40+ API endpoints**<br/>
**4 framework adapters &middot; Language-agnostic API &middot; 100% free & open-source**

<br/>

[Getting Started](#-quick-start) · [Why ADP?](#-why-adp) · [Features](#-features) · [Architecture](#-architecture) · [Contributing](#-contributing) · [Support](#-support-the-project) · [Documentation](https://app-dev-panel.github.io/app-dev-panel/)

</div>

---

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
composer require app-dev-panel/adapter-yii3 --dev
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

---

## Why ADP?

> *"Switch projects, not tools."*

Every PHP framework has its own debug tool — **and they don't talk to each other.** Telescope is Laravel-only. Symfony Profiler is Symfony-only. Clockwork covers a few, but with limited depth.

ADP is **the only debug panel built for the entire PHP ecosystem.** One install, one UI, every framework.

| | **ADP** | Telescope | Symfony Profiler | Clockwork | Ray |
|---|:---:|:---------:|:----------------:|:---------:|:---:|
| **Frameworks** | **4+ any PSR** | 1 | 1 | 3 | 2 |
| **Auto-collectors** | **30** | 14 | ~12 | ~10 | 0 |
| **Live Inspector** | **28 pages** | — | — | — | — |
| **Real-time streaming** | **UDP + SSE** | HTTP poll | HTTP poll | HTTP poll | WebSocket |
| **Code generation** | **Yes** | — | — | — | — |
| **Git integration** | **Yes** | — | — | — | — |
| **DB schema browser** | **Yes** | — | — | — | — |
| **Multi-app debugging** | **Yes** | — | — | — | — |
| **Language-agnostic API** | **OpenAPI 3.1** | — | — | — | — |
| **Free & open-source** | **Forever** | Yes | Yes | Yes | **$79/yr** |

---

## Features

### 30 Data Collectors — Install and Forget

Everything is captured automatically. No configuration, no boilerplate.

| Category | What You See |
|----------|-------------|
| **Logging** | All logs with levels, `dump()` calls |
| **HTTP** | Incoming & outgoing requests, headers, bodies |
| **Database** | SQL queries, execution time, bindings, backtrace, **EXPLAIN in panel** |
| **Errors** | Stack traces, chained exceptions, context |
| **Events** | All dispatched events with timing |
| **Performance** | Timeline waterfall, memory usage, request duration |
| **Middleware** | Middleware stack with before/handler/after phases and timings |
| **Cache** | Hit/miss ratios, operations, keys |
| **Mail** | Sent emails with full preview |
| **Queues** | Jobs, statuses, duration |
| **Validation** | Rules and validation errors |
| **Routing** | Matched routes, controllers |
| **Templates** | Rendered templates (Twig, Blade, etc.) and assets |
| **Security** | Users, roles, firewall |
| **Filesystem** | File operations |
| **Services** | DI container services, method calls |

### Inspector — X-Ray Your Running App

> **No competitor has this.** 28 live introspection pages — no CLI, no SSH, no restart needed.

| Page | What It Does |
|------|-------------|
| **Routes** | Browse all routes with validation and testing |
| **Database** | Explore DB schema, browse tables with pagination |
| **Git** | Status, log, checkout — right from the panel |
| **Composer** | Package management without the terminal |
| **Cache** | View and clear cache entries |
| **OPcache** | Full OPcache statistics and config |
| **Translations** | View and edit translations live |
| **Tests** | Run your test suite from the UI |
| **File Explorer** | Navigate project files |
| **Commands** | Execute CLI commands from the browser |
| **Container** | Browse all DI services |
| **Configuration** | Inspect DI parameters |
| **PHPInfo** | Full PHP configuration |

### Developer Toolkit

| Tool | Description |
|------|-------------|
| **GenCode** | Code generation with preview and diff |
| **cURL Builder** | Generate cURL commands from any captured request |
| **Request Replay** | Re-execute requests with one click |
| **OpenAPI** | Built-in Swagger UI for the debug API |
| **Command Palette** (Ctrl+K) | Navigate like in VS Code |
| **Dark Mode** | Full dark/light theme support |
| **PWA + Offline** | Install as desktop/mobile app, works offline |
| **Fuzzy Search** | Works across keyboard layouts (QWERTY/JCUKEN) |

### Multi-App & Polyglot

- **Service Registry** — Debug multiple applications from one panel. Microservices? One ADP for all.
- **Ingestion API** (OpenAPI 3.1) — Send debug data from **Python, Node.js, or any language**. Not just PHP anymore.

---

## Architecture

```
Any PHP App → Adapter → Kernel (30 collectors) → API (40+ endpoints) → React SPA
```

| Layer | Package | Purpose |
|-------|---------|---------|
| **Kernel** | `app-dev-panel/kernel` | Core engine: debugger lifecycle, collectors, storage, PSR proxies |
| **API** | `app-dev-panel/api` | REST + SSE endpoints for debug data and live inspection |
| **CLI** | `app-dev-panel/cli` | Console commands: debug server, query, reset, serve |
| **Adapters** | `app-dev-panel/adapter-*` | Framework bridges (Laravel, Symfony, Yii 3, Yii 2, Cycle) |
| **Frontend** | `@app-dev-panel/*` | React 19 SPA + embeddable toolbar + shared SDK |

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
│   ├── Yii3/            # Yii 3 adapter
│   ├── Yii2/            # Yii 2 adapter
│   └── Cycle/           # Cycle ORM (DB schema only)
└── frontend/            # React/TypeScript frontend
    └── packages/
        ├── panel/       # Main SPA
        ├── toolbar/     # Embeddable toolbar
        └── sdk/         # Shared SDK
playground/              # Demo apps per framework
```

---

## Development

```bash
make install             # Install all dependencies (PHP + frontend + playgrounds)
make all                 # Run all checks + all tests
make test                # Run all tests (PHP + frontend)
make check               # Run all code quality checks
make fix                 # Auto-fix all code quality issues
```

See [CLAUDE.md](CLAUDE.md) for full development documentation.

---

## Contributing

Contributions are welcome! Please see the [Contributing Guide](https://app-dev-panel.github.io/app-dev-panel/guide/contributing) and the development section above for setup instructions.

---

## Support the Project

ADP is 100% open-source and free. If it saves you debugging time, consider supporting development:

<div align="center">

<a href="https://patreon.com/xepozz"><img height="36" src="https://github.githubassets.com/assets/patreon-96b15b9db4b9.svg" alt="Patreon"> <b>Patreon</b></a>
&nbsp;&nbsp;&middot;&nbsp;&nbsp;
<a href="https://buymeacoffee.com/xepozz"><img height="36" src="https://github.githubassets.com/assets/buy_me_a_coffee-63ed78263f6e.svg" alt="Buy me a coffee"> <b>Buy me a coffee</b></a>
&nbsp;&nbsp;&middot;&nbsp;&nbsp;
<a href="https://boosty.to/xepozz"><img height="36" src="https://boosty.to/favicon.ico" alt="Boosty"> <b>Boosty</b></a>

</div>

### Crypto

| Network | Address |
|---------|---------|
| **USDT — TON** | `UQDuFuRj_PgCMtV30FGLLlc51NzMGrGaHI8uhrkILw00D2UE` |
| **USDT — TRC20 (Tron)** | `THfZotbtgmHrFGhPvY2BFq7ALKhZtYWjPh` |
| **USDT — ERC20 (Ethereum)** | `0x923073361Da37E54443c364bA8fDB994B71D2083` |

---

## License

BSD-3-Clause. See [LICENSE.md](LICENSE.md) for details.
