# Symfony Internals Deep Dive

## Application Lifecycle

```
public/index.php
  → Kernel::__construct(environment, debug)
  → Kernel::handle(Request)
    → boot()
      → initializeBundles()           # Load bundles from config/bundles.php
      → initializeContainer()
        → buildContainer()            # Compile DI container
          → Load Extension configs
          → Run CompilerPasses
          → Compile + dump to cache
        → Container::boot()
    → handleRaw(Request)
      → fireEvent(kernel.request)     # Routing, security, locale
      → controller = resolve(route)
      → fireEvent(kernel.controller)  # Controller arguments resolved
      → fireEvent(kernel.controller_arguments)
      → $response = controller()
      → fireEvent(kernel.response)    # Response processing
    → return Response
  → Response::send()
  → Kernel::terminate(Request, Response)
    → fireEvent(kernel.terminate)     # Cleanup, profiler data flush
```

## DI Container (Symfony\Component\DependencyInjection)

### Compilation Phases

```
ContainerBuilder
  1. Extension::load()              # Each bundle registers services
  2. CompilerPass::process()        # Modify/decorate/validate services
  3. compile()                      # Freeze container, resolve params
  4. dump()                         # Generate PHP class (cached)
```

**Key difference from Laravel:** Container is compiled once, dumped to PHP file, never modified at runtime. No `set()`/`bind()` after compilation.

### Service Registration

```php
// In Extension::load()
$container->register('app_dev_panel.storage', FileStorage::class)
    ->setArguments(['%app_dev_panel.storage.path%', '%app_dev_panel.storage.history_size%'])
    ->setPublic(false);

// Alias for interface
$container->setAlias(StorageInterface::class, 'app_dev_panel.storage');

// Autowired (default in Symfony 6+)
$container->register(Debugger::class)
    ->setAutowired(true)
    ->setAutoconfigured(true);
```

### Service Decoration

```php
// In CompilerPass or Extension
$container->register('app_dev_panel.logger_proxy', LoggerInterfaceProxy::class)
    ->setDecoratedService('logger')  // or LoggerInterface::class
    ->setArguments([
        new Reference('app_dev_panel.logger_proxy.inner'),  // original service
        new Reference(LogCollector::class),
    ]);
```

**How decoration works internally:**
1. `setDecoratedService('logger')` marks this service as a decorator
2. Compiler renames original `logger` → `app_dev_panel.logger_proxy.inner`
3. New service takes the `logger` ID
4. `.inner` reference points to original
5. Priority controls decoration order (higher = outermost wrapper)

**Gotcha:** Decoration happens at compile time. If the decorated service doesn't exist, compilation fails silently (service just won't be decorated). Use `$container->hasDefinition()` check.

### Tagged Services

```php
// Register with tag
$container->register(LogCollector::class)
    ->addTag('app_dev_panel.collector');

// In CompilerPass: collect all tagged services
$taggedServices = $container->findTaggedServiceIds('app_dev_panel.collector');
foreach ($taggedServices as $id => $tags) {
    $debuggerDef->addMethodCall('addCollector', [new Reference($id)]);
}
```

### Parameters

```php
// Set parameter
$container->setParameter('app_dev_panel.storage.path', '%kernel.project_dir%/var/debug');

// Parameters resolved at compile time
// %kernel.project_dir% → /var/www/app
// %env(APP_DEBUG)% → resolved from .env
```

**Gotcha:** After compilation, parameters are frozen. No runtime parameter changes.

### Container Internals

```
ContainerBuilder (compile-time only)
  $definitions    — array<string, Definition>     (service definitions)
  $aliasDefinitions — array<string, Alias>        (alias → service ID)
  $parameterBag   — ParameterBag                  (string parameters)
  $compiler       — Compiler                      (pass scheduler)
  $extensionConfigs — array<string, array[]>       (bundle configs)

Container (runtime, dumped PHP class)
  $services       — array<string, object>         (resolved singletons)
  $privates       — array<string, object>         (private services)
  $parameters     — array<string, mixed>          (frozen params)
  $aliases        — array<string, string>         (alias → service ID)
  $methodMap      — array<string, string>         (service → factory method name)
```

