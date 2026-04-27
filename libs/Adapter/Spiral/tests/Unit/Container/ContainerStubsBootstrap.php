<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Tests\Unit\Container;

/**
 * Loads {@see container-stubs.php} so the autofeed Container tests have minimal
 * stand-ins for the optional `spiral/mailer`, `spiral/queue`, `spiral/translator`,
 * `spiral/views` packages — none of which are pulled into the root `vendor/`. Each
 * stub is conditionally declared, so this is a no-op when the real packages are
 * installed.
 */
final class ContainerStubsBootstrap
{
    private static bool $installed = false;

    public static function install(): void
    {
        if (self::$installed) {
            return;
        }
        self::$installed = true;

        require_once __DIR__ . '/container-stubs.php';
    }
}
