<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Helper;

use AppDevPanel\Kernel\Helper\Silencer;
use PHPUnit\Framework\TestCase;

final class SilencerTest extends TestCase
{
    public function testReturnsCallbackResult(): void
    {
        self::assertSame(42, Silencer::run(static fn(): int => 42));
    }

    public function testMutesWarningsRaisedByTheCallback(): void
    {
        // PHPUnit converts warnings into failures; a muted `file_get_contents` on a
        // missing path must therefore simply return `false`.
        $result = Silencer::run(static fn(): string|false => file_get_contents(
            sys_get_temp_dir() . '/adp-silencer-' . bin2hex(random_bytes(4)) . '/missing',
        ));

        self::assertFalse($result);
    }

    public function testRestoresPreviousHandlerEvenWhenCallbackThrows(): void
    {
        $seen = [];
        set_error_handler(static function (int $severity, string $message) use (&$seen): bool {
            $seen[] = $message;
            return true;
        });

        try {
            try {
                Silencer::run(static function (): never {
                    throw new \RuntimeException('boom');
                });
                self::fail('Exception must propagate');
            } catch (\RuntimeException $e) {
                self::assertSame('boom', $e->getMessage());
            }

            trigger_error('after-silencer', E_USER_NOTICE);
        } finally {
            restore_error_handler();
        }

        self::assertSame(['after-silencer'], $seen);
    }
}
