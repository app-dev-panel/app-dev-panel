# Test Coverage Plan — Target 90%

## Current State

| Module | Source Files | Test Files | Coverage Estimate | Gap |
|--------|-------------|-----------|-------------------|-----|
| **Kernel** | 42 | 32 | ~60% | 12 untested files |
| **API** | 33 | 8 | ~20% | 25 untested files |
| **Cli** | 3 | 1 | ~15% | 2 untested files (+ 1 broken) |
| **Adapter/Yiisoft** | 1 | 0 | 0% | 1 untested file |
| **Total** | 79 | 41 | ~35-40% | 40 untested files |

**Pre-existing failures**: 38 errors, 28 failures out of 277 tests. These must be fixed first.

## Phase 1: Fix Existing Test Failures (Priority: Critical)

Before any coverage improvements, all 277 existing tests must pass.

### 1.1 Fix Network-Dependent Tests
**Files**: `HttpStreamCollectorTest.php`, `HttpStreamProxyTest.php`
**Problem**: Tests call `fopen('http://example.com')` which fails without network.
**Fix**: Mock the stream operations or use a local server stub. Consider `@group network` annotation and skip in CI.

### 1.2 Fix Missing Dependency Tests
**Files**: `ConsoleAppInfoCollectorTest.php`, `CommandCollectorTest.php`
**Problem**: `Class "Yiisoft\Yii\Console\Event\ApplicationStartup" not found` and `Class "Symfony\Contracts\EventDispatcher\Event" not found`.
**Fix**: Add `yiisoft/yii-console` and `symfony/event-dispatcher-contracts` to `require-dev`, or mock these classes.

### 1.3 Fix Container Proxy Tests
**Files**: `ContainerInterfaceProxyTest.php`
**Problem**: `NotFoundException` from Yii DI container — missing provider definitions.
**Fix**: Register missing services in test container configuration.

### 1.4 Fix Deprecation Warnings
**Problem**: 15 PHPUnit deprecations (likely `#[DataProvider]` attribute migration from annotations).
**Fix**: Replace `@dataProvider` PHPDoc annotations with `#[DataProvider]` attributes.

### 1.5 Fix API Test Failures
**Files**: Various tests in `libs/API/tests/`
**Problem**: Missing mock configurations, broken assertions.
**Fix**: Review each failure, update mocks and assertions.

## Phase 2: Kernel Module — Target 90% (Priority: High)

Kernel is the most critical module. Currently ~60% covered.

### 2.1 DebugServer Tests (3 files, high impact)
| File | Class | Test Strategy |
|------|-------|---------------|
| `Connection.php` | Socket-based debug server connection | Mock socket functions, test message serialization/deserialization |
| `LoggerDecorator.php` | Logger that sends to debug server | Mock Connection, test all PSR-3 log levels |
| `VarDumperHandler.php` | Sends var_dump data to debug server | Mock Connection, test data formatting |

### 2.2 Debugger Infrastructure (3 files)
| File | Class | Test Strategy |
|------|-------|---------------|
| `DebuggerIdGenerator.php` | Generates unique debug entry IDs | Test uniqueness, format, thread-safety |
| `ProxyDecoratedCalls.php` | Trait for decorated proxy calls | Test via concrete proxy implementations |
| `ServiceMethodProxy.php` | Method-level service proxy | Test proxy method interception and data collection |

### 2.3 Interfaces and Traits (4 files — low priority)
These are interfaces/traits — coverage comes from their implementations:
- `CollectorInterface.php` — Already tested via concrete collectors
- `CollectorTrait.php` — Already tested via concrete collectors
- `ProxyLogTrait.php` — Test via proxy implementations
- `SummaryCollectorInterface.php` — Interface only
- `StorageInterface.php` — Interface only
- `StreamWrapperInterface.php` — Interface only

## Phase 3: API Module — Target 90% (Priority: High)

API has the biggest gap: 33 source files but only 8 test files.

### 3.1 Controllers (6 files, highest impact)
| File | Class | Test Strategy |
|------|-------|---------------|
| `DebugController.php` | Main debug API controller | Integration tests with MemoryStorage, test all endpoints |
| `CacheController.php` | Cache management | Mock cache services, test CRUD operations |
| `CommandController.php` | Command execution | Mock CommandInterface, test exec and result parsing |
| `ComposerController.php` | Composer info | Mock filesystem, test JSON parsing |
| `GitController.php` | Git operations | Mock process execution, test output parsing |
| `InspectController.php` | Application inspection | Mock DI container, test introspection endpoints |
| `OpcacheController.php` | OPcache info | Mock `opcache_get_status`, test response format |

