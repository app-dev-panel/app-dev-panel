# Collectors

## Kernel Collectors (Framework-Agnostic)

Provided by `app-dev-panel/kernel`, reused by all adapters. The Symfony adapter feeds them via `HttpSubscriber`, `ConsoleSubscriber`, and proxy decoration.

### RequestCollector
- **ID**: `AppDevPanel\Kernel\Collector\Web\RequestCollector`
- **Fed by**: `HttpSubscriber` converts Symfony HttpFoundation → PSR-7, calls `collectRequest()` / `collectResponse()`
- **Data**: `{requestUrl, requestPath, requestQuery, requestMethod, requestIsAjax, userIp, responseStatusCode, requestHeaders, responseHeaders, requestContent, responseContent}`

### LogCollector
- **ID**: `AppDevPanel\Kernel\Collector\LogCollector`
- **Proxy**: `LoggerInterfaceProxy` decorates `logger` or `Psr\Log\LoggerInterface` service
- **Data**: `{level, message, context, timestamp}`

### EventCollector
- **ID**: `AppDevPanel\Kernel\Collector\EventCollector`
- **Proxy**: `SymfonyEventDispatcherProxy` decorates `event_dispatcher` service (implements `Symfony\Component\EventDispatcher\EventDispatcherInterface`)
- **Data**: `{name, event, file, line, time}`
- **Note**: `console.command` event is dispatched before `Debugger::startup()`, so it's not captured. `console.terminate` and all events during command execution are captured.

### ExceptionCollector
- **ID**: `AppDevPanel\Kernel\Collector\ExceptionCollector`
- **Fed by**: `HttpSubscriber::onKernelException()` and `ConsoleSubscriber::onConsoleError()`
- **Data**: `{class, message, code, file, line, trace, previous[]}`

### HttpClientCollector
- **ID**: `AppDevPanel\Kernel\Collector\HttpClientCollector`
- **Proxy**: `HttpClientInterfaceProxy` decorates `Psr\Http\Client\ClientInterface`
- **Data**: `{method, uri, statusCode, headers, duration, requestId}`

### ServiceCollector
- **ID**: `AppDevPanel\Kernel\Collector\ServiceCollector`
- **Data**: `{service, method, arguments, result, error, duration}`

### VarDumperCollector
- **ID**: `AppDevPanel\Kernel\Collector\VarDumperCollector`
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
- **Fed by**: `ConsoleSubscriber` on `console.command`, `console.error`, `console.terminate`
- **Data**: keyed by event class, e.g. `{ConsoleCommandEvent: {name, command, input, arguments, options}, ConsoleTerminateEvent: {exitCode}}`

### WebAppInfoCollector
- **ID**: `AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector`
- **Fed by**: `HttpSubscriber` calls `markApplicationStarted()`, `markRequestStarted()`, `markRequestFinished()`, `markApplicationFinished()`
- **Data**: `{phpVersion, framework, environment, startTime, memoryUsage}`

### ConsoleAppInfoCollector
- **ID**: `AppDevPanel\Kernel\Collector\Console\ConsoleAppInfoCollector`
- **Fed by**: `ConsoleSubscriber` on all console events
- **Data**: `{phpVersion, framework, startTime, memoryUsage}`

## Symfony-Specific Collectors

### DatabaseCollector (Doctrine)
- **ID**: `AppDevPanel\Kernel\Collector\DatabaseCollector`
- **Depends on**: `TimelineCollector`
- **Fed by**: Doctrine DBAL middleware or SQL logger calling `logQuery()`
- **Data**:
  ```
  {
    queries: [{sql, rawSql, params, line, status, actions: [{action, time}], rowsNumber}],
    transactions: [{id, position, status, line, level, actions}]
  }
  ```
- **Summary**: `{db: {queries: {error, total}, transactions: {error, total}}}`

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
- **ID**: `AppDevPanel\Kernel\Collector\MailerCollector`
- **Depends on**: `TimelineCollector`
- **Fed by**: Mailer MessageEvent listener normalizes Symfony Email objects and calls `collectMessage()`
- **Data**: `{messages: [{from, to, cc, bcc, replyTo, subject, textBody, htmlBody, raw, charset, date}]}`
- **Summary**: `{mailer: {total}}`

### MessengerCollector
- **ID**: `AppDevPanel\Adapter\Symfony\Collector\MessengerCollector`
- **Depends on**: `TimelineCollector`
- **Fed by**: Messenger middleware calling `logMessage()`
- **Data**: `{messages: [{messageClass, bus, transport, dispatched, handled, failed, duration}], messageCount, failedCount}`
- **Summary**: `{messenger: {messageCount, failedCount}}`
