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

### DatabaseCollector (Kernel)

SQL queries via `DbProfilingTarget` feeding Kernel's `DatabaseCollector::logQuery()`.

**Fed by**: `DbProfilingTarget` (Yii log target intercepting DB profiling messages)

**Data schema**:
```json
{
    "queries": [
        {
            "sql": "SELECT * FROM user WHERE id = ?",
            "rawSql": "SELECT * FROM user WHERE id = 1",
            "params": [1],
            "line": "/app/controllers/UserController.php:42",
            "status": "success",
            "actions": [
                {"action": "query.start", "time": 1710000000.123},
                {"action": "query.end", "time": 1710000000.126}
            ],
            "rowsNumber": 1
        }
    ],
    "transactions": []
}
```

**Summary**: `{db: {queries: {error, total}, transactions: {error, total}}}`

## Yii 2-Specific Helpers

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

### MailerCollector (Kernel)

Mail messages via Kernel's `MailerCollector::collectMessage()`.

**Fed by**: `yii\mail\BaseMailer::EVENT_AFTER_SEND` → normalized in `Module::registerMailerProfiling()`

**Data schema**:
```json
{
    "messages": [
        {
            "from": {"sender@example.com": "Sender Name"},
            "to": {"recipient@example.com": "Recipient"},
            "cc": {},
            "bcc": {},
            "replyTo": {},
            "subject": "Welcome Email",
            "textBody": "...",
            "htmlBody": "...",
            "raw": "",
            "charset": "UTF-8",
            "date": ""
        }
    ]
}
```

**Summary**: `{mailer: {total: 3}}`

### AssetBundleCollector (Kernel)

Asset bundles via Kernel's `AssetBundleCollector::collectBundles()`.

**Fed by**: `yii\web\View::EVENT_END_PAGE` → normalized in `Module::registerAssetProfiling()`

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
    }
}
```

**Summary**: `{assets: {bundleCount: 5}}`

