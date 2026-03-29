# PHP 8.4+ Best Practices for Symfony Integration

## Code Standards

1. **`declare(strict_types=1)`** — always
2. **`final` classes** — all new classes unless designed for extension
3. **`readonly` properties** — for immutable state
4. **Property hooks** (PHP 8.4) — for computed/validated properties
5. **Named arguments** — for Symfony APIs with many optional params
6. **Enums** — instead of class constants for finite value sets
7. **Union/intersection types** — full type declarations, no `mixed`
8. **`#[Override]`** attribute — on all methods overriding parent/interface
9. **First-class callables** — `$this->method(...)` over `[$this, 'method']`
10. **`array_find()`**, **`array_any()`**, **`array_all()`** (PHP 8.4)

## Architecture Patterns

1. **Wrap, don't extend** — composition over extending Symfony base classes
2. **PSR interfaces** — depend on PSR-3/7/14/18, not Symfony interfaces (except EventDispatcher — see below)
3. **Immutable DTOs** — `readonly class` for data transfer
4. **No static service locator** — inject via constructor
5. **Compiler passes for decoration** — `setDecoratedService()` pattern
6. **Tagged services** — for collecting multiple implementations
7. **Configuration tree** — for bundle config with safe defaults
8. **No `@` error suppression**
9. **No `extract()`/`compact()`**

## Bundle Development Patterns

### Extension Registration

```php
final class AppDevPanelExtension extends Extension
{
    #[Override]
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        if (!$config['enabled']) {
            return;  // Bundle disabled — register nothing
        }

        // Register services based on processed config
        $this->registerCoreServices($container, $config);
        $this->registerCollectors($container, $config['collectors']);
        $this->registerApiServices($container, $config);
    }
}
```

### CompilerPass Pattern

```php
final class CollectorProxyCompilerPass implements CompilerPassInterface
{
    #[Override]
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(Debugger::class)) {
            return;  // Bundle not loaded
        }

        // Collect all tagged collectors
        $collectors = $container->findTaggedServiceIds('app_dev_panel.collector');
        $debuggerDef = $container->getDefinition(Debugger::class);

        foreach ($collectors as $id => $tags) {
            $debuggerDef->addMethodCall('addCollector', [new Reference($id)]);
        }

        // Decorate logger (check existence first!)
        $this->decorateLogger($container);
        $this->decorateEventDispatcher($container);
        $this->decorateHttpClient($container);
    }

    private function decorateLogger(ContainerBuilder $container): void
    {
        // Symfony registers logger as 'logger' service ID
        // Some apps also register via FQCN
        $loggerId = $container->hasDefinition('logger')
            ? 'logger'
            : (LoggerInterface::class);

        if (!$container->hasDefinition($loggerId) && !$container->hasAlias($loggerId)) {
            return;  // No logger to decorate
        }

        $container->register('app_dev_panel.logger_proxy', LoggerInterfaceProxy::class)
            ->setDecoratedService($loggerId)
            ->setArguments([
                new Reference('app_dev_panel.logger_proxy.inner'),
                new Reference(LogCollector::class),
            ]);
    }
}
```

### EventSubscriber Pattern

```php
final class HttpSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Debugger $debugger,
        private readonly RequestCollector $requestCollector,
        private readonly ExceptionCollector $exceptionCollector,
    ) {}

    #[Override]
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 1024],     // First
            KernelEvents::RESPONSE => ['onResponse', -1024],  // Late
            KernelEvents::EXCEPTION => ['onException', 0],
            KernelEvents::TERMINATE => ['onTerminate', -2048], // Last
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;  // Skip sub-requests
        }
        // Convert HttpFoundation Request → PSR-7
        // Start debugger
    }
}
```

## Common Pitfalls

