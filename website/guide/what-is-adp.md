# What is ADP?

**ADP (Application Development Panel)** is a framework-agnostic debugging and development panel for PHP applications. Think of it as a universal "developer toolbar" that works with any PHP framework.

## The Problem

Every PHP framework has its own debugging tools:

- Symfony has the **Web Profiler**
- Laravel has **Telescope**
- Yii has the **Debug Extension**

But what if you work with multiple frameworks? Or want consistent tooling across projects? That's where ADP comes in.

## The Solution

ADP provides a **single, unified debugging experience** that works across frameworks by leveraging PSR standards:

- **PSR-3** (Logger) — Captures log messages
- **PSR-7** (HTTP Messages) — Inspects requests and responses
- **PSR-14** (Event Dispatcher) — Tracks events
- **PSR-15** (HTTP Handlers) — Monitors middleware pipeline
- **PSR-11** (Container) — Inspects dependency injection

## Key Features

### Collectors

ADP ships with collectors for common debugging scenarios:

| Collector | What it captures |
|-----------|-----------------|
| `LogCollector` | PSR-3 log messages |
| `EventCollector` | PSR-14 events |
| `HttpClientCollector` | Outgoing HTTP requests |
| `DatabaseCollector` | SQL queries and timings |
| `ExceptionCollector` | Caught and uncaught exceptions |
| `MiddlewareCollector` | PSR-15 middleware execution |
| `ServiceCollector` | DI container resolutions |
| `AssetCollector` | Frontend assets |
| `RouterCollector` | Route matching and parameters |

### Real-time Updates

The panel uses Server-Sent Events (SSE) to push new debug entries to the browser in real-time. No need to refresh.

### AI Integration

ADP includes an MCP (Model Context Protocol) server that exposes debug data to AI assistants like Claude. Ask your AI to analyze errors, suggest fixes, or explain complex request flows.

### Framework Support

| Framework | Adapter Status |
|-----------|---------------|
| Symfony 7 | Stable |
| Yii 2 | Stable |
| Yii 3 (Yiisoft) | Stable |
| Laravel 12 | Stable |

## Philosophy

- **Framework-agnostic** — Works with any PSR-compliant framework
- **Zero configuration** — Install the adapter and it just works
- **Non-intrusive** — Uses proxies, not patches. Your code stays clean
- **Extensible** — Write custom collectors for your domain
- **Modern stack** — React 18, TypeScript, Material-UI on the frontend
