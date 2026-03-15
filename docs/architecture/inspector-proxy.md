# Inspector Proxy Architecture

## Context

Inspector endpoints (`/inspect/api/*`) access live application state: config, routes, files, cache, DB. For PHP apps, ADP runs in-process via an adapter. For external apps (Python, Node.js, Go), ADP is a separate service with no access to application internals.

## Design

ADP proxies inspector requests to the target application. The application implements a subset of the Inspector API and handles requests about its own state.

```
Frontend → ADP API (/inspect/api/*?service=auth-svc)
               │
               ├─ service=null or service=local → handle locally (current PHP behavior)
               │
               └─ service=auth-svc → ServiceRegistry.resolve("auth-svc")
                                       → proxy HTTP request to http://python-app:9090/inspect/api/*
                                       → return response to frontend
```

## Service Registry

### Registration

```
POST /debug/api/services/register
{
    "service": "auth-service",
    "language": "python",
    "inspectorUrl": "http://auth-app:9090",
    "capabilities": ["config", "routes", "files", "cache"]
}

Response 200:
{
    "service": "auth-service",
    "registered": true
}
```

### Heartbeat

Services send periodic heartbeats. If no heartbeat for 60 seconds, service is marked offline.

```
POST /debug/api/services/heartbeat
{
    "service": "auth-service"
}
```

### Service List

```
GET /debug/api/services

Response:
{
    "services": [
        {
            "service": "auth-service",
            "language": "python",
            "inspectorUrl": "http://auth-app:9090",
            "capabilities": ["config", "routes", "files", "cache"],
            "status": "online",
            "lastSeen": "2026-03-15T12:00:00Z"
        },
        {
            "service": "local",
            "language": "php",
            "inspectorUrl": null,
            "capabilities": ["*"],
            "status": "online",
            "lastSeen": null
        }
    ]
}
```

### Deregistration

```
DELETE /debug/api/services/{service}
```

## Inspector Capabilities

