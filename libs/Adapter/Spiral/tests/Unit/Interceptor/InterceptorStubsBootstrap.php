<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Tests\Unit\Interceptor;

use AppDevPanel\Adapter\Spiral\Tests\Unit\Inspector\SpiralStubsBootstrap;

/**
 * Loads {@see interceptor-stubs.php} so the interceptor unit tests have minimal
 * stand-ins for the optional `spiral/console`, `spiral/queue`, and `spiral/boot`
 * packages. The root composer doesn't pull those in (only the playground does),
 * so the tests need stubs to construct realistic doubles.
 *
 * Reuses the Inspector test stubs ({@see SpiralStubsBootstrap}) for the
 * `spiral/router` interfaces (`RouteInterface`, `UriHandler`, `Router`) so the two
 * stub files don't redefine the same symbols.
 *
 * Each stub is conditionally declared, so this is a no-op when the real packages
 * are installed.
 */
final class InterceptorStubsBootstrap
{
    private static bool $installed = false;

    public static function install(): void
    {
        if (self::$installed) {
            return;
        }
        self::$installed = true;

        // Pulls in `Spiral\Router\{RouteInterface,UriHandler,RouterInterface}` and the
        // `Spiral\Boot\*` event/auth stubs the inspector tests rely on.
        SpiralStubsBootstrap::install();

        require_once __DIR__ . '/interceptor-stubs.php';
    }
}
