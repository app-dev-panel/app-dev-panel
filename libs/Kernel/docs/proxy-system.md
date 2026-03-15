# Proxy System

The proxy system is the key mechanism that makes ADP non-intrusive. Proxies implement the same
PSR interface as the original service, so the application code is completely unaware of the interception.

## How It Works

```
Application Code → PSR Interface → Proxy → Original Service
                                     │
                                     └──▶ Collector (records the call)
```

A proxy wraps an existing service, delegates all calls to it, and simultaneously feeds
the call data (arguments, return values, timing) to a collector.

## Available Proxies

### LoggerInterfaceProxy (PSR-3)

Wraps `Psr\Log\LoggerInterface`. Intercepts all log calls.

```php
// Application calls this normally:
$logger->info('User logged in', ['user_id' => 42]);

// Proxy records: level=info, message="User logged in", context=[user_id=>42], timestamp
// Then delegates to the real logger
```

**Feeds**: `LogCollector`

### EventDispatcherInterfaceProxy (PSR-14)

Wraps `Psr\EventDispatcher\EventDispatcherInterface`. Intercepts all event dispatches.

```php
$dispatcher->dispatch(new UserCreatedEvent($user));
// Proxy records the event object and listener chain
```

**Feeds**: `EventCollector`

### HttpClientInterfaceProxy (PSR-18)

Wraps `Psr\Http\Client\ClientInterface`. Intercepts outgoing HTTP requests.

```php
$response = $httpClient->sendRequest($request);
// Proxy records request, response, and timing
```

**Feeds**: `HttpClientCollector`

### ContainerInterfaceProxy (PSR-11)

Wraps `Psr\Container\ContainerInterface`. Intercepts service resolution.

```php
$service = $container->get(MyService::class);
// Proxy records which service was resolved
```

**Feeds**: `ServiceCollector`

### VarDumperHandlerInterfaceProxy

Wraps the VarDumper handler. Intercepts `dump()` calls.

**Feeds**: `VarDumperCollector`

### ServiceProxy / ServiceMethodProxy

Generic proxy for any service. Configured via the adapter to intercept specific
service methods:

```php
// Configuration: track specific methods on a service
'trackedServices' => [
    MyService::class => ['methodA', 'methodB'],
],
```

This records arguments, return values, exceptions, and execution time for
the specified methods.

## Configuration

Proxy logging levels can be configured:

```php
'logLevel' => [
    'arguments' => true,   // Log method arguments
    'result' => true,      // Log return values
    'error' => true,       // Log exceptions
],
```

## Creating a Proxy for a New Framework

When creating an adapter for a new framework, you need to:

1. Register proxy classes as service decorators in the framework's DI container
2. Ensure proxies are injected *in place of* the original services
3. The proxy receives the original service via constructor injection

The Kernel provides all proxy implementations. The adapter only needs to wire them
into the DI container correctly.
