---
description: "ADP Cycle ORM adapter for database schema inspection. Lightweight — no collectors or lifecycle wiring."
---

# Cycle ORM Adapter

The Cycle ORM adapter is a lightweight adapter that provides database schema inspection only. Unlike full adapters, it has no collectors, event listeners, or lifecycle wiring.

## Installation

```bash
composer require app-dev-panel/adapter-cycle
```

## Usage

Register <class>AppDevPanel\Adapter\Cycle\Inspector\CycleSchemaProvider</class> as <class>AppDevPanel\Api\Inspector\Database\SchemaProviderInterface</class> in your framework's DI container:

```php
use AppDevPanel\Adapter\Cycle\Inspector\CycleSchemaProvider;
use AppDevPanel\Api\Inspector\Database\SchemaProviderInterface;
use Cycle\Database\DatabaseProviderInterface;

SchemaProviderInterface::class => static fn (DatabaseProviderInterface $db) => new CycleSchemaProvider($db),
```

## Capabilities

| Method | Status | Description |
|--------|--------|-------------|
| `getTables()` | Implemented | Lists all tables with columns, primary keys, row counts |
| `getTable()` | Implemented | Single table schema with paginated records |
| `explainQuery()` | Stub | Returns empty array |
| `executeQuery()` | Stub | Returns empty array |

## When to Use

Use the Cycle adapter when your application uses Cycle ORM and you want database schema inspection in the ADP panel. Combine it with a full framework adapter (Yii 3, Symfony, Laravel) that handles lifecycle, collectors, and API wiring.
