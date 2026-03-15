# Middleware

## Request Pipeline

Every API request passes through the following middleware chain:

```
Request → IpFilter → CorsAllowAll → ResponseDataWrapper → DebugHeaders → Controller
```

## IpFilter

Validates the request's remote IP address against a whitelist.

**Default allowed IPs**: `127.0.0.1`, `::1`

Configuration:
```php
'allowedIPs' => ['127.0.0.1', '::1'],
'allowedHosts' => [],
```

If the IP is not allowed, the request is rejected before reaching any controller.

## CorsAllowAll

Adds permissive CORS headers to all responses. Allows requests from any origin.
This is appropriate for a development tool.

## ResponseDataWrapper

Wraps all controller responses in a standardized envelope:

```json
{
    "id": "current-debug-entry-id",
    "data": "<controller response>",
    "error": null,
    "success": true,
    "status": 200
}
```

If the controller throws `NotFoundException`, it is caught and returned as:

```json
{
    "id": "current-debug-entry-id",
    "data": null,
    "error": "Not found message",
    "success": false,
    "status": 404
}
```

## DebugHeaders

Adds debug-related headers to responses:

- `X-Debug-Id`: Current debugger entry ID
- `X-Debug-Link`: URL to view the current debug entry in the panel

## HttpApplicationWrapper

Wraps the framework's HTTP application to inject debug middleware into the
application's own middleware pipeline. This allows the debug panel to capture
middleware execution data.

## RouteCollectorWrapper

Prepends debug middleware to the framework's route collector, ensuring debug
routes are registered alongside application routes.
