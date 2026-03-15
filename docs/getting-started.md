# Getting Started

## Prerequisites

- PHP 8.4+
- Composer 2.x
- Node.js 18+ and npm 9+
- Docker (optional, for containerized development)

## Installation

### 1. Clone and Install Dependencies

```bash
git clone <repository-url>
cd app-dev-panel

# PHP dependencies
composer install

# Frontend dependencies
cd libs/yii-dev-panel
npm install
```

### 2. Start the Demo Application

```bash
# Start the PHP backend (from project root)
cd app
php -S 0.0.0.0:8080 -t public

# Start the frontend dev server (from libs/yii-dev-panel)
cd libs/yii-dev-panel
npm run dev
```

### 3. Using Docker

```bash
cd libs/yii-dev-panel
docker-compose up
```

## Integrating ADP into Your Application

### Yii 3 (First-Party Adapter)

1. Install the packages:

```bash
composer require app-dev-panel/kernel app-dev-panel/api app-dev-panel/adapter-yiisoft
```

2. The Yii config plugin will auto-register the debug panel. No manual configuration needed.

3. Access the debug panel at `http://your-app/debug/api/` (API) or via the frontend SPA.

### Other Frameworks

To integrate ADP with a different framework, you need to create an adapter that:

1. Registers Kernel proxy classes as service decorators in your DI container
2. Hooks into application lifecycle events (startup/shutdown)
3. Configures which collectors are active

See `libs/Adapter/Yiisoft/` for a reference implementation.

## Project Structure

| Directory | Description |
|-----------|-------------|
| `app/` | Demo PHP application |
| `libs/Kernel/` | Core debugging engine |
| `libs/API/` | REST API + SSE endpoints |
| `libs/Cli/` | CLI commands |
| `libs/Adapter/Yiisoft/` | Yii 3 framework adapter |
| `libs/yii-dev-panel/` | Frontend (React SPA + toolbar + SDK) |
| `docs/` | Global documentation |

## Running Tests

```bash
# Backend tests
cd libs/Kernel && composer test
cd libs/API && composer test
cd libs/Cli && composer test

# Frontend tests
cd libs/yii-dev-panel && npm test

# Static analysis
cd libs/Kernel && vendor/bin/phpstan analyse
```
