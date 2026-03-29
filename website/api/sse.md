# SSE (Server-Sent Events)

The SSE endpoint provides real-time push notifications when new debug entries are written to storage.

## Endpoint

```
GET /debug/api/event-stream
```

## How It Works

The server polls storage every second, computing an MD5 hash of the summary data. When a new debug entry is written, the hash changes and an event is emitted to all connected clients.

## Event Format

```
data: {"type": "debug-updated", "payload": []}
```

| Field | Description |
|-------|-------------|
| `type` | Always `debug-updated` |
| `payload` | Reserved for future use (currently empty array) |

## Client Usage

```javascript
const source = new EventSource('/debug/api/event-stream');

source.onmessage = (event) => {
    const data = JSON.parse(event.data);
    if (data.type === 'debug-updated') {
        // Refresh debug entry list
    }
};
```

## Notes

- The SSE stream bypasses `ResponseDataWrapper` -- it uses the native SSE text/event-stream format, not the JSON envelope.
- The connection remains open until the client disconnects.
- IP filtering applies -- only allowed IPs can connect (default: `127.0.0.1`, `::1`).
