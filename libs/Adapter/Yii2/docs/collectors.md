# Collectors

## Kernel Collectors (Framework-Agnostic)

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

SQL queries via Yii 2 `yii\db\Command` events with accurate timing.

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

Yii log target feeding `LogCollector` in real-time as messages are flushed.

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

Mail messages via `yii\mail\BaseMailer` events.

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

Asset bundles from `yii\web\View` (web requests only).

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

