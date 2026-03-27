---
title: Proxy System
---

# Proxy System

ADP uses the proxy pattern to intercept PSR interface calls transparently. Proxies wrap standard PSR implementations and feed intercepted data to collectors without modifying application code.

## How It Works

A proxy implements the same PSR interface as the original service. It delegates all calls to the real implementation while recording data for its paired collector.

```
Application Code
       │
       ▼
┌─────────────────┐
│  PSR Interface   │  ← Your code calls this normally
│  (e.g. Logger)   │
└────────┬────────┘
         │
┌────────▼────────┐
│     Proxy        │  ← Intercepts + records
│ (LoggerProxy)    │
└────────┬────────┘
         │
┌────────▼────────┐
│  Real Service    │  ← Original implementation
│ (Monolog, etc.)  │
└────────┬────────┘
         │
┌────────▼────────┐
│   Collector      │  ← Receives recorded data
│ (LogCollector)   │
└─────────────────┘
```

## Built-in Proxies

Kernel provides framework-independent PSR proxies:

| Proxy | PSR Interface | Paired Collector |
|-------|---------------|-----------------|
| `LoggerInterfaceProxy` | PSR-3 `LoggerInterface` | `LogCollector` |
| `EventDispatcherInterfaceProxy` | PSR-14 `EventDispatcherInterface` | `EventCollector` |
| `HttpClientInterfaceProxy` | PSR-18 `ClientInterface` | `HttpClientCollector` |

Additional proxies exist in framework adapters (e.g., `ContainerInterfaceProxy` for PSR-11 in the Yii adapter).

## ProxyDecoratedCalls Trait

The `ProxyDecoratedCalls` trait provides `__call`, `__get`, and `__set` magic methods that delegate to the wrapped service. Proxies use this trait to forward any non-intercepted method calls transparently.

## Proxy Registration

Proxies are registered by framework adapters through DI container configuration. The adapter replaces the original PSR service binding with the proxy, which wraps the original service:

```php
// Simplified adapter DI configuration
LoggerInterface::class => function (ContainerInterface $c) {
    return new LoggerInterfaceProxy(
        $c->get(LogCollector::class),
        $c->get('original.logger'),
    );
},
```

## Key Principles

- **Transparent** -- Application code is unaware of interception
- **Zero overhead when disabled** -- Proxies are only registered when ADP is active
- **PSR-compliant** -- Proxies implement the exact same interface as the original service
- **Colocated** -- Each proxy lives alongside its paired collector in `src/Collector/`
