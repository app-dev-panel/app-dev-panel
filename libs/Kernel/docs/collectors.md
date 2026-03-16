# Collectors

Collectors capture specific types of runtime data during application execution.

## Collector Interface

```php
interface CollectorInterface
{
    public function getName(): string;
    public function startup(): void;
    public function shutdown(): void;
    public function getCollected(): array;
}
```

`CollectorTrait` provides default `startup()`/`shutdown()` and `isActive()` tracking.

`SummaryCollectorInterface` adds `getSummary(): array` for lightweight entry metadata.

## Core Collectors

### LogCollector
- Fed by: `LoggerInterfaceProxy`
- Data: log level, message, context, timestamp

### EventCollector
- Fed by: `EventDispatcherInterfaceProxy`
- Data: event class name, event object, file, line, time
- Configurable `earlyAcceptedEvents` via `withEarlyAcceptedEvents(array)` -- collects specified event classes even before collector is active

### ServiceCollector
- Fed by: `ContainerInterfaceProxy` and `ServiceProxy`
- Data: service ID/class, resolution time, arguments, results

### ExceptionCollector
- Direct: `collectException(Throwable)`
- Legacy: `collect(object)` -- checks `getThrowable()`, `getError()`, or direct `Throwable`
- Data: exception class, message, file, line, code, stack trace
- Walks previous exceptions chain

### HttpClientCollector
- Fed by: `HttpClientInterfaceProxy`
- Data: request method, URL, headers, body, response status, body, timing

### VarDumperCollector
- Fed by: `VarDumperHandlerInterfaceProxy`
- Data: dumped variable, call site, timestamp

### TimelineCollector
- Timing data for profiling
- Other collectors call `$this->timelineCollector->collect($this, $id)` to record timeline entries

## Web-Specific Collectors

### RequestCollector
- Direct: `collectRequest(ServerRequestInterface)`, `collectResponse(ResponseInterface)`
- Legacy: `collect(object)` -- introspects `getRequest()`/`getResponse()` on event
- Data: URL, path, query, method, isAjax, userIp, statusCode, raw request/response strings (via `GuzzleHttp\Psr7\Message`)

### WebAppInfoCollector
- Direct: `collectTiming(string $eventType)` with constants:
  - `EVENT_APPLICATION_STARTUP`
  - `EVENT_BEFORE_REQUEST`
  - `EVENT_AFTER_REQUEST`
  - `EVENT_AFTER_EMIT`
- Legacy: `collect(object)` -- maps class name suffix to constant
- Data: applicationProcessingTime, requestProcessingTime, preloadTime, memoryPeakUsage, memoryUsage

## Console-Specific Collectors

### CommandCollector
- Input: Symfony Console events (`ConsoleEvent`, `ConsoleErrorEvent`, `ConsoleTerminateEvent`)
- Uses `Symfony\Component\Console\Output\BufferedOutput` and any output with `fetch()` method
- Data: command name, input, output, exit code, arguments, options, error message

### ConsoleAppInfoCollector
- Direct: `collectTiming(string $eventType)` with constants:
  - `EVENT_APPLICATION_STARTUP`
  - `EVENT_APPLICATION_SHUTDOWN`
- Legacy: `collect(object)` -- maps Symfony Console events and class name suffix
- Data: applicationProcessingTime, requestProcessingTime, preloadTime, memoryPeakUsage, memoryUsage

## Stream Collectors

### FilesystemStreamCollector / FilesystemStreamProxy
- Filesystem stream operations (fopen, fread, fwrite, etc.)

### HttpStreamCollector / HttpStreamProxy
- HTTP stream wrapper operations

## External Collectors

| Collector | Package | Data |
|-----------|---------|------|
| `Yiisoft\Db\Debug\DatabaseCollector` | `yiisoft/db` | SQL queries, bindings, execution time |
| `Yiisoft\Yii\Debug\Collector\Web\MiddlewareCollector` | `yiisoft/yii-debug` | PSR-15 middleware stack execution |
| `Yiisoft\Mailer\Debug\MailerCollector` | `yiisoft/mailer` | Sent emails |

The frontend has panels for these. Kernel-native implementations needed for framework-agnostic ADP.

## Creating a Custom Collector

```php
final class MyCollector implements CollectorInterface
{
    use CollectorTrait;

    private array $data = [];

    public function getCollected(): array
    {
        return $this->data;
    }

    public function collect(string $key, mixed $value): void
    {
        if (!$this->isActive()) {
            return;
        }
        $this->data[] = ['key' => $key, 'value' => $value, 'time' => microtime(true)];
    }

    private function reset(): void
    {
        $this->data = [];
    }
}
```

Register in the adapter's configuration.
