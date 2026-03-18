# Cycle ORM Adapter

Database schema provider for ADP via Cycle ORM. Lightweight adapter — only provides `SchemaProviderInterface` implementation.

## Package

- Composer: `app-dev-panel/adapter-cycle`
- Namespace: `AppDevPanel\Adapter\Cycle\`
- PHP: 8.4+
- Dependencies: `app-dev-panel/api`, `cycle/database`

## Directory Structure

```
src/
└── Inspector/
    └── CycleSchemaProvider.php    # SchemaProviderInterface via Cycle DatabaseProviderInterface
```

## Usage

Register `CycleSchemaProvider` as `SchemaProviderInterface` in the framework's DI container:

```php
use AppDevPanel\Adapter\Cycle\Inspector\CycleSchemaProvider;
use AppDevPanel\Api\Inspector\Database\SchemaProviderInterface;

SchemaProviderInterface::class => static fn (DatabaseProviderInterface $db) => new CycleSchemaProvider($db),
```
