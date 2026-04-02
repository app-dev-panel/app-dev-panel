---
description: "Complete ADP REST API reference: debug entries, summaries, collector data, inspector queries, and ingestion."
---

# REST Endpoints

## Debug API

| Method | Path | Description |
|--------|------|-------------|
| GET | `/debug/api/` | List all debug entries (summaries) |
| GET | `/debug/api/summary/{id}` | Single entry summary |
| GET | `/debug/api/view/{id}` | Full entry data (optionally filtered by collector) |
| GET | `/debug/api/dump/{id}` | Dump objects for entry |
| GET | `/debug/api/object/{id}/{objectId}` | Specific object from dump |

### Example: List entries

```
GET /debug/api/
```

```json
{
    "id": null,
    "data": [
        {
            "id": "abc123",
            "collectors": ["request", "log", "event"],
            "url": "/api/users",
            "method": "GET",
            "status": 200,
            "time": 1234567890
        }
    ],
    "error": null,
    "success": true,
    "status": 200
}
```

### Example: View entry

```
GET /debug/api/view/abc123?collector=log
```

Returns full collected data for the specified entry, optionally filtered to a single collector.

## Ingestion API

| Method | Path | Description |
|--------|------|-------------|
| POST | `/debug/api/ingest/` | Ingest single debug entry |
| POST | `/debug/api/ingest/batch` | Ingest multiple entries |
| POST | `/debug/api/ingest/log` | Shorthand: ingest a single log entry |
| GET | `/debug/api/ingest/openapi.json` | OpenAPI 3.1 specification |

## Service Registry API

| Method | Path | Description |
|--------|------|-------------|
| POST | `/debug/api/services/register` | Register an external service |
| POST | `/debug/api/services/heartbeat` | Keep service online |
| GET | `/debug/api/services/` | List registered services |
| DELETE | `/debug/api/services/{service}` | Deregister a service |
