<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Bootloader;

use AppDevPanel\Adapter\Spiral\Interceptor\DebugConsoleInterceptor;
use AppDevPanel\Adapter\Spiral\Interceptor\DebugQueueInterceptor;
use AppDevPanel\Adapter\Spiral\Interceptor\DebugRouteInterceptor;
use Psr\Container\ContainerInterface;
use Spiral\Boot\Bootloader\Bootloader;

/**
 * Optional companion bootloader to {@see AppDevPanelBootloader} that registers
 * domain-specific Spiral interceptors with their host bootloaders / registries.
 *
 * Three interceptors are registered:
 *
 *   - {@see DebugConsoleInterceptor} → `Spiral\Console\Bootloader\ConsoleBootloader::addInterceptor()`
 *   - {@see DebugQueueInterceptor}   → `Spiral\Queue\QueueRegistry::addConsumeInterceptor()`
 *   - {@see DebugRouteInterceptor}   → opt-in per route via `RouteInterface::withInterceptors()`
 *
 * Each registration is gated by `class_exists` / `interface_exists` so apps that
 * don't install the matching optional Spiral package boot cleanly. The route
 * interceptor has no global hook in spiral/router 3.x — apps add it per route.
 *
 * Usage:
 *
 *     public function defineBootloaders(): array
 *     {
 *         return [
 *             AppDevPanelBootloader::class,
 *             AdpInterceptorBootloader::class,
 *         ];
 *     }
 *
 * The `defineDependencies()` method declares `AppDevPanelBootloader` as a dependency
 * so the singletons (Debugger, collectors, interceptors) are guaranteed to be in the
 * container by the time `boot()` runs here.
 */
final class AdpInterceptorBootloader extends Bootloader
{
    protected const SINGLETONS = [
        DebugRouteInterceptor::class => DebugRouteInterceptor::class,
        DebugConsoleInterceptor::class => DebugConsoleInterceptor::class,
        DebugQueueInterceptor::class => DebugQueueInterceptor::class,
    ];

    public function defineDependencies(): array
    {
        return [AppDevPanelBootloader::class];
    }

    public function boot(ContainerInterface $container): void
    {
        $this->registerConsoleInterceptor($container);
        $this->registerQueueInterceptor($container);

        // Route interceptor is opt-in per route — see class docblock.
    }

    private function registerConsoleInterceptor(ContainerInterface $container): void
    {
        $bootloaderClass = 'Spiral\\Console\\Bootloader\\ConsoleBootloader';
        if (!class_exists($bootloaderClass) || !$container->has($bootloaderClass)) {
            return;
        }

        $bootloader = $container->get($bootloaderClass);
        if (!is_object($bootloader) || !method_exists($bootloader, 'addInterceptor')) {
            return;
        }

        $bootloader->addInterceptor(DebugConsoleInterceptor::class);
    }

    private function registerQueueInterceptor(ContainerInterface $container): void
    {
        $registryClass = 'Spiral\\Queue\\QueueRegistry';
        if (!class_exists($registryClass) || !$container->has($registryClass)) {
            return;
        }

        $registry = $container->get($registryClass);
        if (!is_object($registry) || !method_exists($registry, 'addConsumeInterceptor')) {
            return;
        }

        $registry->addConsumeInterceptor(DebugQueueInterceptor::class);
    }
}
