---
title: Elasticsearch Inspector
description: "ADP Elasticsearch Inspector shows cluster health, indexes, mappings, and node information in real time."
---

# Elasticsearch Inspector

Inspect Elasticsearch cluster health, browse indices, and execute search queries.

## Features

| Feature | Description |
|---------|-------------|
| Cluster health | Green/yellow/red status |
| Indices list | All indices with document counts and sizes |
| Index details | Mappings, settings, and statistics |
| Search | Execute search queries against an index |
| Raw query | Run arbitrary Elasticsearch API requests |

## Search

Execute Elasticsearch queries against any index. Specify the index, query body, limit, and offset. Results are displayed with document source data.

## Raw Query

For advanced use, execute any Elasticsearch API request by specifying the HTTP method, endpoint, and optional body.

## API Endpoints

| Method | Path | Description |
|--------|------|-------------|
| GET | `/inspect/api/elasticsearch` | Cluster health + indices list |
| GET | `/inspect/api/elasticsearch/{name}` | Index mappings, settings, stats |
| POST | `/inspect/api/elasticsearch/search` | Search query against an index |
| POST | `/inspect/api/elasticsearch/query` | Raw Elasticsearch API request |

**Search request body:**
```json
{
    "index": "products",
    "query": {"match": {"name": "widget"}},
    "limit": 10,
    "offset": 0
}
```

**Raw query request body:**
```json
{
    "method": "GET",
    "endpoint": "/_cluster/stats",
    "body": null
}
```

## Requirements

Requires an <class>AppDevPanel\Api\Inspector\Elasticsearch\ElasticsearchProviderInterface</class> implementation in the DI container. Each adapter provides its own implementation based on the Elasticsearch client library in use.
