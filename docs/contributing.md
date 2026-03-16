# Contributing

## Setup

```bash
composer install
cd libs/frontend && npm install
```

## Development Workflow

1. Create a feature branch
2. Implement changes
3. Run the full pipeline (see below)
4. Submit a pull request

## Pipeline

All checks must pass before merging.

### PHP

```bash
composer fix          # Format + lint + analyze
composer test         # Run all PHPUnit tests
```

### Frontend (if changed)

```bash
cd libs/frontend
npm run check         # Format check + lint
```

## Code Style

### PHP

- PER-CS (PER-2) via [Mago](https://mago.carthage.software/)
- `declare(strict_types=1)` in every file
- `final class` by default
- PSR interfaces for all abstractions

### TypeScript

- Prettier 3.8+: single quotes, trailing commas, 120 char width, 4-space indent, `objectWrap: "collapse"`
- ESLint 9 with @typescript-eslint
- `type` over `interface` (`consistent-type-definitions: "type"`)
- Functional React components, Redux Toolkit patterns

## Module Dependencies

```
Adapter → API → Kernel
  │               ↑
  └───────────────┘
Cli → Kernel
Frontend → API (HTTP only)
```

| Module | Can depend on | Cannot depend on |
|--------|--------------|-----------------|
| Kernel | PSR interfaces, generic PHP libs | API, Cli, Adapter |
| API | Kernel, PSR interfaces | Adapter, Cli |
| Cli | Kernel, Symfony Console | API, Adapter |
| Adapter | Kernel, API, framework packages | Other adapters |

## Testing Conventions

- One test class per source class: `src/Foo/Bar.php` → `tests/Unit/Foo/BarTest.php`
- Inline mocks only (`$this->createMock()`, anonymous classes)
- No shared test utilities, no test environment classes
- `assertSame()` over `assertEquals()`
- Data providers via `#[DataProvider('name')]` attribute
- Collectors extend `AbstractCollectorTestCase`

## Adding a Collector

1. Create class implementing `CollectorInterface` in `libs/Kernel/src/Collector/`
2. Implement `startup()`, `shutdown()`, `getCollected()`
3. Write test extending `AbstractCollectorTestCase`
4. Register in adapter config (`libs/Adapter/Yiisoft/config/params.php`)

## Adding an Inspector Page

### Backend

1. Create controller in `libs/API/src/Inspector/Controller/`
2. Add route in `libs/API/config/routes.php`
3. Write controller test extending `ControllerTestCase`

### Frontend

1. Create page component in `packages/panel/src/Module/Inspector/Pages/`
2. Add RTK Query endpoint in `packages/panel/src/Module/Inspector/API/`
3. Add route to inspector module's route config

## Mago Baselines

Existing issues are suppressed via baseline files. New code must not introduce new issues.

```bash
composer lint:baseline      # Regenerate lint baseline
composer analyze:baseline   # Regenerate analyze baseline
```
