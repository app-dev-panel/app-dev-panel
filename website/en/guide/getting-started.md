# Getting Started

ADP (Application Development Panel) is a framework-agnostic debugging panel for PHP applications. It collects runtime data and provides a web UI to inspect and debug your application.

## Prerequisites

- PHP 8.4 or higher
- Composer
- Node.js 21+ (for frontend development)

## Installation

### 1. Install the adapter for your framework

::: code-group

```bash [Yii 3]
composer require app-dev-panel/yiisoft-adapter
```

```bash [Symfony]
composer require app-dev-panel/symfony-adapter
```

```bash [Laravel]
composer require app-dev-panel/laravel-adapter
```

```bash [Yii 2]
composer require app-dev-panel/yii2-adapter
```

:::

### 2. Configure your application

Each adapter auto-registers with your framework's dependency injection container. No manual configuration is typically needed.

### 3. Start debugging

Run your application and open the ADP panel in your browser. You'll see debug data collected from your application in real-time.

## Architecture Overview

ADP follows a layered architecture:

```
┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│   Frontend   │────▶│     API      │────▶│    Kernel     │
│  (React SPA) │ HTTP│  (REST+SSE)  │     │ (Collectors)  │
└──────────────┘     └──────────────┘     └───────┬───────┘
                                                  │
                                          ┌───────┴───────┐
                                          │    Adapter     │
                                          └───────┬───────┘
                                                  │
                                          ┌───────┴───────┐
                                          │  Target App   │
                                          └───────────────┘
```

1. **Kernel** — Core engine managing debugger lifecycle, collectors, and storage
2. **API** — HTTP layer exposing debug data via REST + SSE
3. **Adapter** — Framework bridge wiring collectors into your application
4. **Frontend** — React SPA consuming the API

## What's Next?

- [What is ADP?](/en/guide/what-is-adp) — Learn about the project philosophy
- [Architecture](/en/guide/architecture) — Deep dive into the system design
- [Collectors](/en/guide/collectors) — Understand how data is collected
- [Data Flow](/en/guide/data-flow) — Follow data from your app to the panel
