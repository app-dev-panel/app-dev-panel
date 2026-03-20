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
| `yii\db\Command` | `DbProfilingTarget` → Kernel `DatabaseCollector::logQuery()` | Done |
| `yii\mail\BaseMailer` | `EVENT_AFTER_SEND` → Kernel `MailerCollector::collectMessage()` | Done |
| `yii\web\AssetBundle` | `View::EVENT_END_PAGE` → Kernel `AssetBundleCollector::collectBundles()` | Done |
| `yii\base\Component::trigger()` | No single dispatch point; use behavior attachment | Future |

## Implementation Details

### 1. DebugLogTarget (Real-time log capture)

- `DebugLogTarget extends yii\log\Target` registered in `Yii::getLogger()->targets['adp-debug']`
- `exportInterval = 1` for immediate capture (no batching)
- Maps Yii levels to PSR-3: ERROR→error, WARNING→warning, INFO→info, TRACE→debug, PROFILE→debug
- Feeds `LogCollector::collect()` per message in `export()`

### 2. DatabaseCollector (Kernel, fed by DbProfilingTarget)

- `DbProfilingTarget` intercepts Yii log profiling messages for DB commands
- Tracks query start times internally, calls `DatabaseCollector::logQuery()` on profile end
- Captures: SQL, rawSql, params, line (backtrace), start/end times, row count
- Output format: `{queries: [...], transactions: []}` (Kernel standard schema)

### 3. MailerCollector (Kernel, fed by Module event hook)

- `BaseMailer::EVENT_AFTER_SEND` → Module normalizes Yii2 `MessageInterface` → `MailerCollector::collectMessage()`
- Captures: from, to, cc, bcc, replyTo, subject, textBody, htmlBody, raw, charset, date
- Summary: total message count

### 4. AssetBundleCollector (Kernel, fed by Module event hook)

- `View::EVENT_END_PAGE` → Module normalizes `View::$assetBundles` → `AssetBundleCollector::collectBundles()`
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