| Pitfall | Why | Fix |
|---------|-----|-----|
| Decorating non-existent service | `setDecoratedService()` on missing service = silent no-op | Check `hasDefinition()` first |
| Wrong CompilerPass phase | Service may not exist yet in early phases | Use `TYPE_BEFORE_OPTIMIZATION` (default) |
| Sub-request events | `kernel.request` fires for ESI/sub-requests too | Check `$event->isMainRequest()` |
| Event priority collision | Two subscribers at same priority = undefined order | Use explicit priorities, document them |
| Serialization in collectors | `Data` objects from `VarCloner` are heavy | Use Kernel's Dumper instead |
| Container not compiled | Calling `get()` on `ContainerBuilder` before compile | Only use `ContainerBuilder` in passes |
| Private service access | Symfony 6+ makes services private by default | Use aliases or make public explicitly |
| Config cache | `cache:clear` needed after config changes | In dev, container auto-recompiles |
| Autowiring conflicts | Multiple implementations of same interface | Use explicit `bind()` or aliases |
| Logger decoration loop | Logger used in LogCollector → infinite recursion | Proxy must not log its own operations |
| Event dispatcher decoration | Must implement Symfony interface, not PSR-14 | See "Why ADP uses Symfony interface" in internals |

## Symfony-Specific Event Dispatcher Proxy

The ADP event dispatcher proxy for Symfony MUST implement `Symfony\Component\EventDispatcher\EventDispatcherInterface`, not `Psr\EventDispatcher\EventDispatcherInterface`:

```php
final class SymfonyEventDispatcherProxy implements EventDispatcherInterface
{
    public function __construct(
        private readonly EventDispatcherInterface $dispatcher,
        private readonly EventCollector $collector,
    ) {}

    #[Override]
    public function dispatch(object $event, ?string $eventName = null): object
    {
        $this->collector->collect($event, $eventName);
        return $this->dispatcher->dispatch($event, $eventName);
    }

    // Must proxy ALL EventDispatcherInterface methods:
    // addListener, addSubscriber, removeListener, removeSubscriber,
    // getListeners, getListenerPriority, hasListeners
}
```

**Reasons:**
1. Symfony's `dispatch()` signature: `dispatch(object $event, ?string $eventName = null)` — PSR-14 has no `$eventName`
2. `SymfonyConfigProvider` calls `getListeners()` — not in PSR-14
3. Container passes `[$service, 'method']` arrays to `addListener()` — Symfony-specific

## PSR-7 Bridge

Symfony uses HttpFoundation, not PSR-7. Conversion required for ADP Kernel:

```php
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;

// HttpFoundation Request → PSR-7
$psr17Factory = new Psr17Factory();
$creator = new ServerRequestCreator($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
$psrRequest = $creator->fromGlobals();
// OR from existing Symfony Request:
$psrRequest = $creator->fromSymfonyRequest($symfonyRequest);  // Not standard — use bridge

// HttpFoundation Response → PSR-7
$psrResponse = $psr17Factory->createResponse($symfonyResponse->getStatusCode());
foreach ($symfonyResponse->headers->all() as $name => $values) {
    foreach ($values as $value) {
        $psrResponse = $psrResponse->withAddedHeader($name, $value);
    }
}
$psrResponse = $psrResponse->withBody(
    $psr17Factory->createStream($symfonyResponse->getContent())
);
```

## Testing Symfony Integration

- Test Extension: create `ContainerBuilder`, call `load()`, assert definitions exist
- Test CompilerPass: create `ContainerBuilder` with pre-registered services, call `process()`, verify decorations
- Test EventSubscriber: instantiate with mocks, call event handler methods directly
- Test Configuration: process config arrays, verify defaults and validation
- Never use Symfony Kernel in unit tests — mock container and services
- Use `$this->createMock(ContainerBuilder::class)` for compiler pass tests

## Before Implementing

1. Read the ADP Symfony adapter — `libs/Adapter/Symfony/src/`
2. Read the Kernel collector interfaces — `libs/Kernel/src/Collector/`
3. Read existing tests — `libs/Adapter/Symfony/tests/`
4. Check Symfony source for the exact hook/event you need

## After Implementing

1. Run `make test-php` — all tests pass
2. Run `make mago-fix` — formatting and lint clean
3. Test against Symfony playground: `make fixtures-symfony`
4. Verify no Symfony classes leak into Kernel or API modules
