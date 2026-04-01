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
| <class>AppDevPanel\Kernel\Collector\LoggerInterfaceProxy</class> | PSR-3 <class>Psr\Log\LoggerInterface</class> | <class>AppDevPanel\Kernel\Collector\LogCollector</class> |
| <class>AppDevPanel\Kernel\Collector\EventDispatcherInterfaceProxy</class> | PSR-14 <class>Psr\EventDispatcher\EventDispatcherInterface</class> | <class>AppDevPanel\Kernel\Collector\EventCollector</class> |
| <class>AppDevPanel\Kernel\Collector\HttpClientInterfaceProxy</class> | PSR-18 <class>Psr\Http\Client\ClientInterface</class> | <class>AppDevPanel\Kernel\Collector\HttpClientCollector</class> |

### Framework-Specific Proxies

Framework adapters provide additional proxies for interfaces that are not PSR-standardized:

| Proxy | Framework | Interface | Paired Collector |
|-------|-----------|-----------|-----------------|
| <class>AppDevPanel\Adapter\Symfony\Proxy\SymfonyTranslatorProxy</class> | Symfony | <class>Symfony\Contracts\Translation\TranslatorInterface</class> | <class>AppDevPanel\Kernel\Collector\TranslatorCollector</class> |
| <class>AppDevPanel\Adapter\Symfony\Proxy\SymfonyEventDispatcherProxy</class> | Symfony | <class>Symfony\Contracts\EventDispatcher\EventDispatcherInterface</class> | <class>AppDevPanel\Kernel\Collector\EventCollector</class> |
| <class>AppDevPanel\Adapter\Laravel\Proxy\LaravelTranslatorProxy</class> | Laravel | <class>Illuminate\Contracts\Translation\Translator</class> | <class>AppDevPanel\Kernel\Collector\TranslatorCollector</class> |
| <class>AppDevPanel\Adapter\Laravel\Proxy\LaravelEventDispatcherProxy</class> | Laravel | `Dispatcher` | <class>AppDevPanel\Kernel\Collector\EventCollector</class> |
| <class>AppDevPanel\Adapter\Yiisoft\Collector\Translator\TranslatorInterfaceProxy</class> | Yiisoft | `TranslatorInterface` | <class>AppDevPanel\Kernel\Collector\TranslatorCollector</class> |
| <class>AppDevPanel\Adapter\Yiisoft\Collector\Validator\ValidatorInterfaceProxy</class> | Yiisoft | `ValidatorInterface` | <class>AppDevPanel\Kernel\Collector\ValidatorCollector</class> |
| <class>AppDevPanel\Adapter\Yiisoft\Proxy\ContainerInterfaceProxy</class> | Yiisoft | PSR-11 <class>Psr\Container\ContainerInterface</class> | <class>AppDevPanel\Kernel\Collector\ServiceCollector</class> |
| <class>AppDevPanel\Adapter\Yii2\Proxy\I18NProxy</class> | Yii 2 | `yii\i18n\I18N` | <class>AppDevPanel\Kernel\Collector\TranslatorCollector</class> |

### Translator Proxies

Each framework has its own translator interface. ADP provides a dedicated proxy for each, all feeding the same <class>AppDevPanel\Kernel\Collector\TranslatorCollector</class>. See the [Translator](/guide/translator) page for full details.

**Symfony** -- decorates <class>Symfony\Contracts\Translation\TranslatorInterface</class> via `setDecoratedService()` in the compiler pass. Intercepts `trans()` calls.

**Laravel** -- decorates <class>Illuminate\Contracts\Translation\Translator</class> via `$app->extend('translator')`. Intercepts `get()` and `choice()` calls. Parses Laravel's dot-notation keys (`group.key`) into category and message.

**Yiisoft** -- registered in `trackedServices` alongside <class>AppDevPanel\Adapter\Yiisoft\Collector\Validator\ValidatorInterfaceProxy</class>. Intercepts `translate()` calls. Supports `withDefaultCategory()` and `withLocale()` immutable methods.

**Yii 2** -- extends `yii\i18n\I18N` and overrides `translate()`. Replaces the `i18n` application component during module bootstrap.

::: tip Missing translation detection
All proxies detect missing translations by comparing the translator's return value with the original message ID. If they are identical, the translation is marked as `missing`.
:::

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
