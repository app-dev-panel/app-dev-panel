---
title: Elasticsearch
---

# Elasticsearch

ADP provides an `ElasticsearchCollector` for capturing Elasticsearch requests during application lifecycle and an inspector for live cluster inspection.

## Collector

`ElasticsearchCollector` implements `SummaryCollectorInterface` and captures all Elasticsearch requests — searches, indexing, deletions, bulk operations.

### Collection Patterns

Two patterns are supported:

**Paired pattern** — for proxy-based adapters that intercept the ES client:

```php
$collector->collectRequestStart($id, 'GET', '/users/_search', $body, $line);
// ... request executes ...
$collector->collectRequestEnd($id, 200, $responseBody, $responseSize);
// or on failure:
$collector->collectRequestError($id, $exception);
```

**Simple pattern** — for event-based adapters that measure timing externally:

```php
$collector->logRequest(new ElasticsearchRequestRecord(
    method: 'GET',
    endpoint: '/users/_search',
    body: '{"query":{"match_all":{}}}',
    line: __FILE__ . ':' . __LINE__,
    startTime: $start,
    endTime: $end,
    statusCode: 200,
    responseBody: '{"hits":{"total":{"value":42}}}',
    responseSize: 256,
));
```

### Collected Data

```php
[
    'requests' => [
        [
            'method' => 'GET',
            'endpoint' => '/users/_search',
            'index' => 'users',           // auto-extracted from endpoint
            'body' => '{"query":{...}}',
            'line' => '/src/Repo.php:42',
            'status' => 'success',         // success | error | initialized
            'startTime' => 1711900000.123,
            'endTime' => 1711900000.135,
            'duration' => 0.012,
            'statusCode' => 200,
            'responseBody' => '...',
            'responseSize' => 256,
            'hitsCount' => 42,             // extracted from response (null for non-search)
            'exception' => null,
        ],
    ],
    'duplicates' => [
        'groups' => [...],                 // repeated method+endpoint combinations
        'totalDuplicatedCount' => 0,
    ],
]
```

### Summary

```php
[
    'elasticsearch' => [
        'total' => 3,
        'errors' => 0,
        'totalTime' => 0.045,
        'duplicateGroups' => 0,
        'totalDuplicatedCount' => 0,
    ],
]
```

### Features

- **Index extraction** — parses the index name from the endpoint path (e.g., `/users/_search` → `users`)
- **Hits count** — extracts `hits.total.value` from search responses
- **Duplicate detection** — identifies repeated `method + endpoint` combinations (N+1 pattern detection)
- **Timeline integration** — reports to `TimelineCollector` for unified performance timeline

## Inspector

The Elasticsearch inspector provides live cluster inspection via `ElasticsearchProviderInterface`.

### API Endpoints

| Method | Path | Description |
|--------|------|-------------|
| GET | `/inspect/api/elasticsearch` | Cluster health + indices list |
| GET | `/inspect/api/elasticsearch/{name}` | Index detail (mappings, settings, stats) |
| POST | `/inspect/api/elasticsearch/search` | Execute search query |
| POST | `/inspect/api/elasticsearch/query` | Execute raw query |

### Provider Interface

```php
interface ElasticsearchProviderInterface
{
    public function getHealth(): array;
    public function getIndices(): array;
    public function getIndex(string $name): array;
    public function search(string $index, array $query, int $limit, int $offset): array;
    public function executeQuery(string $method, string $endpoint, array $body): array;
}
```

Default: `NullElasticsearchProvider` returns empty data. Adapters provide concrete implementations backed by an actual ES client.

## Frontend

### Debug Panel

The `ElasticsearchPanel` displays captured requests with:
- Method and status code badges (color-coded)
- Endpoint with extracted index name
- Duration and hits count per request
- Expandable request/response body (JSON-rendered)
- Filter by endpoint, index, method, or body content
- Duplicate detection warnings

### Inspector Page

The `ElasticsearchPage` shows live cluster state:
- Cluster health banner (green/yellow/red status chips)
- Node and shard counts
- Indices table with docs count, store size, health, shards

## Framework Integration

Register `ElasticsearchCollector` in your adapter's DI with `TimelineCollector` as constructor dependency. Enable via config flag `'elasticsearch' => true`.

See [Adapters](/guide/adapters/symfony) for framework-specific registration patterns.
