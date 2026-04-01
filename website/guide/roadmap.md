---
title: Roadmap
---

# Roadmap

ADP development follows a phased approach. This page tracks progress and planned work.

## Phase 1: Stabilization & Testing ✅

All critical bugs resolved. Test coverage expanding across modules.

- Decoupled <class>AppDevPanel\Kernel\Debugger</class> from framework-specific events via <class>AppDevPanel\Kernel\StartupContext</class>
- Fixed shutdown registration, socket lifecycle, JSON error handling
- 755+ PHP tests, 328 frontend tests passing

## Phase 2: Security Hardening ✅ (core)

Eliminated critical security vulnerabilities in the API.

- Path traversal protection on file endpoints
- Input validation for class names, git branches, locales, database queries
- Pagination limits on all list endpoints

**Remaining:** Authentication/authorization for inspector, CSRF protection, URL allowlist for request replay.

## Phase 3: Performance Optimization ✅ (core)

- Optimized SSE poll interval (1s → 500ms)
- Reduced backtrace overhead (`IGNORE_ARGS` + depth limit)

**Remaining:** Frontend code splitting, list virtualization for large datasets.

## Phase 4: Architecture Improvements 🔄

Backend improvements (mostly complete):

- Split monolithic <class>AppDevPanel\Api\Inspector\Controller\InspectController</class> into domain-specific controllers
- Replaced global <class>AppDevPanel\Api\Inspector\ApplicationState</class> with proper DI
- Added file locking to <class>AppDevPanel\Kernel\Storage\FileStorage</class> (atomic writes + non-blocking GC)
- Completed namespace migration to `AppDevPanel`

**Remaining backend:** Connection refactoring, CLI configurability, circular dependency guards.

**Remaining frontend:** TypeScript type generation from API, `ErrorBoundary` coverage, shared types package.

## Phase 5: Ecosystem Growth 🔄

Multi-framework support:

| Adapter | Status |
|---------|--------|
| Symfony | ✅ Complete |
| Yii 2 | ✅ Complete |
| Yii 3 (Yiisoft) | ✅ Complete |
| Laravel | ✅ Complete |

See [Feature Matrix](/guide/feature-matrix) for detailed adapter capabilities.

## Phase 6: Observability & Documentation

- Structured logging for Kernel operations
- OpenAPI spec for the API
- Integration tests with real framework DI containers

## Summary

| Phase | Status | Theme |
|-------|--------|-------|
| 1. Stabilization | ✅ Complete | Make it reliable |
| 2. Security | ✅ Core done | Make it safe |
| 3. Performance | ✅ Core done | Make it fast |
| 4. Architecture | 🔄 In progress | Make it maintainable |
| 5. Ecosystem | 🔄 In progress | Make it universal |
| 6. Observability | Planned | Make it understandable |
