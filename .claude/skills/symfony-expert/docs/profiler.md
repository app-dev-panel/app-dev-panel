# Symfony Profiler & Web Debug Toolbar Internals

## Architecture Overview

```
symfony/web-profiler-bundle
  ├── WebProfilerBundle              # Bundle entry point
  ├── Controller/
  │   ├── ProfilerController         # Serves profiler UI (/_profiler/{token})
  │   └── RouterController           # Route debugging page
  ├── Twig/
  │   └── WebProfilerExtension       # Twig functions for toolbar rendering
  ├── EventListener/
  │   └── WebDebugToolbarListener    # Injects toolbar HTML into responses
  └── Resources/
      └── views/                     # Twig templates for profiler panels

symfony/http-kernel (Profiler component)
  ├── Profiler                       # Core: collects, stores, loads profiles
  ├── Profile                        # Container for one request's collected data
  ├── DataCollector/
  │   ├── DataCollectorInterface     # Contract for data collectors
  │   ├── DataCollector              # Base class with serialization
  │   ├── LateDataCollectorInterface # Collects data at terminate phase
  │   ├── RequestDataCollector       # Request/response/session/cookies
  │   ├── TimeDataCollector          # Request timing
  │   ├── MemoryDataCollector        # Memory usage
  │   ├── ExceptionDataCollector     # Exceptions
  │   ├── EventDataCollector         # Dispatched events
  │   ├── LoggerDataCollector        # Monolog messages
  │   ├── RouterDataCollector        # Route matching
  │   └── AjaxDataCollector          # AJAX requests detection
  └── Profiling/
      └── Storage/
          └── FileProfilerStorage    # Default: var/cache/{env}/profiler/
```

## Profiler Lifecycle

```
kernel.request (ProfilerListener, priority 1024)
  → $profiler->enable()
  → set X-Debug-Token on request

kernel.response (ProfilerListener, priority -100)
  → $profiler->collect($request, $response)
    → foreach DataCollector:
      → $collector->collect($request, $response, $exception)
    → create Profile with token
  → add X-Debug-Token-Link header to response

kernel.terminate (ProfilerListener, priority -1024)
  → foreach LateDataCollectorInterface:
    → $collector->lateCollect()    # Collect data available only after response
  → $profiler->saveProfile($profile)
    → ProfilerStorageInterface::write($profile)
    → serialize all collector data to storage

kernel.response (WebDebugToolbarListener, priority -128)
  → if HTML response + not redirect + dev env:
    → inject toolbar <div> before </body>
    → toolbar loads via AJAX: GET /_profiler/{token}/toolbar
```

## DataCollector System

### Interface

```php
interface DataCollectorInterface extends ResetInterface
{
    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void;
    public function getName(): string;
    public function reset(): void;
}

interface LateDataCollectorInterface
{
    public function lateCollect(): void;  // Called during kernel.terminate
}
```

### Base Class Internals

```php
abstract class DataCollector implements DataCollectorInterface
{
    protected array|Data $data = [];

    // Serialization uses igbinary if available, then serialize()
    public function __sleep(): array { return ['data']; }
    public function __wakeup(): void {}

    // Cloner for safe data serialization
    protected function cloneVar(mixed $var): Data
    {
        // Uses VarCloner → converts to Data object
        // Safe to serialize (handles circular refs, resources, closures)
        return (new VarCloner())->cloneVar($var);
    }
}
```

### Built-in Collectors Detail