### 3.2 Commands (3 files)
| File | Class | Test Strategy |
|------|-------|---------------|
| `PHPUnitCommand.php` | PHPUnit execution wrapper | Test command building, output parsing |
| `CodeceptionCommand.php` | Codeception wrapper | Test command building, output parsing |
| `PsalmCommand.php` | Psalm wrapper | Test command building, output parsing |

### 3.3 HTTP/Middleware (2 files)
| File | Class | Test Strategy |
|------|-------|---------------|
| `HttpApplicationWrapper.php` | PSR-15 application wrapper | Test middleware pipeline execution |
| `MiddlewareDispatcherMiddleware.php` | Debug middleware | Test data collection during request processing |

### 3.4 Database Providers (2 files)
| File | Class | Test Strategy |
|------|-------|---------------|
| `CycleSchemaProvider.php` | Cycle ORM schema | Mock Cycle ORM registry, test schema extraction |
| `DbSchemaProvider.php` | Already has test | Extend existing `DbSchemaProviderTest` |

### 3.5 Test Reporters (2 files)
| File | Class | Test Strategy |
|------|-------|---------------|
| `PHPUnitJSONReporter.php` | JSON test output | Test report generation with sample test results |
| `CodeceptionJSONReporter.php` | JSON test output | Test report generation with sample test results |

### 3.6 Other (4 files)
| File | Class | Test Strategy |
|------|-------|---------------|
| `ServerSentEventsStream.php` | SSE output stream | Mock output, test event formatting |
| `RouteCollectorWrapper.php` | Route collection | Test route registration and serialization |
| `ModuleFederationAssetBundle.php` | Asset bundle | Simple property test |
| Interfaces/exceptions | Simple structure tests | Test exception messages, interface contracts |

## Phase 4: CLI Module — Target 90% (Priority: Medium)

Only 1 test exists (DebugResetCommand). Need 2 more.

| File | Class | Test Strategy |
|------|-------|---------------|
| `DebugServerCommand.php` | Starts debug WebSocket server | Mock Connection, test server lifecycle |
| `DebugServerBroadcastCommand.php` | Broadcasts to debug clients | Mock Connection, test message broadcasting |

## Phase 5: Adapter Module — Target 80% (Priority: Low)

| File | Class | Test Strategy |
|------|-------|---------------|
| `DebugServiceProvider.php` | Yii 3 DI registration | Test that all expected services are registered, test configuration merging |

Note: Adapter tests require Yii 3 packages in dev dependencies.

## Implementation Priority

```
Phase 1  ───▶  Phase 2  ───▶  Phase 3  ───▶  Phase 4  ───▶  Phase 5
Fix bugs       Kernel 90%     API 90%        CLI 90%        Adapter 80%
(critical)     (high)         (high)         (medium)       (low)
~2 days        ~3 days        ~5 days        ~1 day         ~0.5 day
```

## Estimated Effort

| Phase | New Tests | Estimated Lines | Coverage Gain |
|-------|----------|-----------------|--------------|
| Phase 1: Fix existing | 0 (fix ~66 failures) | ~200 lines modified | +15% (passing tests count) |
| Phase 2: Kernel | ~15 test classes | ~800 lines | +15% |
| Phase 3: API | ~20 test classes | ~1500 lines | +25% |
| Phase 4: CLI | ~2 test classes | ~150 lines | +3% |
| Phase 5: Adapter | ~1 test class | ~100 lines | +2% |
| **Total** | ~38 test classes | ~2750 lines | ~35-40% → 90% |

## Testing Guidelines

1. **Use MemoryStorage** for storage-dependent tests (no filesystem needed)
2. **Mock external services** (network, database, cache) — no integration tests in unit suite
3. **Use data providers** for parameterized tests (`#[DataProvider]` attribute, not annotations)
4. **Follow existing patterns** — see `AbstractCollectorTestCase` for collector test structure
5. **One test class per source class** — keep 1:1 mapping for maintainability
6. **Test edge cases**: empty data, null values, error conditions, boundary values
7. **Use strict assertions**: `assertSame()` over `assertEquals()`, exact type checks