## Bundle System

### Bundle Lifecycle

```
Bundle::build(ContainerBuilder $container)     # Register compiler passes
  → getContainerExtension()                    # Returns Extension instance
    → Extension::load(array $configs, ContainerBuilder $container)
      → process config
      → register services
  ↓ (all bundles loaded)
CompilerPass::process(ContainerBuilder $container)
  ↓ (container compiled)
Bundle::boot()                                  # Runtime initialization
  ↓
Bundle::shutdown()                              # Cleanup
```

### Extension Configuration

```php
// Configuration.php — defines the config tree
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('app_dev_panel');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->booleanNode('enabled')->defaultTrue()->end()
                ->arrayNode('storage')
                    ->children()
                        ->scalarNode('path')->defaultValue('%kernel.project_dir%/var/debug')->end()
                        ->integerNode('history_size')->defaultValue(50)->end()
                    ->end()
                ->end()
                ->arrayNode('collectors')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('request')->defaultTrue()->end()
                        ->booleanNode('log')->defaultTrue()->end()
                        // ...
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
```

**Config loading:**
1. User writes `config/packages/app_dev_panel.yaml`
2. Symfony loads all YAML configs, merges per environment
3. `Extension::load()` receives merged config array
4. `Configuration` validates and normalizes it
5. Extension uses processed config to register services

**How users DON'T override your defaults:**
- `Configuration` tree defines `defaultValue()` for every node
- User config MERGES with defaults (not replaces)
- `addDefaultsIfNotSet()` ensures missing keys get defaults
- Environment-specific configs override base: `config/packages/dev/app_dev_panel.yaml`
- `canBeDisabled()` / `canBeEnabled()` — shorthand for enabled/disabled boolean

### Bundle Registration

```php
// config/bundles.php
return [
    AppDevPanel\Adapter\Symfony\AppDevPanelBundle::class => ['dev' => true, 'test' => true],
];
```

Environments: `all`, `dev`, `test`, `prod`. Bundle only loads in specified environments.

### CompilerPass Priorities

```php
class AppDevPanelBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(
            new CollectorProxyCompilerPass(),
            PassConfig::TYPE_BEFORE_OPTIMIZATION,  // Phase
            0  // Priority (higher = earlier)
        );
    }
}
```

**Pass phases (execution order):**
1. `TYPE_BEFORE_OPTIMIZATION` — most passes run here
2. `TYPE_OPTIMIZE` — Symfony core optimization
3. `TYPE_BEFORE_REMOVING` — last chance before unused services removed
4. `TYPE_REMOVE` — remove unused/private services
5. `TYPE_AFTER_REMOVING` — post-cleanup

**For ADP:** `TYPE_BEFORE_OPTIMIZATION` — we need to decorate services before Symfony optimizes them.

## Event System (EventDispatcher Component)

### Architecture

```
EventDispatcher
  $listeners    — array<string, array<int, array<Closure>>>
                   eventName → priority → [listeners]
  $sorted       — array<string, Closure[]>  (cached sorted listeners)
  $optimized    — ?EventDispatcher  (immutable compiled dispatcher)
```

### Dispatch Flow

```php
$dispatcher->dispatch(new RequestEvent($kernel, $request, $type));
// OR
$dispatcher->dispatch($event, 'kernel.request');  // second arg = event name
```

1. Get event name (class name or explicit string)
2. Call listeners sorted by priority (highest first)
3. Each listener receives the event object
4. If `$event->stopPropagation()` called → stop
5. Return the event object

### Listener Registration

