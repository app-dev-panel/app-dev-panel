# Plan: Fix Mago Baseline Errors

## Current State

| Module | Lint | Analyze | Total |
|--------|-----:|--------:|------:|
| libs/Kernel | 71 | 707 | 778 |
| libs/API | 41 | 479 | 520 |
| libs/Cli | 2 | 38 | 40 |
| libs/Testing | 16 | 82 | 98 |
| libs/Adapter/Yii3 | 19 | 228 | 247 |
| libs/Adapter/Symfony | 14 | 452 | 466 |
| libs/Adapter/Laravel | 13 | 652 | 665 |
| libs/Adapter/Yii2 | 14 | 313 | 327 |
| libs/Adapter/Cycle | 0 | 41 | 41 |
| **Total** | **190** | **2992** | **3182** |

---

## Phase 1: Lint Fixes (190 entries)

Lint issues are mostly architectural/style — many require manual refactoring. Grouped by fix strategy.

### 1.1 `no-isset` / `no-empty` (50 entries)

Replace `isset()` with null-safe operators, explicit null checks, or `array_key_exists()`.
Replace `empty()` with explicit comparisons (`=== []`, `=== ''`, `=== 0`).

| Module | isset | empty | Total |
|--------|------:|------:|------:|
| libs/API | 14 | 2 | 16 |
| libs/Kernel | 12 | 6 | 18 |
| libs/Adapter/Yii3 | 7 | 0 | 7 |
| libs/Testing | 4 | 0 | 4 |
| libs/Adapter/Yii2 | 3 | 0 | 3 |
| libs/Adapter/Symfony | 2 | 0 | 2 |
| libs/Adapter/Laravel | 2 | 0 | 2 |

**Files to change:** ~25 files. Mechanical — safe, low risk.

### 1.2 `no-error-control-operator` (17 entries)

Replace `@func()` with explicit error handling (try/catch or `set_error_handler`).

| Module | Count |
|--------|------:|
| libs/Kernel | 17 |

Concentrated in: `StreamWrapper`, `FilesystemStreamProxy`, `HttpStreamProxy`, `Broadcaster`, `Connection`, `SocketReader`, `FileStorage`, test stubs.

**Risk:** Medium — `@` is used intentionally for socket/file operations. Each case needs review. Some may need to stay (e.g., `@stream_socket_accept` where suppressing warnings is standard practice). For those, add `// mago-ignore` comments or update baseline.

### 1.3 `too-many-methods` (44 entries)

Classes/interfaces exceed method count threshold. Fix strategies:
- **StreamWrapper/StreamWrapperInterface (2):** Dictated by PHP stream wrapper protocol — cannot reduce. Baseline.
- **PSR implementations (LoggerInterfaceProxy, etc.):** Interface-driven — cannot reduce. Baseline.
- **Test classes (16):** Split large test classes into trait-based test groups, or increase threshold. Baseline is acceptable for tests.
- **Controllers (Dumper, FlattenException, FileStorage):** Extract helper classes or services.
- **Proxy classes:** Interface-driven. Baseline.

**Actionable:** ~8 source classes worth splitting. Rest stays in baseline.

### 1.4 `cyclomatic-complexity` / `kan-defect` (31 entries)

Complex classes. Fix strategies:
- Extract methods / break into smaller services
- Use early returns, strategy pattern, or lookup tables

| Module | complexity | kan-defect | Total |
|--------|----------:|----------:|------:|
| libs/Kernel | 6 | 2 | 8 |
| libs/API | 6 | 1 | 7 |
| libs/Testing | 4 | 4 | 8 |
| libs/Adapter/Yii2 | 3 | 3 | 6 |
| libs/Adapter/Laravel | 3 | 2 | 5 |
| libs/Adapter/Symfony | 3 | 2 | 5 |
| libs/Adapter/Yii3 | 1 | 1 | 2 |
| libs/Cli | 1 | 1 | 2 |

