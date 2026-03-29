# API Overview

ADP exposes three API domains over HTTP: **Debug** (stored debug entries), **Inspector** (live application state), and **Ingestion** (external data intake).

## Base URLs

| Domain | Base Path | Purpose |
|--------|-----------|---------|
| Debug | `/debug/api` | Access stored debug entries and SSE stream |
| Inspector | `/inspect/api` | Query live application state (routes, config, database, files) |
| Ingestion | `/debug/api/ingest` | Accept debug data from external applications |

## Response Format

All responses (except SSE and MCP) are wrapped in a standard envelope:

```json
{
    "id": "debug-entry-id",
    "data": { ... },
    "error": null,
    "success": true,
    "status": 200
}
```

On error, `success` is `false`, `error` contains the error message, and `data` is `null`.

## Middleware Chain

Every API request passes through:

1. **IpFilter** -- validates request IP against allowed IPs (default: `127.0.0.1`, `::1`)
2. **CorsAllowAll** -- adds permissive CORS headers
3. **ResponseDataWrapper** -- wraps responses in the standard envelope
4. **DebugHeaders** -- adds `X-Debug-Id` and `X-Debug-Link` response headers

Inspector endpoints additionally pass through:

5. **InspectorProxyMiddleware** -- routes `?service=<name>` requests to registered external services

## Authentication

By default, the API is restricted to localhost via IP filtering. An optional `auth_token` can be configured for additional security.

## Transports

- **REST** -- standard JSON request/response ([reference](./rest))
- **SSE** -- real-time push notifications for new debug entries ([reference](./sse))
- **MCP** -- JSON-RPC 2.0 endpoint for AI assistant integration ([reference](./inspector))
