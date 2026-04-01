---
title: HTTP Client Collector
---

# HTTP Client Collector

Captures outgoing PSR-18 HTTP requests and responses with timing and status codes.

![HTTP Client Collector panel](/images/collectors/http-client.png)

## What It Captures

| Field | Description |
|-------|-------------|
| `method` | HTTP method (GET, POST, etc.) |
| `uri` | Request URI |
| `headers` | Request headers |
| `line` | Source file and line of the HTTP call |
| `responseStatus` | Response HTTP status code |
| `responseRaw` | Raw response body |
| `totalTime` | Total request/response time in seconds |

## Data Schema

```json
[
    {
        "startTime": 1711878000.100,
        "endTime": 1711878000.350,
        "totalTime": 0.25,
        "method": "GET",
        "uri": "https://api.example.com/users/42",
        "headers": {"Authorization": "Bearer ***"},
        "line": "/app/src/ApiClient.php:55",
        "responseRaw": "{\"id\": 42, \"name\": \"John\"}",
        "responseStatus": 200
    }
]
```

**Summary** (shown in debug entry list):

```json
{
    "http": {
        "count": 3,
        "totalTime": 0.75
    }
}
```

## Contract

```php
use AppDevPanel\Kernel\Collector\HttpClientCollector;

// Start collection
$collector->collect(
    request: $psrRequest,
    startTime: microtime(true),
    line: '/app/src/ApiClient.php:55',
    uniqueId: 'req-1',
);

// Complete with response
$collector->collectTotalTime(
    response: $psrResponse,
    endTime: microtime(true),
    uniqueId: 'req-1',
);
```

::: info
<class>\AppDevPanel\Kernel\Collector\HttpClientCollector</class> implements <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class> and depends on <class>\AppDevPanel\Kernel\Collector\TimelineCollector</class>.
:::

## How It Works

The collector is fed by <class>\AppDevPanel\Kernel\Collector\HttpClientInterfaceProxy</class> — a PSR-18 <class>Psr\Http\Client\ClientInterface</class> decorator. Every `$client->sendRequest($request)` call is automatically intercepted, timed, and recorded.

## Debug Panel

- **Request list** — all outgoing HTTP calls with method, URL, status, and timing
- **Request/response details** — expandable view with headers and body
- **Status badges** — color-coded by response status (2xx green, 4xx orange, 5xx red)
- **Timing breakdown** — per-request duration