| Collector | `collect()` source | `lateCollect()` | Key data |
|-----------|-------------------|-----------------|----------|
| `RequestDataCollector` | Request/Response objects | No | Method, URI, headers, content, status, format, locale, route |
| `TimeDataCollector` | Stopwatch events | Yes | Start time, duration, memory |
| `MemoryDataCollector` | `memory_get_peak_usage()` | Yes | Peak memory, limit |
| `ExceptionDataCollector` | `$exception` param | No | Exception class, message, trace |
| `EventDataCollector` | EventDispatcher | Yes (listener details) | Events dispatched, listeners called, not called, orphans |
| `LoggerDataCollector` | DebugLoggerInterface | Yes | Log messages (priority-grouped), error count, deprecation count |
| `RouterDataCollector` | Router `_route` attribute | No | Matched route, redirect info |
| `ConfigDataCollector` | Kernel | No | PHP version, Symfony version, environment, debug mode, bundles |
| `SecurityDataCollector` | TokenStorage, Firewall | Yes | User, roles, firewall name, authentication status |
| `TwigDataCollector` | Twig Profiler | Yes | Templates rendered, render time, template count |
| `DoctrineDataCollector` | Debug DBAL logger | Yes | Queries, params, time, explain plans |
| `CacheDataCollector` | TraceableAdapter | Yes | Cache operations, hits, misses, writes |
| `ValidatorDataCollector` | TraceableValidator | Yes | Constraint violations |
| `FormDataCollector` | Form debug tree | Yes | Form types, submitted data, errors, views |
| `MailerDataCollector` | MessageEvents | Yes | Emails: to, subject, body, headers |
| `MessengerDataCollector` | TraceableMiddleware | Yes | Messages dispatched, handled, failed |

### Profile Storage

**Default:** `FileProfilerStorage` — one file per profile token:
```
var/cache/dev/profiler/
  ├── ab/                # First 2 chars of token
  │   └── cd/            # Next 2 chars
  │       └── abcdef     # Full token → serialized Profile
  └── index.csv          # Index: token,ip,method,url,time,parent,status_code
```

**Gotcha:** Profile data is serialized PHP. Large profiles (many queries, events) can be multi-MB. Profiler stores last 100 profiles by default (`framework.profiler.collect_parameter`).

## Web Debug Toolbar Injection

### WebDebugToolbarListener

```php
public function onKernelResponse(ResponseEvent $event): void
{
    $response = $event->getResponse();
    $request = $event->getRequest();

    if (!$event->isMainRequest()) return;
    if ($request->isXmlHttpRequest()) return;  // No toolbar on AJAX
    if ($response->isRedirection()) return;
    if ($response->headers->has('X-Debug-Token')) {
        // Already profiled (sub-request)
    }

    // Check Content-Type is HTML
    if (!str_contains($response->headers->get('Content-Type', ''), 'text/html')) return;

    $this->injectToolbar($response, $request);
}

private function injectToolbar(Response $response): void
{
    $content = $response->getContent();
    // Find </body> tag
    $pos = strripos($content, '</body>');
    if ($pos !== false) {
        // Insert toolbar div + AJAX loader script before </body>
        $toolbar = '<div id="sfwdt{token}" ...></div><script>...</script>';
        $content = substr($content, 0, $pos) . $toolbar . substr($content, $pos);
        $response->setContent($content);
    }
}
```

**How the toolbar loads:**
1. Listener injects a `<div>` placeholder + `<script>` before `</body>`
2. Script makes AJAX GET to `/_profiler/{token}/toolbar`
3. ProfilerController returns toolbar HTML
4. Script replaces placeholder with toolbar content

### How to NOT break user's responses

The toolbar listener has multiple guards:
- Only main request (not sub-requests/ESI)
- Only HTML responses (Content-Type check)
- Not on redirects
- Not on AJAX (X-Requested-With header)
- Not on streaming responses (no `getContent()`)
- Only in dev environment (bundle registered only for `dev`)

## Profiler Panel System

### Creating a Custom Panel (Symfony way)

```php
// DataCollector
class CustomCollector extends DataCollector
{
    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        $this->data = ['items' => $this->cloneVar($collectedData)];
    }

    public function getName(): string { return 'custom'; }
    public function reset(): void { $this->data = []; }

    // Accessor methods for Twig template
    public function getItems(): Data { return $this->data['items']; }
}

// Twig template: templates/data_collector/custom.html.twig
// Extends @WebProfiler/Profiler/layout.html.twig
// Block: toolbar (summary), menu (sidebar), panel (detail page)
```

