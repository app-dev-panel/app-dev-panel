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

## Proxies in Kernel

Kernel provides framework-independent PSR proxies, colocated with their collectors in `src/Collector/`:

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

## Proxies in Adapters

The following proxies are framework-specific and live in adapter packages:

### Yii Adapter (`libs/Adapter/Yiisoft/src/Proxy/`)

- **ContainerInterfaceProxy** (PSR-11) — Wraps `Psr\Container\ContainerInterface`, feeds `ServiceCollector`
- **ContainerProxyConfig** — Configuration for container proxy (tracked services, log levels)
- **ServiceProxy** / **ServiceMethodProxy** — Generic interception for any service method
- **VarDumperHandlerInterfaceProxy** — Wraps Yii VarDumper handler, feeds `VarDumperCollector`
- **ProxyLogTrait** — Shared logging logic for Yii-specific proxies

## Shared Infrastructure

### ProxyDecoratedCalls Trait

Located in `src/ProxyDecoratedCalls.php`. Provides `__call`, `__get`, `__set` delegation
to the decorated service. Used by both Kernel and adapter proxies.

## Creating a Proxy for a New Framework

When creating an adapter for a new framework, you need to:

1. Register Kernel's PSR proxies (Logger, EventDispatcher, HttpClient) as service decorators in the framework's DI container
2. Create framework-specific proxies as needed (e.g., container proxy, ORM proxy) in the adapter package
3. Ensure proxies are injected *in place of* the original services
4. The proxy receives the original service via constructor injection

Kernel provides the PSR proxies and shared traits. The adapter wires them into the DI container
and adds any framework-specific proxies.
