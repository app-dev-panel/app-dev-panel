---
title: Database Collector
---

# Database Collector

Captures SQL queries, parameters, execution time, transactions, and duplicate query detection.

![Database Collector panel](/images/collectors/database.png)

## What It Captures

| Field | Description |
|-------|-------------|
| `sql` | SQL query with parameter placeholders |
| `rawSql` | SQL query with parameters inlined |
| `params` | Bound parameters array |
| `line` | Source file and line of the query call |
| `status` | Query status (`success` or `error`) |
| `rowsNumber` | Number of affected/returned rows |
| `exception` | Exception if query failed |
| `transactionId` | Associated transaction ID |

## Data Schema

```json
{
    "queries": [
        {
            "position": 0,
            "transactionId": null,
            "sql": "SELECT * FROM users WHERE id = :id",
            "rawSql": "SELECT * FROM users WHERE id = 42",
            "params": {":id": 42},
            "line": "/app/src/UserRepository.php:35",
            "status": "success",
            "rowsNumber": 1,
            "exception": null,
            "actions": []
        }
    ],
    "transactions": {},
    "duplicates": {
        "groups": [],
        "totalDuplicatedCount": 0
    }
}
```

**Summary** (shown in debug entry list):

```json
{
    "db": {
        "queries": {"error": 0, "total": 3},
        "transactions": {"error": 0, "total": 1},
        "duplicateGroups": 0,
        "totalDuplicatedCount": 0
    }
}
```

## Contract

### Query lifecycle

```php
use AppDevPanel\Kernel\Collector\DatabaseCollector;

// Option A: start/end pattern
$collector->collectQueryStart(
    id: 'query-1',
    sql: 'SELECT * FROM users WHERE id = :id',
    rawSql: 'SELECT * FROM users WHERE id = 42',
    params: [':id' => 42],
    line: '/app/src/UserRepository.php:35',
);
$collector->collectQueryEnd(id: 'query-1', rowsNumber: 1);

// Option B: single record
use AppDevPanel\Kernel\Collector\QueryRecord;

$collector->logQuery(new QueryRecord(
    sql: 'SELECT * FROM users WHERE id = :id',
    rawSql: 'SELECT * FROM users WHERE id = 42',
    params: [':id' => 42],
    duration: 0.0023,
    line: '/app/src/UserRepository.php:35',
));
```

### Transactions

```php
$collector->collectTransactionStart(isolationLevel: 'READ COMMITTED', line: '...');
$collector->collectTransactionEnd(status: 'commit', line: '...');
```

::: info
<class>\AppDevPanel\Kernel\Collector\DatabaseCollector</class> implements <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class>, depends on <class>\AppDevPanel\Kernel\Collector\TimelineCollector</class>, and uses <class>\AppDevPanel\Kernel\Collector\DuplicateDetectionTrait</class> for detecting repeated queries.
:::

## How It Works

Framework adapters intercept database operations through framework-specific hooks:
- **Symfony**: Doctrine DBAL middleware
- **Laravel**: DB query listener
- **Yii 2**: Log target for DB profiling messages
- **Yii 3**: Query event listeners

## Debug Panel

- **Query count and total time** — summary header with aggregate stats
- **SQL syntax highlighting** — queries displayed with keyword coloring
- **Query type badges** — SELECT, INSERT, UPDATE, DELETE color-coded
- **Row count and timing** — per-query execution metrics
- **Explain plan** — visual EXPLAIN plan tree for SELECT queries
- **Duplicate detection** — highlights repeated identical queries
- **Transaction grouping** — queries grouped by transaction boundaries
