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

Captures SQL queries executed through Yii 2's database layer.

**Fed by**: `yii\db\Command::EVENT_AFTER_EXECUTE` event hook

**Data schema**:
```json
{
    "queries": [
        {
            "sql": "SELECT * FROM user WHERE id = 1",
            "params": [],
            "rowCount": 1,
            "time": 0.003,
            "backtrace": "/app/controllers/UserController.php:42"
        }
    ],
    "queryCount": 15,
    "connectionCount": 1,
    "totalTime": 0.045
}
```

**Summary**: `{db: {queryCount: 15, totalTime: 45.0}}`

### Yii2LogCollector

Captures Yii 2's native log messages (from `Yii::getLogger()`).

Yii 2 buffers all log messages in memory. This collector reads them at shutdown, capturing the complete log output including messages that PSR-3 LogCollector wouldn't see.

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
