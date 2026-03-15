# Feature Roadmap

## Current State

ADP Kernel provides 13 collectors. The frontend has panels for 3 additional external collectors
(Database, Middleware, Mailer) that live in Yii-specific packages. Dark mode follows system
preference automatically. SSE provides real-time updates with 1-second polling.

**Architecture debt**: Kernel has 8 files importing `Yiisoft\Yii\Http\Event\*` and
`Yiisoft\Yii\Console\Event\*`. API has 15+ Yii dependencies and one reversed dependency
on Adapter. See `docs/architectural-constraints.md` for full violation list.
Decoupling Kernel from Yii events is a prerequisite for multi-framework support.

## Priority Matrix

| Priority | Feature | Effort | Rationale |
|----------|---------|--------|-----------|
| P0 | Database Query Collector (Kernel-native) | 7-8d | Frontend panel exists. External collector is Yii-only. Must-have for any debug panel |
| P0 | Mail Collector (Kernel-native) | 2d | Frontend panel exists. Quick win |
| P1 | Middleware Profiling Collector (Kernel-native) | 3d | Frontend panel exists. PSR-15 proxy-based |
| P1 | Exception Grouping + Error Tracking | 4d | Fingerprint-based grouping, occurrence count, trend |
| P1 | Dark Mode Manual Toggle | 1d | Auto-detection already works. Add settings toggle |
| P1 | Symfony Adapter | 7-10d | Strategic — expands addressable market |
| P1 | Laravel Adapter | 7-10d | Strategic — expands addressable market |
| P2 | Flame Graph / Call Tree Profiling | 10-12d | Span-based tracing or Xdebug integration |
| P2 | Request Diff / Comparison | 4-5d | Compare two debug entries side-by-side |
| P3 | Export / Share Debug Entry | 3-4d | JSON export/import for team collaboration |
| P3 | WebSocket (replace SSE) | 6d | SSE with 1s polling is acceptable. Low priority |

## P0: Database Query Collector

**Problem**: `DatabasePanel.tsx` renders SQL query data but relies on `Yiisoft\Db\Debug\DatabaseCollector`
from the `yiisoft/db` package. ADP Kernel has no framework-agnostic SQL collector.

**Solution**:
- Create `DatabaseQueryCollector` implementing `CollectorInterface`
- Create `PDOProxy` wrapping `\PDO` to intercept `query()`, `exec()`, `prepare()`+`execute()`
- Capture: SQL string, bound parameters, execution time, row count, caller (file:line)
- Detect N+1 queries (same SQL pattern executed N times in one request)
- Optional: EXPLAIN plan for SELECT queries

**Architecture**:
```
PDOProxy → DatabaseQueryCollector → Storage
                                      ↓
                            DatabasePanel.tsx (existing)
```

**Files to create**:
- `libs/Kernel/src/Collector/DatabaseQueryCollector.php`
- `libs/Kernel/src/Proxy/PDOProxy.php`
- `libs/Kernel/src/Proxy/PDOStatementProxy.php`

## P0: Mail Collector

**Problem**: `MailerPanel.tsx` exists but relies on `Yiisoft\Mailer\Debug\MailerCollector`.

**Solution**:
- Create `MailCollector` implementing `CollectorInterface`
- Accept mail data via public `collect()` method
- Capture: from, to, cc, bcc, subject, body (text+html), headers, attachments metadata
- Adapter wires this into the framework's mailer (Symfony Mailer, SwiftMailer, etc.)

**Files to create**:
- `libs/Kernel/src/Collector/MailCollector.php`

## P1: Middleware Profiling Collector

**Problem**: `MiddlewarePanel.tsx` exists but relies on external Yii collector.

**Solution**:
- Create `MiddlewareCollector` implementing `CollectorInterface`
- Create `MiddlewareDispatcherProxy` wrapping PSR-15 `MiddlewareInterface`
- Capture: middleware class, execution time, request/response modifications

**Files to create**:
- `libs/Kernel/src/Collector/Web/MiddlewareCollector.php`

## P1: Exception Grouping

**Enhancement to existing `ExceptionCollector`**:
- Add fingerprint computation: `md5(class + message_pattern + top_3_frames)`
- Group identical exceptions by fingerprint
- Track occurrence count, first/last seen timestamps
- Frontend: grouped view with expand/collapse

## P1: Dark Mode Toggle

**Enhancement to existing `DefaultThemeProvider`**:
- Add `themeMode` setting to `ApplicationSlice` (values: `system`, `light`, `dark`)
- Update `DefaultThemeProvider` to read from Redux state
- Add toggle in `SettingsDialog`

**Current state**: `useMediaQuery('(prefers-color-scheme: dark)')` already switches theme.
Need: manual override stored in localStorage via Redux Persist.

## P1: Symfony / Laravel Adapters

Both follow the Yii adapter pattern:

1. **DI integration**: Register Kernel proxies as service decorators
2. **Event mapping**: Map framework events to `Debugger::startup()`/`shutdown()`
3. **Config**: Provide sensible defaults
4. **Bootstrap**: Wire VarDumper handler early

**Symfony**: Bundle class, DI compiler passes, kernel event subscribers.
**Laravel**: ServiceProvider, middleware registration, event listeners.

## Effort Summary

| Category | Features | Total Effort |
|----------|----------|-------------|
| P0 (must-have) | DB Collector, Mail Collector | ~10d |
| P1 (high value) | Middleware, Exceptions, Dark Toggle, Adapters | ~25d |
| P2 (nice-to-have) | Flame Graph, Request Diff | ~15d |
| P3 (low priority) | Export, WebSocket | ~10d |
| **Total** | | **~60d** |
