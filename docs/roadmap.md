# Roadmap

Status as of 2026-03-15. See [module-reviews.md](module-reviews.md) for detailed issue descriptions.

## Phase 1: Stabilization & Testing ✓

**Goal**: Fix remaining critical bugs, achieve 90% test coverage.

### 1.1 Critical Bugs — All resolved

| Task | Module | Status |
|------|--------|--------|
| ~~Decouple Debugger from Yii events~~ | Kernel | Done — StartupContext value object |
| ~~Fix `register_shutdown_function` in Debugger constructor~~ | Kernel | Done — moved to startup() with guard |
| ~~Fix DebugServerBroadcastCommand socket lifecycle~~ | CLI | Done |
| ~~Add try-catch for JSON decode in server loop~~ | CLI | Done |

### 1.2 Test Coverage

Current: ~354 tests passing. Expanded from ~328.

| Phase | Module | Status |
|-------|--------|--------|
| ~~Fix existing failures~~ | All | Done — 0 errors, 0 failures |
| Kernel coverage | Kernel | Partial — StartupContext, ProxyDecoratedCalls, Connection added |
| API coverage | API | Partial — DebugController, BashCommand tests added |
| CLI coverage | CLI | Partial — DebugServerCommand, BroadcastCommand added |
| Adapter coverage | Adapter | Pending |

---

## Phase 2: Security Hardening ✓ (core items)

**Goal**: Eliminate all security vulnerabilities in API.

| Task | Module | Status |
|------|--------|--------|
| ~~Add path traversal protection to `files()` endpoint~~ | API | Done — realpath + str_starts_with check |
| ~~Validate and sanitize class names in `object()` endpoint~~ | API | Done — null/empty/exists/container checks |
| ~~Add pagination/LIMIT to database queries~~ | API | Done — limit capped at 10000 |
| ~~Validate git branch names before checkout~~ | API | Done — regex validation |
| ~~Validate locale in translation write endpoint~~ | API | Done — regex validation |
| Add authentication/authorization to inspector endpoints | API | Pending — needs design decision |
| Add URL allowlist to request replay endpoint | API | Pending |
| Add CSRF protection to mutation endpoints | API | Pending |
| Validate postMessage origin in toolbar | Frontend | Pending |

---

## Phase 3: Performance Optimization ✓ (core items)

**Goal**: Optimize for production use with large datasets.

| Task | Module | Status |
|------|--------|--------|
| ~~Reduce SSE poll interval from 1s to 500ms, increase retries~~ | API | Done |
| ~~Optimize backtrace collection with IGNORE_ARGS + depth limit~~ | Kernel | Done |
| Add code splitting / lazy loading for frontend modules | Frontend | Pending |
| Add exponential backoff to SSE reconnection | Frontend | Pending |
| Add virtualization for large lists (react-window) | Frontend | Pending |

---

## Phase 4: Architecture Improvements — In Progress

**Goal**: Improve maintainability and extensibility.

### 4.1 Backend

| Task | Module | Status |
|------|--------|--------|
| ~~Split InspectController into domain-specific controllers~~ | API | Done — DatabaseController, FileController, RoutingController, TranslationController, RequestController |
| ~~Replace ApplicationState with proper DI~~ | API | Done — params injected via DI config |
| ~~Add file locking to FileStorage~~ | Kernel | Done — LOCK_EX writes, flock GC |
| ~~Complete namespace migration (yii-debug → app-dev-panel)~~ | Frontend | Done — collectors.ts, Layout.tsx updated |
| ~~Resolve TODO comments in production code~~ | API | Done — CacheController, DebugController cleaned |
| ~~Replace hardcoded old namespace in class filters~~ | API | Done — uses AppDevPanel namespace |
| Break Connection into ConnectionFactory, SocketReader, Broadcaster | Kernel | Pending |
| Add guard against circular dependency in DebugServiceProvider | Adapter | Pending |
| Make address/port configurable via DI config | CLI | Pending |
| Standardize on `#[AsCommand]` attribute pattern | CLI | Pending |

### 4.2 Frontend

| Task | Module | Status |
|------|--------|--------|
| Generate TypeScript types from backend API | Frontend | Pending |
| Add ErrorBoundary to all route-level components | Frontend | Pending |
| Add shared types package to monorepo | Frontend | Pending |
| Add frontend unit tests with Vitest | Frontend | Pending |
| Add E2E tests with Playwright | Frontend | Pending |

---

## Phase 5: Ecosystem Growth

**Goal**: Enable multi-framework support.

| Task | Priority | Status |
|------|----------|--------|
| Create Symfony adapter | P2 | Pending |
| Create Laravel adapter | P2 | Pending |
| Document adapter creation guide | P2 | Pending |
| Lazy-initialize VarDumper handler | P2 | Pending |

---

## Phase 6: Observability & Documentation

**Goal**: Make the debugger self-debugging. Fill documentation gaps.

| Task | Priority | Status |
|------|----------|--------|
| Add structured logging to Kernel operations | P2 | Pending |
| Add API reference documentation (OpenAPI spec) | P3 | Pending |
| Add contribution guide | P3 | Pending |
| Add logging to CLI debug server operations | P3 | Pending |
| Add integration tests for Adapter with real Yii DI | P3 | Pending |

---

## Summary

| Phase | Done | Remaining | Theme |
|-------|------|-----------|-------|
| 1. Stabilization & Testing | 4 bugs fixed, 26 tests added | More test coverage | Make it reliable |
| 2. Security Hardening | 5 fixes | 4 tasks | Make it safe |
| 3. Performance | 2 optimizations | 3 frontend tasks | Make it fast |
| 4. Architecture | 6 improvements | 9 tasks | Make it maintainable |
| 5. Ecosystem | 0 | 4 tasks | Make it universal |
| 6. Observability & Docs | 0 | 5 tasks | Make it understandable |
| **Total** | **~23 done** | **~25 remaining** | |

## Completed (23+ tasks)

Initial sprint (see [tasks/](../tasks/)):
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

Phases 1–4 sprint:
- Decouple Debugger from Yii events via StartupContext
- Fix shutdown registration (moved to startup with guard)
- Fix existing test failures (4 errors, 1 failure → 0)
- Add 26 new tests across Kernel, API, CLI
- Security: path traversal, class validation, DB pagination, git branch validation, locale validation
- Performance: SSE poll interval, backtrace optimization
- Split InspectController into 5 domain controllers
- Replace ApplicationState global with DI-injected params
- Add file locking to FileStorage (LOCK_EX + flock GC)
- Migrate frontend namespace references to AppDevPanel
- Resolve TODO comments in production code
