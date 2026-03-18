# API Endpoints Reference

## Authentication & Security

All API endpoints are protected by `IpFilter` middleware. By default, only `127.0.0.1` and `::1`
are allowed. Configure `allowedIPs` in params to change this:

```php
'app-dev-panel/yiisoft-api' => [
    'allowedIPs' => ['127.0.0.1', '::1', '192.168.1.0/24'],
    'allowedHosts' => [],
],
```

## Debug Endpoints

### GET /debug/api/

Returns a list of all debug entries (most recent first).

**Response:**
```json
{
    "data": [
        {
            "id": "abc123",
            "collectors": ["log", "event", "request"],
            "url": "/api/users",
            "method": "GET",
            "statusCode": 200,
            "time": 1705312345.123
        }
    ]
}
```

### GET /debug/api/summary/{id}

Returns summary for a single debug entry.

### GET /debug/api/view/{id}?collector=ClassName

Returns detailed data for a debug entry. If `collector` query param is specified,
returns only that collector's data.

Supports three response modes based on the collector:
1. **JSON** (default): Returns collector data as JSON
2. **HTML**: If collector implements `HtmlViewProviderInterface`, renders a view template
3. **Module Federation**: If collector implements `ModuleFederationProviderInterface`, returns asset info for remote panel loading

### GET /debug/api/dump/{id}

Returns serialized object dumps for the entry.

### GET /debug/api/object/{id}/{objectId}

Returns a specific object from the dump by its ID.

### GET /debug/api/event-stream

SSE endpoint. Emits `debug-updated` events when new entries appear.

**Headers:**
```
Content-Type: text/event-stream
Cache-Control: no-cache
Connection: keep-alive
```

## Inspector Endpoints

### GET /inspect/api/routes

Returns all registered application routes with methods, path, middleware stack, and action handler.

### GET /inspect/api/route/check?url=/path&method=GET

Tests whether a URL matches any registered route.

### GET /inspect/api/config?group=di-web

Returns DI configuration. The `group` query param selects which config group.

### GET /inspect/api/params

Returns all application parameters.

### GET /inspect/api/events

Returns registered event listeners grouped by event class.

### GET /inspect/api/files?path=/src

Lists files in the given directory. If `class` param is provided, resolves the class file via reflection.

### GET /inspect/api/table

Lists all database tables with column info and record counts.

### GET /inspect/api/table/{name}

Returns schema and all records for a specific table.

### PUT /inspect/api/request

Re-executes an HTTP request from debug history. Request body contains the original request data.

### POST /inspect/api/curl/build

Builds a cURL command string from a debug entry's request data.

## Command Execution

### GET /inspect/api/command/

Lists available commands from configuration and composer scripts.

### POST /inspect/api/command/

Executes a command. Request body:
```json
{
    "command": "phpunit",
    "arguments": "--filter=MyTest"
}
```

Response:
```json
{
    "status": "ok",
    "result": "...command output...",
    "error": []
}
```

Supported built-in commands: PHPUnit, Codeception, Psalm, generic Bash.
