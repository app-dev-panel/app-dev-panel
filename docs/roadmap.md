# Roadmap

Status as of 2026-03-15. See [module-reviews.md](module-reviews.md) for detailed issue descriptions.

## Phase 1: Stabilization & Testing

**Goal**: Fix remaining critical bugs, achieve 90% test coverage.

### 1.1 Critical Bugs

| Task | Module | Priority | Details |
|------|--------|----------|---------|
| Decouple Debugger from Yii events | Kernel | P0 | `Debugger` imports Yii event classes directly; should accept generic events via adapter |
| Fix `register_shutdown_function` in Debugger constructor | Kernel | P0 | Called unconditionally, runs even for ignored requests |
| Fix DebugServerBroadcastCommand socket lifecycle | CLI | P0 | Signal handler never runs, socket never closed on success |
| Add try-catch for JSON decode in server loop | CLI | P1 | Malformed JSON crashes the server |

### 1.2 Test Coverage

Current: ~35-40%. Target: 90%. See [test-coverage-plan.md](test-coverage-plan.md).

| Phase | Module | New Tests | Estimated Lines | Priority |
|-------|--------|----------|-----------------|----------|
| Fix existing failures | All | 0 (fix ~66 failures) | ~200 modified | Critical |
| Kernel coverage | Kernel | ~15 classes | ~800 | High |
| API coverage | API | ~20 classes | ~1500 | High |
| CLI coverage | CLI | ~2 classes | ~150 | Medium |
| Adapter coverage | Adapter | ~1 class | ~100 | Low |

---

## Phase 2: Security Hardening

**Goal**: Eliminate all security vulnerabilities in API.

| Task | Module | Priority | Risk |
|------|--------|----------|------|
| Add authentication/authorization to inspector endpoints | API | P0 | Unauthorized access to debug data |
| Add path traversal protection to `files()` endpoint | API | P0 | Directory escape via symlinks |
| Validate and sanitize class names in `object()` endpoint | API | P0 | Arbitrary class instantiation |
| Add URL allowlist to request replay endpoint | API | P0 | SSRF to internal services |
| Add pagination/LIMIT to database queries | API | P0 | DoS via unbounded SELECT |
| Validate git branch names before checkout | API | P0 | Command injection |
| Validate locale in translation write endpoint | API | P0 | Path traversal |
| Add CSRF protection to mutation endpoints | API | P2 | Cross-site request forgery |
| Validate postMessage origin in toolbar | Frontend | P2 | Message spoofing |

---

## Phase 3: Performance Optimization

**Goal**: Optimize for production use with large datasets.

| Task | Module | Priority | Impact |
|------|--------|----------|--------|
| Replace polling SSE with event-driven approach | API | P1 | Free up PHP workers (currently `sleep(1)` blocks) |
| Add configurable backtrace depth/toggle to proxies | Kernel | P2 | Reduce overhead in hot paths |
| Add code splitting / lazy loading for frontend modules | Frontend | P1 | Smaller initial bundle |
| Add exponential backoff to SSE reconnection | Frontend | P1 | Prevent server overload on connection loss |
| Add virtualization for large lists (react-window) | Frontend | P2 | Handle 1000+ entries without perf degradation |

---

## Phase 4: Architecture Improvements

**Goal**: Improve maintainability and extensibility.

### 4.1 Backend

| Task | Module | Priority | Impact |
|------|--------|----------|--------|
| Split InspectController into domain-specific controllers | API | P1 | SRP — currently 490 lines, 15+ methods |
| Replace ApplicationState with proper DI | API | P1 | Remove global mutable state |
| Break Connection into ConnectionFactory, SocketReader, Broadcaster | Kernel | P2 | Better SRP, testability |
| Add file locking to FileStorage | Kernel | P1 | Prevent race conditions on concurrent requests |
| Complete namespace migration (yii-debug → app-dev-panel) | Adapter | P1 | Consistency across codebase |
| Add guard against circular dependency in DebugServiceProvider | Adapter | P1 | Stability |
| Make address/port configurable via DI config | CLI | P2 | Deployment flexibility |
| Standardize on `#[AsCommand]` attribute pattern | CLI | P2 | Consistency |

### 4.2 Frontend

| Task | Module | Priority | Impact |
|------|--------|----------|--------|
| Generate TypeScript types from backend API | Frontend | P1 | Type safety, prevent frontend/backend drift |
| Add ErrorBoundary to all route-level components | Frontend | P2 | Prevent full app crash on component error |
| Add shared types package to monorepo | Frontend | P3 | Code organization across 3 packages |
| Add frontend unit tests with Vitest | Frontend | P3 | Test coverage |
| Add E2E tests with Playwright | Frontend | P3 | Integration test coverage |

---

## Phase 5: Ecosystem Growth

**Goal**: Enable multi-framework support.

| Task | Priority | Details |
|------|----------|---------|
| Create Symfony adapter | P2 | Map Symfony lifecycle events to Debugger, register proxies in Symfony DI, provide bundle |
| Create Laravel adapter | P2 | Map Laravel lifecycle events to Debugger, register proxies in Laravel container, provide service provider |
| Document adapter creation guide | P2 | Reference implementation: `libs/Adapter/Yiisoft/` |
| Lazy-initialize VarDumper handler | P2 | Avoid early socket creation at bootstrap |

---

## Phase 6: Observability & Documentation

**Goal**: Make the debugger self-debugging. Fill documentation gaps.

| Task | Priority | Details |
|------|----------|---------|
| Add structured logging to Kernel operations | P2 | Flush time, collector counts, storage size |
| Add API reference documentation (OpenAPI spec) | P3 | Machine-readable endpoint documentation |
| Add contribution guide | P3 | Onboarding for new contributors |
| Resolve TODO comments in production code | P3 | 3 TODOs in API controllers |
| Implement or remove ModuleFederationAssetBundle | P3 | Currently an empty stub class |
| Add return type to `eventListeners()` | P2 | Type safety |
| Replace hardcoded old namespace in class filters | P2 | Correctness |
| Add logging to CLI debug server operations | P3 | Debugging server issues |
| Add integration tests for Adapter with real Yii DI | P3 | Test coverage |

---

## Summary

| Phase | Open Tasks | Theme |
|-------|-----------|-------|
| 1. Stabilization & Testing | 4 bugs + ~38 test classes | Make it reliable |
| 2. Security Hardening | 9 tasks | Make it safe |
| 3. Performance | 5 tasks | Make it fast |
| 4. Architecture | 13 tasks | Make it maintainable |
| 5. Ecosystem | 4 tasks | Make it universal |
| 6. Observability & Docs | 9 tasks | Make it understandable |
| **Total** | **~44 tasks** | |

## Completed (14 tasks)

All from the initial sprint (see [tasks/](../tasks/)):
- FileStorage bug fixes (variable shadowing, error handling)
- `debug_backtrace()` optimization
- LoggerInterfaceProxy deduplication
- CollectorTrait::reset() visibility
- Operator precedence in Command classes
- Null dereference in createJsPanelResponse
- Empty catch blocks replaced with logging
- CLI server error handling
- Command name mismatch fix
- Duplicated enabled-check extraction
- Magic error codes replaced with constants
- Import ordering fix
- Commented-out code removal
