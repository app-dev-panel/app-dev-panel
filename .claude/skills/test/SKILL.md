---
name: test
description: Write tests for new or modified PHP code. Use after implementing features or fixes to ensure test coverage. Follows project conventions — inline mocks, AbstractCollectorTestCase for collectors, strict assertions, data providers via attributes.
argument-hint: "[file or class to test]"
allowed-tools: Read, Write, Edit, Grep, Glob, Bash, Agent
---

# Test Writer

Write tests for: $ARGUMENTS

## Rules

1. **One test class per source class**. Path mirrors source: `src/Foo/Bar.php` → `tests/Unit/Foo/BarTest.php`.
2. **Always `declare(strict_types=1)`**.
3. **Extend `TestCase`** (PHPUnit). Collectors extend `AbstractCollectorTestCase` from `libs/Kernel/tests/Shared/`.
4. **Naming**: `test` + camelCase description. Example: `testCollectWithEmptyData`, `testHandleNotFoundException`.
5. **No test environment setup classes**. No custom TestCase bases (except AbstractCollectorTestCase), no bootstrap files, no container builders, no service locators.
6. **Inline mocks only**:
   - `$this->createMock()` / `$this->createStub()` for simple mocks.
   - Anonymous classes for PSR interfaces (RequestHandlerInterface, ResponseInterface, etc.).
   - Never create shared mock factories or test utility classes.
7. **Private helper methods** for repeated construction within the same test class. Name: `createXxx()`.
8. **Strict assertions**: `assertSame()` over `assertEquals()`. Use `assertInstanceOf()`, `assertCount()`, `assertArrayHasKey()`.
9. **Data providers** via `#[DataProvider('providerName')]` attribute (not `@dataProvider`). Providers are `public static`, return `iterable`.
10. **No network calls**. Mock HTTP, sockets, filesystem. Use `MemoryStorage` not `FileStorage`.
11. **Test structure**: Arrange → Act → Assert. No section comments.
12. **Namespace** per `autoload-dev` PSR-4:
    - Kernel: `AppDevPanel\Kernel\Tests\Unit\...`
    - API: `AppDevPanel\Api\Tests\Unit\...`
    - Cli: `AppDevPanel\Cli\Tests\Unit\...`

## Before Writing

1. Read the source file — understand public methods, constructor deps, edge cases.
2. Read existing tests in the same module — match style exactly.
3. For collectors: read `AbstractCollectorTestCase` and an existing collector test (e.g., `LogCollectorTest`).

## After Writing

1. Run `vendor/bin/phpunit --filter=ClassName` — all tests must pass.
2. Run `vendor/bin/mago fmt` on the new test file.
3. Run `vendor/bin/mago lint` and `vendor/bin/mago analyze` — fix issues.

## Anti-Patterns

- No `TestHelper`, `TestFactory`, `TestContainer`, `MockBuilder` classes.
- No `setUp()` / `tearDown()` unless absolutely necessary.
- No `@group`, `@covers` annotations.
- Don't test interfaces/traits directly — test via concrete implementations.
- No integration tests requiring running services.
