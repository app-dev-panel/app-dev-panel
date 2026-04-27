<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Tests\Unit\Inspector;

/**
 * Loads {@see spiral-stubs.php} so the Inspector tests have minimal stand-ins for the
 * `spiral/boot`, `spiral/router`, and `spiral/events` packages — none of which are
 * pulled into the root `vendor/` (only `spiral/core` is). Each stub is conditionally
 * declared, so this is a no-op when the real packages are installed.
 */
final class SpiralStubsBootstrap
{
    private static bool $installed = false;

    public static function install(): void
    {
        if (self::$installed) {
            return;
        }
        self::$installed = true;

        require_once __DIR__ . '/spiral-stubs.php';
    }
}