| Capability | Endpoints | Universal | PHP-only |
|-----------|-----------|-----------|----------|
| `config` | GET /config, GET /params | yes | |
| `routes` | GET /routes, GET /route/check | yes | |
| `files` | GET /files | yes | |
| `cache` | GET,DELETE /cache, POST /cache/clear | yes | |
| `database` | GET /table, GET /table/{name} | yes | |
| `translations` | GET,PUT /translations | yes | |
| `events` | GET /events | yes | |
| `commands` | GET,POST /command | yes | |
| `git` | GET /git/summary,log, POST /git/checkout,command | yes | |
| `composer` | GET,POST /composer/* | | yes |
| `classes` | GET /classes | | yes |
| `object` | GET /object | | yes |
| `phpinfo` | GET /phpinfo | | yes |
| `opcache` | GET /opcache | | yes |
| `request` | PUT /request, POST /curl/build | | yes |

External apps implement the universal endpoints using their own runtime. For example:
- Python app: `/config` returns Flask/Django config, `/routes` returns URL patterns
- Node.js app: `/config` returns Express settings, `/routes` returns router stack

## Proxy Middleware

`InspectorProxyMiddleware` — inserted into the `/inspect/api` route group.

### Resolution Logic

1. Check `?service=` query parameter on incoming request
2. If absent or `service=local` → pass through to current PHP controllers (no change)
3. If `service=<name>` → look up in ServiceRegistry
4. If service not found → 404
5. If service found but capability not supported for this endpoint → 501
6. Proxy the request to `{inspectorUrl}/inspect/api/{path}` with same method, headers, body
7. Return proxied response to frontend

### Request Transformation

```php
// Original request to ADP:
GET /inspect/api/routes?service=auth-service

// Proxied to external app:
GET /inspect/api/routes
// (service param stripped, X-ADP-Request-Id header added)
```

### Timeout & Error Handling

- Proxy timeout: 10 seconds (configurable)
- Connection refused → 502 Bad Gateway
- Timeout → 504 Gateway Timeout
- Invalid JSON response → 502 Bad Gateway

## Abstractions

### ServiceRegistryInterface (Kernel)

```php
namespace AppDevPanel\Kernel\Service;

interface ServiceRegistryInterface
{
    public function register(ServiceDescriptor $descriptor): void;
    public function deregister(string $service): void;
    public function heartbeat(string $service): void;
    public function resolve(string $service): ?ServiceDescriptor;
    public function all(): array;
}
```

### ServiceDescriptor (Kernel)

```php
namespace AppDevPanel\Kernel\Service;

final readonly class ServiceDescriptor
{
    public function __construct(
        public string $service,
        public string $language,
        public ?string $inspectorUrl,
        public array $capabilities,
        public float $registeredAt,
        public float $lastSeenAt,
    ) {}

    public function supports(string $capability): bool;
    public function isOnline(float $timeoutSeconds = 60.0): bool;
}
```

### InMemoryServiceRegistry (Kernel)

In-memory implementation. Services lost on restart. Sufficient for development use case — services re-register on startup.

### FileServiceRegistry (Kernel)

JSON file-based persistence at `{storagePath}/.services.json`. Survives restarts.

### InspectorProxyMiddleware (API)

```php
namespace AppDevPanel\Api\Inspector\Middleware;

final class InspectorProxyMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ServiceRegistryInterface $registry,
        private ClientInterface $httpClient,     // PSR-18
        private RequestFactoryInterface $requestFactory, // PSR-17
        private StreamFactoryInterface $streamFactory,   // PSR-17
        private ResponseFactoryInterface $responseFactory,
        private float $timeoutSeconds = 10.0,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface;
}
```

### Capability Mapping

```php
private const CAPABILITY_MAP = [
    '/config' => 'config',
    '/params' => 'config',
    '/routes' => 'routes',
    '/route/check' => 'routes',
    '/files' => 'files',
    '/cache' => 'cache',
    '/cache/clear' => 'cache',
    '/table' => 'database',
    '/translations' => 'translations',
    '/events' => 'events',
    '/command' => 'commands',
    '/git/' => 'git',
    '/composer/' => 'composer',
    '/classes' => 'classes',
    '/object' => 'object',
    '/phpinfo' => 'phpinfo',
    '/opcache' => 'opcache',
    '/request' => 'request',
    '/curl/build' => 'request',
];
```

### ServiceController (API)

```php
namespace AppDevPanel\Api\Inspector\Controller;

final class ServiceController
{
    public function register(ServerRequestInterface $request): ResponseInterface;
    public function heartbeat(ServerRequestInterface $request): ResponseInterface;
    public function list(): ResponseInterface;
    public function deregister(ServerRequestInterface $request, CurrentRoute $route): ResponseInterface;
}
```

## Inspector OpenAPI Spec

`openapi/inspector.yaml` — contract for external applications. Covers universal capabilities only. Same request/response format as current PHP implementation.

External SDKs (Python, TypeScript) provide `InspectorServer`:
- Starts HTTP server on configurable port
- Implements inspector endpoints via framework introspection
- Auto-registers with ADP on startup
- Sends periodic heartbeats

## Data Flow

```
1. External app starts → creates InspectorServer on port 9090
2. InspectorServer → POST /debug/api/services/register to ADP
3. InspectorServer → periodic POST /debug/api/services/heartbeat
4. User opens ADP frontend → sees service selector dropdown
5. User selects "auth-service" → frontend adds ?service=auth-service to all /inspect/api/* calls
6. ADP InspectorProxyMiddleware → resolves service → proxies to http://auth-app:9090/inspect/api/*
7. External app handles request → returns JSON response
8. ADP forwards response to frontend
```

## File Structure

```
libs/Kernel/src/Service/
├── ServiceRegistryInterface.php
├── ServiceDescriptor.php
├── InMemoryServiceRegistry.php
└── FileServiceRegistry.php

libs/API/src/Inspector/
├── Controller/
│   └── ServiceController.php          # NEW: service registration endpoints
├── Middleware/
│   └── InspectorProxyMiddleware.php   # NEW: proxy logic
└── ... (existing controllers unchanged)

openapi/
├── ingestion.yaml                     # Existing
└── inspector.yaml                     # NEW: inspector contract for external apps

clients/python/adp_client/
├── client.py                          # Existing ingestion client
└── inspector_server.py                # NEW: inspector server implementation

clients/typescript/src/
├── client.ts                          # Existing ingestion client
└── inspector-server.ts               # NEW: inspector server implementation
```

## Migration

Zero breaking changes:
- Existing `/inspect/api/*` routes unchanged when no `?service=` param
- Frontend defaults to `service=local`
- External apps opt-in via service registration
