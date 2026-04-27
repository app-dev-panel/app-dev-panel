<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Container;

use Psr\Container\ContainerInterface;
use Spiral\Core\BinderInterface;

/**
 * Helper trait for {@see \Spiral\Core\Container\InjectorInterface} implementations
 * that decorate an existing binding.
 *
 * Spiral's `bindInjector(string $alias, string $injectorClass)` overwrites the existing
 * binding for `$alias` with an `Injectable` config — the container has no native concept
 * of "stack" or "previous binding". Therefore, when our bootloader registers an injector
 * over a service the application has already bound (e.g. Monolog for `LoggerInterface`),
 * the original instance must be captured BEFORE the injector is installed and stored on
 * the injector itself; otherwise it is lost.
 *
 * This trait exposes `setUnderlying()` (called from `AppDevPanelBootloader::boot()` while
 * the original binding is still resolvable) and `resolveUnderlying()` (called from
 * `createInjection()`). The resolution order is:
 *   1. Captured instance set via `setUnderlying()` — the common path.
 *   2. Live container lookup with the injector temporarily detached — only useful when
 *      the application binds the type AFTER the bootloader has run; falls back to the
 *      provided `$fallback` when nothing is bound.
 */
trait InjectorTrait
{
    private ?object $underlying = null;

    /**
     * Capture the original service instance before our injector replaces the binding.
     * Called from the bootloader's `boot()` method while the prior `Shared`/`Factory`
     * binding is still in place.
     */
    public function setUnderlying(?object $underlying): void
    {
        $this->underlying = $underlying;
    }

    /**
     * Return the underlying service instance, or `$fallback` if none is available.
     *
     * @template T of object
     *
     * @param class-string<T> $type
     * @param T|null $fallback
     *
     * @return T|null
     */
    private function resolveUnderlying(
        ContainerInterface $container,
        BinderInterface $binder,
        string $type,
        ?object $fallback = null,
    ): ?object {
        if ($this->underlying !== null) {
            /** @var T */
            return $this->underlying;
        }

        // Fallback path: nothing was captured at boot time. Detach our injector from
        // the alias, ask the container to resolve it normally (in case the application
        // bound it after the bootloader ran), then re-attach. If that also yields
        // nothing, surface the supplied default.
        if (!$binder->hasInjector($type)) {
            return $container->has($type) ? $container->get($type) : $fallback;
        }

        $binder->removeInjector($type);
        try {
            if ($container->has($type)) {
                /** @var T */
                return $container->get($type);
            }
        } finally {
            $binder->bindInjector($type, static::class);
        }

        return $fallback;
    }
}
