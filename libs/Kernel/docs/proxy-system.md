# Proxy System

Proxies implement the same PSR interface as the original service, intercept all calls,
feed call data to collectors, then delegate to the original service. Application code
is unaware of the interception.

## Architecture

```
Application Code -> PSR Interface -> Proxy -> Original Service
                                      |
                                      +-> Collector (records the call)
```

## Base Components (`Proxy/`)

### `AbstractObjectProxy`

Base class for all object proxies. Wraps an `object $instance`.

- `__call()` -- delegates to instance, calls `afterCall()` hook with timing
- `__get()`, `__set()`, `__isset()` -- delegate to instance
- `getInstance()` -- returns wrapped instance
- `afterCall(string $methodName, array $arguments, mixed $result, float $timeStart)` -- override in subclasses to record data
- `getNewStaticInstance(object $instance)` -- factory for cloning proxy with different instance

### `ProxyFactory`

Generates dynamic proxy classes that extend a proxy class AND implement a target interface.

```php
$factory = new ProxyFactory();
$proxy = $factory->createObjectProxy(
    SomeInterface::class,       // interface to implement
    ServiceProxy::class,        // proxy class to extend
    [$service, $instance, $config], // constructor args
);
```

How it works:
1. Resolves interface(s) from the given class/interface name
2. Generates PHP code for method stubs that delegate to `__call()`
3. Evaluates the code via `eval()`, creating a class that extends the proxy AND implements the interface
4. Caches generated classes in memory (keyed by interface+proxy class)

Handles: union types, nullable types, variadic params, default values, void return types, by-reference params.

### `ErrorHandlingTrait`

Tracks the current error during proxy operations.

- `getCurrentError(): ?Throwable`
- `hasCurrentError(): bool`
- `resetCurrentError(): void`
- `repeatError(Throwable): never` -- stores error and rethrows

## PSR Interface Proxies

| Proxy | PSR | Feeds | Location |
|-------|-----|-------|----------|
| `LoggerInterfaceProxy` | PSR-3 | `LogCollector` | `Collector/` |
| `EventDispatcherInterfaceProxy` | PSR-14 | `EventCollector` | `Collector/` |
| `HttpClientInterfaceProxy` | PSR-18 | `HttpClientCollector` | `Collector/` |
| `ContainerInterfaceProxy` | PSR-11 | `ServiceCollector` | `Collector/` |
| `VarDumperHandlerInterfaceProxy` | N/A | `VarDumperCollector` | `Collector/` |

`VarDumperHandlerInterfaceProxy` wraps `DumpHandlerInterface` (defined in Kernel, replaces `Yiisoft\VarDumper\HandlerInterface`).

## Service Proxies

### `ServiceProxy`

Extends `AbstractObjectProxy`. Uses `ProxyLogTrait` and `ErrorHandlingTrait`. Generic proxy for any service -- records all method calls via `afterCall()`.

### `ServiceMethodProxy`

Extends `ServiceProxy`. Accepts a map of `method => callable` callbacks. Invokes the callback after each tracked method call instead of generic logging.

### `ContainerInterfaceProxy`

Implements `ContainerInterface` directly. Uses `ProxyFactory` to create `ServiceProxy` or `ServiceMethodProxy` instances for services retrieved via `get()`. Supports multiple decoration strategies:

- Callable config: `$callback($container, $instance)`
- Array config with string keys: method-specific callbacks via `ServiceMethodProxy`
- Array config: custom proxy class instantiation
- Default: `ServiceProxy` via `ProxyFactory` for interface-typed services

## Configuration

Proxy logging levels on `ContainerInterfaceProxy`:

```php
ContainerInterfaceProxy::LOG_NOTHING    // 0
ContainerInterfaceProxy::LOG_ARGUMENTS  // 1
ContainerInterfaceProxy::LOG_RESULT     // 2
ContainerInterfaceProxy::LOG_ERROR      // 4
```

## Creating a Proxy for a New Framework

1. Register proxy classes as service decorators in the framework's DI container
2. Ensure proxies are injected in place of the original services
3. The proxy receives the original service via constructor injection

The Kernel provides all proxy implementations. The adapter only needs to wire them
into the DI container correctly.
