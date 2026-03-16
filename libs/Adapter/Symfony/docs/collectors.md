# Collectors

## Kernel Collectors (Framework-Agnostic)

These collectors are provided by `app-dev-panel/kernel` and reused by all adapters.

### LogCollector
- **ID**: `AppDevPanel\Kernel\Collector\LogCollector`
- **Proxy**: `LoggerInterfaceProxy` wraps `Psr\Log\LoggerInterface`
- **Data**: `{level, message, context, timestamp}`

### EventCollector
- **ID**: `AppDevPanel\Kernel\Collector\EventCollector`
- **Proxy**: `EventDispatcherInterfaceProxy` wraps `Psr\EventDispatcher\EventDispatcherInterface`
- **Data**: `{event, class, timestamp, backtrace}`

### ServiceCollector
- **ID**: `AppDevPanel\Kernel\Collector\ServiceCollector`
- **Proxy**: `ContainerInterfaceProxy` wraps `Psr\Container\ContainerInterface`
- **Data**: `{service, method, arguments, result, error, duration}`

### HttpClientCollector
- **ID**: `AppDevPanel\Kernel\Collector\HttpClientCollector`
- **Proxy**: `HttpClientInterfaceProxy` wraps `Psr\Http\Client\ClientInterface`
- **Data**: `{method, uri, statusCode, headers, duration, requestId}`

### ExceptionCollector
- **ID**: `AppDevPanel\Kernel\Collector\ExceptionCollector`
- **Data**: `{class, message, code, file, line, trace, previous[]}`

### VarDumperCollector
- **ID**: `AppDevPanel\Kernel\Collector\VarDumperCollector`
- **Proxy**: `VarDumperHandlerInterfaceProxy`
- **Data**: `{variable, file, line}`

### TimelineCollector
- **ID**: `AppDevPanel\Kernel\Collector\TimelineCollector`
- **Data**: `{collector, eventId, timestamp}` — cross-collector timeline

### FilesystemStreamCollector
- **ID**: `AppDevPanel\Kernel\Collector\Stream\FilesystemStreamCollector`
- **Data**: `{operation, path, args, duration}`

### HttpStreamCollector
- **ID**: `AppDevPanel\Kernel\Collector\Stream\HttpStreamCollector`
- **Data**: `{operation, uri, args, duration}`

### CommandCollector
- **ID**: `AppDevPanel\Kernel\Collector\Console\CommandCollector`
- **Data**: `{name, input, output, exitCode}`

### WebAppInfoCollector
- **ID**: `AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector`
- **Data**: `{phpVersion, framework, environment, startTime, memoryUsage}`

### ConsoleAppInfoCollector
- **ID**: `AppDevPanel\Kernel\Collector\Console\ConsoleAppInfoCollector`
- **Data**: `{phpVersion, framework, startTime, memoryUsage}`

## Symfony-Specific Collectors

### SymfonyRequestCollector
- **ID**: `AppDevPanel\Adapter\Symfony\Collector\SymfonyRequestCollector`
- **Depends on**: `TimelineCollector`
- **Fed by**: `HttpSubscriber` (kernel.request, kernel.response events)
- **Data**:
  ```
  {
    requestUrl, requestPath, requestQuery, requestMethod,
    requestIsAjax, userIp, responseStatusCode,
    routeName, controllerName,
    requestHeaders, responseHeaders,
    requestContent, responseContent
  }
  ```
- **Summary**: `{request: {url, path, query, method, isAjax, userIp, route, controller}, response: {statusCode}}`

### DoctrineCollector
- **ID**: `AppDevPanel\Adapter\Symfony\Collector\DoctrineCollector`
- **Depends on**: `TimelineCollector`
- **Fed by**: Doctrine DBAL middleware or SQL logger calling `logQuery()`
- **Data**:
  ```
  {
    queries: [{sql, params, types, executionTime, backtrace}],
    totalTime, queryCount
  }
  ```
- **Summary**: `{doctrine: {queryCount, totalTime}}`

### TwigCollector
- **ID**: `AppDevPanel\Adapter\Symfony\Collector\TwigCollector`
- **Depends on**: `TimelineCollector`
- **Fed by**: Twig profiler extension calling `logRender()`
- **Data**: `{renders: [{template, renderTime}], totalTime, renderCount}`
- **Summary**: `{twig: {renderCount, totalTime}}`

### SecurityCollector
- **ID**: `AppDevPanel\Adapter\Symfony\Collector\SecurityCollector`
- **Fed by**: Security event listener
- **Data**:
  ```
  {
    username, roles, firewallName, authenticated,
    accessDecisions: [{attribute, subject, result, voters}]
  }
  ```
- **Summary**: `{security: {username, authenticated, roles}}`

### CacheCollector
- **ID**: `AppDevPanel\Adapter\Symfony\Collector\CacheCollector`
- **Depends on**: `TimelineCollector`
- **Fed by**: Decorated cache adapter calling `logCacheOperation()`
- **Data**: `{operations: [{pool, operation, key, hit, duration}], hits, misses, totalOperations}`
- **Summary**: `{cache: {hits, misses, totalOperations}}`

### MailerCollector
- **ID**: `AppDevPanel\Adapter\Symfony\Collector\MailerCollector`
- **Fed by**: Mailer MessageEvent listener calling `logMessage()`
- **Data**: `{messages: [{from, to, subject, transport}], messageCount}`
- **Summary**: `{mailer: {messageCount}}`

### MessengerCollector
- **ID**: `AppDevPanel\Adapter\Symfony\Collector\MessengerCollector`
- **Depends on**: `TimelineCollector`
- **Fed by**: Messenger middleware calling `logMessage()`
- **Data**: `{messages: [{messageClass, bus, transport, dispatched, handled, failed, duration}], messageCount, failedCount}`
- **Summary**: `{messenger: {messageCount, failedCount}}`
