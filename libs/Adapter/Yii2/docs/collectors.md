# Collectors

All collectors available in the Yii 2 adapter.

## Kernel Collectors (Framework-Agnostic)

These collectors come from `app-dev-panel/kernel` and work identically across all adapters.

| Collector | ID | Data Schema |
|---|---|---|
| `RequestCollector` | `request` | `{request: {method, uri, headers, ...}, response: {status, headers, ...}}` |
| `WebAppInfoCollector` | `web-app-info` | `{startTime, requestStartTime, requestEndTime, endTime}` |
| `LogCollector` | `log` | `{messages: [{level, message, context, time}]}` |
| `EventCollector` | `event` | `{events: [{name, class, caller, time}]}` |
| `ExceptionCollector` | `exception` | `{exception: {class, message, file, line, trace}}` |
| `HttpClientCollector` | `http-client` | `{requests: [{method, uri, status, time}]}` |
| `ServiceCollector` | `service` | `{calls: [{service, method, args, result, time}]}` |
| `VarDumperCollector` | `var-dumper` | `{dumps: [{value, file, line}]}` |
| `TimelineCollector` | `timeline` | `{events: [{category, name, data, time}]}` |
| `FilesystemStreamCollector` | `filesystem-stream` | `{operations: [{type, path, bytes}]}` |
| `HttpStreamCollector` | `http-stream` | `{operations: [{type, url, bytes}]}` |
| `CommandCollector` | `command` | `{commands: [{name, input, exitCode}]}` |
| `ConsoleAppInfoCollector` | `console-app-info` | `{startTime, endTime}` |

## Yii 2-Specific Collectors

### DbCollector

Captures SQL queries executed through Yii 2's database layer with accurate timing.

**Fed by**: Paired `yii\db\Command::EVENT_BEFORE_EXECUTE` / `EVENT_AFTER_EXECUTE` event hooks

**Data schema**:
```json
{
    "queries": [
        {
            "sql": "SELECT * FROM user WHERE id = 1",
            "params": [],
            "rowCount": 1,
            "time": 0.003,
            "type": "SELECT",
            "backtrace": "/app/controllers/UserController.php:42"
        }
    ],
    "queryCount": 15,
    "connectionCount": 1,
    "totalTime": 0.045
}
```

**SQL types detected**: SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, DROP, TRUNCATE, TRANSACTION, COMMIT, ROLLBACK, SHOW, EXPLAIN, OTHER

**Summary**: `{db: {queryCount: 15, totalTime: 45.0}}`

### DebugLogTarget

Real-time log target that feeds Yii 2 log messages to the Kernel's `LogCollector`.

Unlike `Yii2LogCollector` which reads logs at shutdown (and misses early-flushed messages), `DebugLogTarget` is registered as a Yii log target and captures messages in real-time as they are flushed by the logger.

**Fed by**: `yii\log\Target::export()` — called automatically by Yii's logger

**Level mapping**:

| Yii Level | PSR-3 Level |
|---|---|
| `Logger::LEVEL_ERROR` | `error` |
| `Logger::LEVEL_WARNING` | `warning` |
| `Logger::LEVEL_INFO` | `info` |
| `Logger::LEVEL_TRACE` | `debug` |
| `Logger::LEVEL_PROFILE*` | `debug` |

**Integration**: Messages flow into `LogCollector` → `TimelineCollector`, visible alongside PSR-3 logs.

### MailerCollector

Captures mail messages sent via Yii 2's mailer component.

**Fed by**: `yii\mail\BaseMailer::EVENT_AFTER_SEND`

**Data schema**:
```json
{
    "messages": [
        {
            "from": ["sender@example.com"],
            "to": ["recipient@example.com"],
            "cc": [],
            "bcc": [],
            "subject": "Welcome Email",
            "isSuccessful": true
        }
    ],
    "messageCount": 3
}
```

**Summary**: `{mailer: {messageCount: 3}}`

### AssetBundleCollector

Captures registered asset bundles from Yii 2's View component (web requests only).

**Fed by**: `yii\web\View::EVENT_END_PAGE`

**Data schema**:
```json
{
    "bundles": {
        "yii\\web\\JqueryAsset": {
            "class": "yii\\web\\JqueryAsset",
            "sourcePath": "@bower/jquery/dist",
            "basePath": "/var/www/assets/abc123",
            "baseUrl": "/assets/abc123",
            "css": [],
            "js": ["jquery.js"],
            "depends": [],
            "options": {}
        }
    },
    "bundleCount": 5
}
```

**Summary**: `{assets: {bundleCount: 5}}`

### Yii2LogCollector (Legacy)

Captures Yii 2's native log messages from `Yii::getLogger()` at shutdown time.

**Note**: Prefer `DebugLogTarget` for real-time capture. This collector is kept for backward compatibility and captures profiling messages that `DebugLogTarget` filters out.

**Fed by**: `Yii::getLogger()->messages` at shutdown

**Data schema**:
```json
{
    "messages": [
        {
            "level": "info",
            "message": "Application request processed",
            "category": "application",
            "timestamp": 1710000000.123
        }
    ],
    "count": 42
}
```

**Log levels**: `error`, `warning`, `info`, `trace`, `profile`, `profile_begin`, `profile_end`
