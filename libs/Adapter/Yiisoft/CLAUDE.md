# Yii 3 Adapter

Bridges the ADP Kernel and API into the Yii 3 framework. This is the first (reference) adapter
implementation. Future adapters for Symfony, Laravel, etc. should follow a similar pattern.

## Package

- Composer: `app-dev-panel/adapter-yiisoft`
- Namespace: `AppDevPanel\Adapter\Yiisoft\`
- PHP: 8.4+
- Dependencies: `app-dev-panel/kernel`, `app-dev-panel/api`, Yiisoft packages

## Directory Structure

```
config/
├── bootstrap.php         # VarDumper handler setup (runs at bootstrap)
├── di.php                # Common DI definitions (storage, proxies, collectors)
├── di-web.php            # Web-specific DI (RequestCollector, WebAppInfoCollector)
├── di-console.php        # Console-specific DI (CommandCollector, ConsoleAppInfoCollector)
├── di-providers.php      # Service provider registration
├── events-web.php        # Web event → debugger lifecycle mapping
├── events-console.php    # Console event → debugger lifecycle mapping
└── params.php            # Master configuration for all settings
src/
└── DebugServiceProvider.php  # Wraps container with ContainerInterfaceProxy
```

## How It Works

### 1. Config Plugin Auto-Registration

Yii 3 uses config plugins. This adapter registers itself via `composer.json` extra config,
so installing the package automatically wires everything up.

### 2. Bootstrap Phase (`bootstrap.php`)

Replaces the VarDumper handler with `VarDumperHandlerInterfaceProxy` so that
`dump()` calls are captured by `VarDumperCollector`.

### 3. DI Registration (`di.php`, `di-web.php`, `di-console.php`)

Registers all proxy services as decorators in the DI container:

- `LoggerInterface` → `LoggerInterfaceProxy`
- `EventDispatcherInterface` → `EventDispatcherInterfaceProxy`
- `ClientInterface` (HTTP) → `HttpClientInterfaceProxy`
- Any tracked services → `ServiceProxy`

Registers storage, collectors, and their configurations.

### 4. Service Provider (`DebugServiceProvider.php`)

Wraps the `ContainerInterface` itself with `ContainerInterfaceProxy` to track
which services are resolved from the DI container.

### 5. Event Wiring (`events-web.php`, `events-console.php`)

Maps framework lifecycle events to debugger lifecycle:

**Web events:**
| Framework Event | Debugger Action |
|----------------|-----------------|
| `ApplicationStartup` | `Debugger::startup()` |
| `BeforeRequest` | Collectors receive request data |
| `AfterRequest` | Collectors receive response data |
| `AfterEmit` | `Debugger::shutdown()` |
| `ApplicationError` | `ExceptionCollector` captures error |

**Console events:**
| Framework Event | Debugger Action |
|----------------|-----------------|
| `ApplicationStartup` | `Debugger::startup()` |
| `ConsoleCommandEvent` | `CommandCollector` captures command |
| `ConsoleTerminateEvent` | `Debugger::shutdown()` |
| `ConsoleErrorEvent` | `ExceptionCollector` captures error |

## Configuration (`params.php`)

```php
'app-dev-panel/yii-debug' => [
    'enabled' => true,                    // Enable/disable debugger
    'collectors' => [...],                // Active collectors
    'trackedServices' => [...],           // Services to proxy with ServiceProxy
    'ignoredRequests' => [],              // URL patterns to skip
    'ignoredCommands' => [],              // Command patterns to skip
    'dumper' => [
        'excludedClasses' => [],          // Classes to skip in dumps
    ],
    'logLevel' => [
        'AppDevPanel\\' => 0,             // Log level per namespace
    ],
    'storage' => [
        'path' => '@runtime/debug',       // Storage directory
        'historySize' => 50,              // Max entries
        'exclude' => [],                  // Collectors to exclude from storage
    ],
],
```

## Creating a New Adapter

To integrate ADP with another framework (e.g., Symfony), follow this adapter's pattern:

1. **DI integration**: Register Kernel proxies as service decorators
2. **Event mapping**: Map framework events to `Debugger::startup()`/`shutdown()`
3. **Config**: Provide sensible defaults, let users customize
4. **Bootstrap**: Wire VarDumper handler early in the boot process
5. **Context separation**: Different collectors for web vs. CLI
