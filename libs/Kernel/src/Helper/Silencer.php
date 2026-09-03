<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Helper;

/**
 * Runs a callback with PHP warnings/notices muted for its duration.
 *
 * Replacement for the `@` operator on inherently racy filesystem calls
 * (`mkdir` after `is_dir`, `rename` onto a live file, best-effort `copy`):
 * the return value still signals failure, but no warning escapes into a
 * strict error handler that would turn it into an exception. The previous
 * handler is always restored, even when the callback throws.
 */
final class Silencer
{
    /**
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T
     */
    public static function run(callable $callback): mixed
    {
        set_error_handler(static fn(): bool => true);

        try {
            return $callback();
        } finally {
            restore_error_handler();
        }
    }
}
