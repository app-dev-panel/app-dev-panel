# Collectors

Collectors are the primary data-gathering mechanism in ADP. Each collector is responsible for
capturing a specific type of runtime data during application execution.

## Collector Interface

Every collector implements `CollectorInterface`:

```php
interface CollectorInterface
{
    public function startup(): void;           // Called when debugger starts
    public function shutdown(): void;          // Called when debugger stops
    public function getCollected(): array;     // Return collected data
}
```

## Available Collectors

### Core Collectors (Framework-Independent)

#### LogCollector
- **Collects**: All PSR-3 log entries
- **Data**: Log level, message, context, timestamp
- **Fed by**: `LoggerInterfaceProxy`

#### EventCollector
- **Collects**: All PSR-14 dispatched events
- **Data**: Event class name, event object data, listener information
- **Fed by**: `EventDispatcherInterfaceProxy`

#### ServiceCollector
- **Collects**: DI container service resolutions
- **Data**: Service ID/class, resolution time, arguments, results
- **Fed by**: Adapter-provided container proxy (e.g., `ContainerInterfaceProxy` and `ServiceProxy` in Yii adapter)

#### ExceptionCollector
- **Collects**: Uncaught exceptions and errors
- **Data**: Exception class, message, stack trace, file, line
- **API**: `collect(Throwable $throwable)` — framework-agnostic, accepts any `Throwable`
- **Fed by**: Adapter exception handler or direct call

#### HttpClientCollector
- **Collects**: Outgoing HTTP requests made via PSR-18 client
- **Data**: Request method, URL, headers, body, response status, response body, timing
- **Fed by**: `HttpClientInterfaceProxy`

#### VarDumperCollector
- **Collects**: Manual `dump()` / `dd()` calls
- **Data**: Dumped variable, call site (file, line), timestamp
- **Fed by**: Adapter-provided VarDumper handler proxy (e.g., `VarDumperHandlerInterfaceProxy` in Yii adapter)

#### TimelineCollector
- **Collects**: Timing data for profiling
- **Data**: Event name, start time, duration, category

#### FilesystemStreamCollector
- **Collects**: Filesystem stream operations (fopen, fread, fwrite, etc.)
- **Data**: Operation type, path, bytes read/written

#### HttpStreamCollector
- **Collects**: HTTP stream wrapper operations
- **Data**: URL, method, response data

### Web-Specific Collectors

#### RequestCollector
- **Collects**: HTTP request and response data
- **Data**: Method, URL, headers, body, query params, status code, response headers, response body
- **API**: `collectRequest(ServerRequestInterface $request)`, `collectResponse(ResponseInterface $response)` — framework-agnostic PSR-7 methods

#### WebAppInfoCollector
- **Collects**: Web application metadata
- **Data**: PHP version, memory usage, execution time, framework version
- **API**: `markApplicationStarted()`, `markRequestStarted()`, `markRequestFinished()`, `markApplicationFinished()`

### Console-Specific Collectors

#### CommandCollector
- **Collects**: Console command execution data
- **Data**: Command name, arguments, options, exit code, output
- **API**: `collect(ConsoleEvent|ConsoleErrorEvent|ConsoleTerminateEvent $event)` — accepts Symfony console events
- **Note**: Uses `method_exists($output, 'fetch')` instead of `instanceof ConsoleBufferedOutput` for framework independence

#### ConsoleAppInfoCollector
- **Collects**: Console application metadata
- **Data**: PHP version, memory usage, execution time
- **API**: `markApplicationStarted()`, `markApplicationFinished()`, `collect(object $event)` — accepts Symfony console events

## Creating a Custom Collector

```php
<?php

declare(strict_types=1);

namespace MyApp\Debug;

use AppDevPanel\Kernel\Collector\CollectorInterface;

final class MyCustomCollector implements CollectorInterface
{
    private array $data = [];

    public function startup(): void
    {
        $this->data = [];
    }

    public function shutdown(): void
    {
        // Finalize data if needed
    }

    public function getCollected(): array
    {
        return $this->data;
    }

    // Public method called by a proxy or directly
    public function collect(string $key, mixed $value): void
    {
        $this->data[] = [
            'key' => $key,
            'value' => $value,
            'time' => microtime(true),
        ];
    }
}
```

Register it in the adapter configuration to make it active.
