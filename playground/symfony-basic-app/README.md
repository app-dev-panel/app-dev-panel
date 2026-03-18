# Symfony Playground — ADP Integration Demo

Minimal Symfony 7 application with ADP (Application Development Panel) integrated.

## Setup

```bash
cd playground/symfony-basic-app
composer install
```

## Run

```bash
# Start PHP built-in server
php -S 127.0.0.1:8080 -t public
```

## Console

```bash
# Run Symfony console commands
php bin/console
```

## Demo Endpoints

| Endpoint | Description |
|----------|-------------|
| `GET /` | Welcome page with endpoint list |
| `GET /api/users` | Demo user list (generates log entries) |
| `GET /api/error` | Triggers a demo exception |

## Debug Data

After making requests, debug entries are stored in `var/debug/`.
The ADP API is available at `/debug/api/` (when API routes are wired).

## Configuration

See `config/packages/app_dev_panel.yaml` for all available options.

## What Gets Collected

With default configuration, every request generates:
- HTTP request/response details (method, URL, status, headers, route, controller)
- All PSR-3 log entries
- Dispatched events
- Exceptions (if any)
- Timeline data
- Application info (PHP version, memory usage)
