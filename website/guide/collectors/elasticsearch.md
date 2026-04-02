---
title: Elasticsearch Collector
description: "ADP Elasticsearch Collector captures search queries, index operations, and cluster requests with timing."
---

# Elasticsearch Collector

Captures Elasticsearch requests — method, endpoint, index, request/response body, timing, hits count, and duplicate detection.

![Elasticsearch Collector panel](/images/collectors/elasticsearch.png)

## What It Captures

| Field | Description |
|-------|-------------|
| `method` | HTTP method (GET, POST, PUT, DELETE) |
| `endpoint` | Elasticsearch endpoint path |
| `index` | Target index name |
| `body` | Request body (JSON) |
| `status` | Request status (`success` or `error`) |
| `statusCode` | HTTP response status code |
| `responseBody` | Response body |
| `responseSize` | Response size in bytes |
| `hitsCount` | Number of search hits (for search queries) |
| `duration` | Request duration in seconds |

## Data Schema

```json
{
    "requests": [
        {
            "method": "GET",
            "endpoint": "/users/_search",
            "index": "users",
            "body": "{\"query\": {\"match\": {\"name\": \"John\"}}}",
            "line": "/app/src/SearchService.php:42",
            "status": "success",
            "startTime": 1711878000.100,
            "endTime": 1711878000.150,
            "duration": 0.05,
            "statusCode": 200,
            "responseBody": "{\"hits\": {\"total\": 5, ...}}",
            "responseSize": 1024,
            "hitsCount": 5,
            "exception": null
        }
    ],
    "duplicates": {
        "groups": [],
        "totalDuplicatedCount": 0
    }
}
```

**Summary** (shown in debug entry list):

```json
{
    "elasticsearch": {
        "total": 3,
        "errors": 0,
        "totalTime": 0.15,
        "duplicateGroups": 0,
        "totalDuplicatedCount": 0
    }
}
```

## Contract

```php
use AppDevPanel\Kernel\Collector\ElasticsearchCollector;
use AppDevPanel\Kernel\Collector\ElasticsearchRequestRecord;

// Option A: start/end pattern
$collector->collectRequestStart(
    id: 'es-1',
    method: 'GET',
    endpoint: '/users/_search',
    body: '{"query": {"match": {"name": "John"}}}',
    line: '/app/src/SearchService.php:42',
);
$collector->collectRequestEnd(
    id: 'es-1',
    statusCode: 200,
    responseBody: '{"hits": {"total": 5}}',
    responseSize: 1024,
);

// Option B: single record
$collector->logRequest(new ElasticsearchRequestRecord(
    method: 'GET',
    endpoint: '/users/_search',
    index: 'users',
    body: '{"query": {"match": {"name": "John"}}}',
    duration: 0.05,
    statusCode: 200,
    hitsCount: 5,
    line: '/app/src/SearchService.php:42',
));
```

::: info
<class>\AppDevPanel\Kernel\Collector\ElasticsearchCollector</class> implements <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class>, depends on <class>\AppDevPanel\Kernel\Collector\TimelineCollector</class>, and uses <class>\AppDevPanel\Kernel\Collector\DuplicateDetectionTrait</class>.
:::

See the dedicated [Elasticsearch](/guide/elasticsearch) page for configuration and integration details.

## Debug Panel

- **Request list** — all ES requests with method, endpoint, status, and timing
- **Hits count** — number of search results for search queries
- **Duplicate detection** — highlights repeated identical requests
- **Error tracking** — failed requests highlighted with error details