**Actionable:** Refactor the worst offenders (Dumper, CommandController, InspectController, Module). Some (e.g. E2E ScenarioTest with kan-defect 4.1) stay in baseline.

### 1.5 `excessive-parameter-list` (9 entries)

Constructors/methods with too many parameters. Fix by introducing parameter objects or config DTOs.

| Module | Count |
|--------|------:|
| libs/Kernel | 9 |
| libs/Adapter/Yii3 | 5 |
| libs/API | 1 |

**Actionable:** Introduce value objects for Collector constructors (CacheCollector, DatabaseCollector, QueueCollector, ServiceCollector), ProxyLogTrait methods.

### 1.6 Other lint (6 entries)

- `require-preg-quote-delimiter` (5): Add delimiter arg to `preg_quote()` in BacktraceIgnoreMatcherTest.
- `no-debug-symbols` (1): Remove debug function in InspectController.
- `no-literal-password` (1): Likely test fixture — baseline.
- `no-empty-catch-clause` (4): Add logging or comments to empty catch blocks.

---

## Phase 2: Analyze Fixes (2992 entries)

Most analyzer entries are `mixed-*` type inference issues (2100+). Strategy: add type annotations to progressively eliminate them.

### 2.1 Quick wins — redundant/dead code (~75 entries)

| Error type | Count | Fix |
|------------|------:|-----|
| `redundant-type-comparison` | 27 | Remove `instanceof`/`is_*` checks where type is already known |
| `redundant-comparison` | 10 | Remove always-true/false comparisons |
| `redundant-cast` | 8 | Remove unnecessary `(int)`, `(string)` casts |
| `redundant-null-coalesce` | 8 | Remove `?? null` or `?? default` where value can't be null |
| `redundant-condition` | 3 | Simplify conditions |
| `redundant-logical-operation` | 7 | Remove redundant `&&`/`||` operands |
| `redundant-docblock-type` | 1 | Remove docblock that repeats native type |
| `impossible-condition` | 1 | Remove dead branch |

**Concentrated in:** libs/Testing (26), libs/Kernel (11), libs/Adapter/Yii2 (12), libs/API (6).
**Risk:** Low — removing dead code. Run tests after each batch.

### 2.2 Null-safety fixes (~75 entries)

| Error type | Count | Fix |
|------------|------:|-----|
| `possibly-null-argument` | 27 | Add null checks before passing |
| `possibly-null-operand` | 8 | Add null guards |
| `possibly-null-array-index` | 1 | Add null check |
| `null-argument` | 11 | Fix callers passing null to non-nullable params |
| `method-access-on-null` | 26 | Add null checks or fix type narrowing |
| `possible-method-access-on-null` | 7 | Add null checks |

**Concentrated in:** libs/Adapter/Laravel (37), libs/Kernel (17), libs/CLI (5).

### 2.3 `unevaluated-code` (48 entries, all Kernel)

Dead code after return/throw. Remove or restructure.

### 2.4 `unused-*` (35 entries)

| Error type | Count | Fix |
|------------|------:|-----|
| `unused-property` | 20 | Remove unused class properties |
| `unused-method` | 15 | Remove unused methods |
| `unused-statement` | 10 | Remove unused expressions |

**Risk:** Medium — verify via grep before removing. Some may be used via magic methods.

### 2.5 `non-existent-*` (171 entries)

| Error type | Count | Fix |
|------------|------:|-----|
| `non-existent-class-like` | 125 | Missing vendor types — mostly phpDoc refs to uninstalled packages. Baseline. |
| `non-existent-class` | 43 | Same — missing Laravel/Symfony classes in adapter contexts. Baseline. |
| `non-existent-method` | 41 | Magic methods or wrong interface. Add `@method` annotations. |
| `non-existent-property` | 2 | False positive (test accessing implementation). Baseline. |
| `non-existent-function` | 1 | Laravel `class_basename()` — add FQN backslash. |

**Actionable:** ~42 entries. Rest stays in baseline.

### 2.6 Type annotations for `mixed-*` elimination (~2100 entries)

