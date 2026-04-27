<?php

declare(strict_types=1);

/**
 * Compatibility stubs for the optional Spiral packages the interceptor classes touch.
 *
 * The root composer pulls in `spiral/core` + `spiral/interceptors` (and a handful of
 * other components) but NOT `spiral/boot`, `spiral/router`, `spiral/console`, or
 * `spiral/queue`. The interceptor unit tests need minimal stand-ins for those — both
 * to construct realistic doubles AND to satisfy `interface_exists` / `class_exists`
 * gates inside the production code.
 *
 * Each stub is conditionally declared: when the real package IS installed (CI matrix,
 * playground vendor) the autoloader's class wins and the stub is skipped.
 *
 * @noinspection PhpUnused
 */

namespace Spiral\Boot\Bootloader {
    if (!class_exists(Bootloader::class)) {
        abstract class Bootloader
        {
            /** @var array<string, class-string|callable> */
            protected const SINGLETONS = [];

            /** @var array<string, class-string|callable> */
            protected const BINDINGS = [];

            public function defineBindings(): array
            {
                return static::BINDINGS;
            }

            public function defineSingletons(): array
            {
                return static::SINGLETONS;
            }

            public function defineDependencies(): array
            {
                return [];
            }
        }
    }
}

namespace Spiral\Router {
    // `RouteInterface` + `UriHandler` are defined by the Inspector stubs
    // (`spiral-stubs.php`), which `InterceptorStubsBootstrap::install()` always
    // loads first. We only add the `Router` class here for its `ROUTE_*` constants.

    if (!class_exists(Router::class)) {
        final class Router
        {
            public const ROUTE_ATTRIBUTE = 'route';
            public const ROUTE_NAME = 'routeName';
            public const ROUTE_MATCHES = 'matches';
        }
    }
}

namespace Spiral\Console\Bootloader {
    if (!class_exists(ConsoleBootloader::class)) {
        /**
         * Tiny stub mirroring the relevant slice of `spiral/console`'s bootloader —
         * just enough surface for the AdpInterceptorBootloader test to assert that
         * `addInterceptor` was called with the expected class string.
         */
        final class ConsoleBootloader
        {
            /** @var list<string> */
            public array $registeredInterceptors = [];

            public function addInterceptor(string $interceptor): void
            {
                $this->registeredInterceptors[] = $interceptor;
            }
        }
    }
}

namespace Spiral\Queue {
    if (!class_exists(QueueRegistry::class)) {
        /**
         * Tiny stub mirroring the relevant slice of `spiral/queue`'s registry — just
         * enough surface for the AdpInterceptorBootloader test to assert that
         * `addConsumeInterceptor` was called with the expected class string.
         */
        final class QueueRegistry
        {
            /** @var list<string|object> */
            public array $registeredConsumeInterceptors = [];

            public function addConsumeInterceptor(string|object $interceptor): void
            {
                $this->registeredConsumeInterceptors[] = $interceptor;
            }
        }
    }
}