```php
// Direct
$dispatcher->addListener('kernel.request', $callable, $priority = 0);

// Via subscriber (recommended)
class HttpSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 1024],
            KernelEvents::RESPONSE => ['onResponse', -1024],
            KernelEvents::EXCEPTION => ['onException', 0],
            KernelEvents::TERMINATE => ['onTerminate', -2048],
        ];
    }
}

// Via service tag (in DI)
$container->register(HttpSubscriber::class)
    ->addTag('kernel.event_subscriber');
```

### Priority Convention

| Priority | Usage |
|----------|-------|
| 2048+ | Security (firewall) |
| 1024 | ADP request capture (must run before controllers) |
| 256 | Routing |
| 0 | Default (most subscribers) |
| -256 | Response modification |
| -1024 | ADP response capture (must run after controllers) |
| -2048 | ADP terminate/flush (must run last) |

### Symfony Event Dispatcher vs PSR-14

| Feature | Symfony EventDispatcher | PSR-14 |
|---------|----------------------|--------|
| Priority | Yes (int) | No |
| Stop propagation | `$event->stopPropagation()` | `$event->isPropagationStopped()` |
| Event naming | Class name or string | Class name only |
| `dispatch()` signature | `dispatch(object $event, ?string $eventName)` | `dispatch(object $event)` |
| `getListeners()` | Yes | No (not in interface) |
| Subscriber interface | Yes (`EventSubscriberInterface`) | No |

**Why ADP uses Symfony interface, not PSR-14:**
- Symfony's `dispatch()` has `?string $eventName` second param — PSR-14 doesn't
- `SymfonyConfigProvider` needs `getListeners()` to inspect registered listeners
- Symfony container passes `[$service, 'method']` arrays as listeners — requires `callable|array` signature

## Kernel Events

| Event | Constant | When | Use |
|-------|----------|------|-----|
| `RequestEvent` | `kernel.request` | Request received | Routing, auth, CORS, ADP startup |
| `ControllerEvent` | `kernel.controller` | Controller resolved | Modify controller |
| `ControllerArgumentsEvent` | `kernel.controller_arguments` | Args resolved | Modify arguments |
| `ViewEvent` | `kernel.view` | Controller returned non-Response | Convert to Response |
| `ResponseEvent` | `kernel.response` | Response ready | Headers, cache, ADP capture |
| `FinishRequestEvent` | `kernel.finish_request` | Sub-request finished | Stack cleanup |
| `TerminateEvent` | `kernel.terminate` | After Response::send() | ADP flush, profiler |
| `ExceptionEvent` | `kernel.exception` | Exception thrown | Error handling, ADP capture |

### Console Events

| Event | Constant | When |
|-------|----------|------|
| `ConsoleCommandEvent` | `console.command` | Before command executes |
| `ConsoleErrorEvent` | `console.error` | Command threw exception |
| `ConsoleTerminateEvent` | `console.terminate` | After command finishes |

## Routing

### Route Loading

```
Kernel::boot()
  → RoutingExtension → load config/routes.yaml
    → imports config/routes/*.yaml
  → RouterListener (kernel.request, priority 32)
    → UrlMatcher::match($pathinfo)
      → compiled regex match against RouteCollection
      → return route params + _controller + _route
    → $request->attributes->set('_controller', ...)
```

### Route Registration for Bundles

```php
// In Extension or routes config
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes->add('adp_api', '/debug/api/{path}')
        ->controller([AdpApiController::class, 'handle'])
        ->requirements(['path' => '.+'])
        ->methods(['GET', 'POST', 'PUT', 'DELETE']);
};
```

### Route Introspection

```php
$router = $container->get('router');
$routeCollection = $router->getRouteCollection();

foreach ($routeCollection->all() as $name => $route) {
    $route->getPath();       // '/debug/api/{path}'
    $route->getMethods();    // ['GET', 'POST']
    $route->getDefaults();   // ['_controller' => '...']
    $route->getRequirements(); // ['path' => '.+']
}
```