The largest category. Strategy: add `@var`, `@param`, `@return` annotations and typed properties.

Priority order (best ROI — annotate shared infrastructure first):
1. **Storage layer** (FileStorage::read returns `array<string, mixed>`) — cascades to all consumers
2. **CollectorInterface::getCollected()** return types — cascades to repository + API
3. **Dumper** — heavy mixed usage, central utility
4. **Request/Response data** — arrays from PSR-7 `getQueryParams()`, `getParsedBody()`

This is a massive effort. Tackle incrementally per module, 50-100 entries at a time.

### 2.7 `missing-magic-method` (124 entries)

Add `@method` docblock tags to proxy classes. Concentrated in:
- libs/API (58) — controller-related
- libs/Adapter/Symfony (23) — DI container
- libs/Kernel (22) — proxy trait users

### 2.8 `invalid-method-access` / `invalid-property-access` (143 entries)

Accessing methods/properties through interfaces that don't declare them. Fix by:
- Adding `@method` annotations to interfaces
- Using `assert($obj instanceof ConcreteClass)` narrowing
- Fixing interface declarations

---

## Execution Order

### Round 1: Quick wins (est. ~150 entries eliminated)
1. `require-preg-quote-delimiter` (5) — Kernel tests
2. `no-debug-symbols` (1) — API InspectController
3. `no-empty-catch-clause` (4) — add comments/logging
4. Redundant code (75) — remove dead comparisons, casts, coalesces
5. `unevaluated-code` (48) — remove dead code after return/throw
6. `unused-statement` (10) — remove unused expressions
7. Regenerate baselines, run tests

### Round 2: isset/empty + null-safety (est. ~125 entries eliminated)
1. `no-isset` / `no-empty` (50) — replace with explicit checks
2. Null-safety fixes (75) — add null guards
3. Regenerate baselines, run tests

### Round 3: Error control + empty catch (est. ~20 entries eliminated)
1. `no-error-control-operator` (17) — replace `@` with try/catch where safe
2. `no-empty-catch-clause` remaining
3. Regenerate baselines, run tests

### Round 4: Type annotations — Storage + Collectors (est. ~200 entries eliminated)
1. Add return type annotations to `FileStorage`, `MemoryStorage`
2. Add return types to `CollectorInterface::getCollected()` and implementations
3. Add `@var` annotations to key array processing in API controllers
4. Regenerate baselines, run tests

### Round 5: Magic method annotations (est. ~120 entries eliminated)
1. Add `@method` docblocks to proxy classes and controllers
2. `non-existent-method` — add missing method annotations
3. Regenerate baselines, run tests

### Round 6: Unused code cleanup (est. ~35 entries eliminated)
1. Verify and remove `unused-property`, `unused-method`
2. Regenerate baselines, run tests

### Round 7: Structural refactoring (est. ~40 entries eliminated)
1. `excessive-parameter-list` — introduce config DTOs for Collectors
2. `cyclomatic-complexity` — extract methods in worst offenders
3. Regenerate baselines, run tests

### Round 8: Remaining mixed-* annotations (ongoing)
1. Module-by-module type annotation pass
2. Priority: Kernel → API → Adapters
3. Each batch: annotate, test, regenerate baseline

---

## What Stays in Baseline (permanently)

| Category | Count | Reason |
|----------|------:|--------|
| `too-many-methods` on interface-driven classes | ~30 | PSR/stream protocol dictates method count |
| `non-existent-class-like` / `non-existent-class` | ~168 | Vendor types not installed in monorepo root |
| `mixed-*` on truly dynamic data (JSON, config arrays) | ~500+ | Cannot type without generics/runtime checks |
| `string-member-selector` on proxy dispatch | ~58 | Dynamic by design |
| `impossible-type-comparison` | 2 | Static variable — false positive |
| Test-only lint issues (`too-many-methods`, `kan-defect`) | ~20 | Test organization, not production code |

**Estimated reducible:** ~700-800 entries (from 3182 down to ~2400).
