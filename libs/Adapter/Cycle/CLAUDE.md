# Cycle ORM Adapter

Database schema provider for ADP via Cycle ORM. Lightweight adapter — only provides `SchemaProviderInterface` implementation.
Unlike full adapters (Yii3, Symfony, Laravel, Yii2, Spiral), this adapter has no collectors, event listeners, or lifecycle wiring.

## Package

- Composer: `app-dev-panel/adapter-cycle`
- Namespace: `AppDevPanel\Adapter\Cycle\`
- PHP: 8.4+
- Dependencies: `app-dev-panel/api`, `cycle/database ^2.0`

## Directory Structure

```
src/
└── Inspector/
    └── CycleSchemaProvider.php    # SchemaProviderInterface via Cycle DatabaseProviderInterface
tests/
└── Unit/
    └── Inspector/
        └── CycleSchemaProviderTest.php
```

## CycleSchemaProvider

Implements `AppDevPanel\Api\Inspector\Database\SchemaProviderInterface`.

Constructor: `__construct(Cycle\Database\DatabaseProviderInterface $databaseProvider)`

| Method | Status | Description |
|--------|--------|-------------|
| `getTables()` | Implemented | Lists all tables with columns, primary keys, row counts |
| `getTable($name, $limit, $offset)` | Implemented | Single table schema + paginated records |
| `explainQuery($sql, $params, $analyze)` | Stub | Returns `[]` |
| `executeQuery($sql, $params)` | Stub | Returns `[]` |

Column serialization maps Cycle `ColumnInterface` to: `name`, `size`, `type` (internal), `dbType` (database), `defaultValue`, `comment` (always null), `allowNull`.

## Usage

Register `CycleSchemaProvider` as `SchemaProviderInterface` in the framework's DI container:

```php
use AppDevPanel\Adapter\Cycle\Inspector\CycleSchemaProvider;
use AppDevPanel\Api\Inspector\Database\SchemaProviderInterface;
use Cycle\Database\DatabaseProviderInterface;

SchemaProviderInterface::class => static fn (DatabaseProviderInterface $db) => new CycleSchemaProvider($db),
```

## Architecture Comparison

| Aspect | Full Adapters (Yii3/Symfony/Laravel/Yii2/Spiral) | Cycle Adapter |
|--------|----------------------------------------------|---------------|
| Scope | Full framework bridge | Database inspector only |
| Collectors | Multiple (log, event, request, etc.) | None |
| Event listeners | Request/console lifecycle | None |
| Proxy wiring | Logger, event dispatcher, HTTP client | None |
| DI integration | Framework-specific bundle/module | Manual registration |
| Database inspector | Framework-specific schema provider | `CycleSchemaProvider` |
