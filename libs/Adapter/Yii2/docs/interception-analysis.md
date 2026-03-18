# Yii2 Adapter Interception Analysis

## Current State

| Area | Implementation | Quality |
|---|---|---|
| Request/Response | WebListener via `EVENT_BEFORE/AFTER_REQUEST` | OK |
| DB Queries | Paired `EVENT_BEFORE/AFTER_EXECUTE` with timing | **Implemented** — SQL type detection, params, backtrace |
| Logs | `DebugLogTarget` feeds `LogCollector` in real-time | **Implemented** — replaces batch-read approach |
| Exceptions | Error handler `.exception` property check | Works |
| Console | ConsoleListener via `EVENT_BEFORE/AFTER_REQUEST` | OK |
| Mail | `MailerCollector` via `BaseMailer::EVENT_AFTER_SEND` | **Implemented** |
| Assets | `AssetBundleCollector` via `View::EVENT_END_PAGE` | **Implemented** |

## Architecture Patterns

### What yii2-debug Does (Reference)

ADP Kernel patterns leveraged:

1. **LoggerInterfaceProxy** — PSR-3 decorator, captures caller file:line
2. **EventDispatcherInterfaceProxy** — PSR-14 decorator
3. **HttpClientInterfaceProxy** — PSR-18 decorator with timing
4. **FilesystemStreamCollector** — Stream wrapper hijacking
5. **TimelineCollector** — Central timeline event sourcing
6. **ExceptionCollector** — Exception chain serialization

### Yii2 Interception Strategy

Yii2 has its own systems that don't use PSR interfaces:

| Yii2 API | Interception | Status |
|---|---|---|
| `Yii::getLogger()` | `DebugLogTarget extends yii\log\Target` → `LogCollector` | Done |
| `yii\db\Command` | `EVENT_BEFORE/AFTER_EXECUTE` with `beginQuery()`/`logQuery()` | Done |
| `yii\mail\BaseMailer` | `EVENT_AFTER_SEND` → `MailerCollector` | Done |
| `yii\web\AssetBundle` | `View::EVENT_END_PAGE` → `AssetBundleCollector` | Done |
| `yii\base\Component::trigger()` | No single dispatch point; use behavior attachment | Future |

## Implementation Details

### 1. DebugLogTarget (Real-time log capture)

- `DebugLogTarget extends yii\log\Target` registered in `Yii::getLogger()->targets['adp-debug']`
- `exportInterval = 1` for immediate capture (no batching)
- Maps Yii levels to PSR-3: ERROR→error, WARNING→warning, INFO→info, TRACE→debug, PROFILE→debug
- Feeds `LogCollector::collect()` per message in `export()`

### 2. DbCollector (Timing + SQL analysis)

- `EVENT_BEFORE_EXECUTE` → `DbCollector::beginQuery()` starts `microtime(true)` timer
- `EVENT_AFTER_EXECUTE` → `DbCollector::logQuery()` stops timer, calculates duration
- Captures: SQL, params, row count, execution time, SQL type, caller backtrace
- SQL type detection: SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, DROP, TRUNCATE, TRANSACTION, COMMIT, ROLLBACK, SHOW, EXPLAIN

### 3. MailerCollector

- `BaseMailer::EVENT_AFTER_SEND` → `MailerCollector::logMessage()`
- Captures: from, to, cc, bcc, subject, isSuccessful
- Address normalization: handles string, array, and null inputs
- Summary: message count

### 4. AssetBundleCollector (Web only)

- `View::EVENT_END_PAGE` → reads `View::$assetBundles`
- Captures: bundle class, sourcePath, basePath, baseUrl, CSS files, JS files, dependencies, options
- Summary: bundle count

## Future Improvements

### Request/Response Enhancement
- Capture cookies, session data
- Capture route info (`Yii::$app->requestedRoute`, `Yii::$app->controller`)
- Capture user identity info if available

### Error Handler Integration
- Use `ErrorHandler::EVENT_SHUTDOWN` if available
- `register_shutdown_function` for fatal errors