```yaml
# services.yaml
services:
    App\DataCollector\CustomCollector:
        tags:
            - name: data_collector
              template: '@App/data_collector/custom.html.twig'
              id: 'custom'
              priority: 300
```

### Panel Rendering Pipeline

```
ProfilerController::panelAction($token, $panel)
  → $profiler->loadProfile($token)
  → find collector by panel name
  → render Twig template with collector data
  → return Response
```

### Limitations of Symfony Profiler

| Limitation | Details |
|-----------|---------|
| Twig-coupled UI | Panels are Twig templates — no SPA, no API |
| Serialized PHP storage | Not portable, not queryable |
| No real-time | Data available only after request completes |
| No cross-app | Single Symfony app only |
| HTML injection | Toolbar modifies response body — fragile |
| No structured data | Collectors return opaque `Data` objects |
| No SSE/WebSocket | Polling only (manual page refresh) |
| Heavy serialization | Large profiles slow to store/load |
| Memory overhead | All collectors run on every request |
| No collector communication | Collectors can't share data (no timeline) |

### How ADP Differs from Symfony Profiler

| Aspect | Symfony Profiler | ADP Symfony Adapter |
|--------|-----------------|---------------------|
| Storage | Serialized PHP files | JSON via Kernel FileStorage |
| UI | Twig templates in toolbar | React SPA (separate app) |
| Transport | Page refresh / AJAX | REST API + SSE real-time |
| Data format | `VarCloner\Data` objects | Structured JSON (Kernel Dumper) |
| Architecture | `DataCollectorInterface` | `CollectorInterface` (Kernel) |
| Cross-framework | Symfony only | Any PHP framework |
| Extensibility | Twig templates + tags | Kernel collectors + API |
| Console | Limited (ConsoleEvents) | Full (ConsoleSubscriber) |
| Persistence | 100 profiles, then purge | Configurable history_size |
| Timeline | Stopwatch component (partial) | TimelineCollector (cross-collector) |

## How User Config Doesn't Override Bundle Defaults

### Configuration Merge Strategy

```yaml
# Bundle default (in Configuration.php):
#   collectors.request: true (defaultTrue)
#   collectors.log: true (defaultTrue)
#   storage.path: '%kernel.project_dir%/var/debug'

# User writes (config/packages/app_dev_panel.yaml):
app_dev_panel:
    collectors:
        log: false            # Override: disable log collector
    # request not mentioned → keeps default (true)
    # storage not mentioned → keeps default
```

**Merge rules:**
- Scalar nodes: user value overrides default
- Array nodes with `addDefaultsIfNotSet()`: missing keys get defaults
- `canBeDisabled()`: generates `enabled: true` default + allows `enabled: false`
- `arrayNode()->useAttributeAsKey('name')`: merges by key, not replaces
- Environment override: `config/packages/dev/` overrides `config/packages/`

### Protecting Against Accidental Overwrites

```php
$rootNode
    ->children()
        // User CAN'T set invalid types — tree builder validates
        ->integerNode('history_size')
            ->defaultValue(50)
            ->min(1)->max(1000)  // Validated range
        ->end()

        // User CAN'T remove required children
        ->arrayNode('storage')
            ->isRequired()
            ->children()
                ->scalarNode('path')->isRequired()->cannotBeEmpty()->end()
            ->end()
        ->end()

        // User CAN disable but defaults are safe
        ->arrayNode('collectors')
            ->addDefaultsIfNotSet()  // Key: missing keys get defaults
            ->children()
                ->booleanNode('request')->defaultTrue()->end()
            ->end()
        ->end()
    ->end();
```

### Config Environments

```
config/packages/
  ├── app_dev_panel.yaml          # Base config (all environments)
  ├── dev/
  │   └── app_dev_panel.yaml      # Dev overrides
  └── prod/
      └── app_dev_panel.yaml      # Prod: enabled: false
```

Symfony merges: base → environment-specific. Deep merge for arrays.
