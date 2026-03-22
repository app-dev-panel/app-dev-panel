<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Helper;

use Yiisoft\Strings\CombinedRegexp;

/**
 * All backtrace parameters should contain at least 4 elements in the following order:
 * 0 – Called method
 * 1 – Proxy
 * 2 – Real using place / Composer\ClassLoader include function
 * 3 – Whatever / Composer\ClassLoader
 */
final class BacktraceIgnoreMatcher
{
    public static function isIgnoredByFile(array $backtrace, array $patterns): bool
    {
        if (!array_key_exists(2, $backtrace)) {
            return false;
        }
        $path = $backtrace[2]['file'];

        return self::doesStringMatchPattern($path, $patterns);
    }

    public static function isIgnoredByClass(array $backtrace, array $classes): bool
    {
        return (
            array_key_exists(3, $backtrace)
            && array_key_exists('class', $backtrace[3])
            && in_array($backtrace[3]['class'], $classes, true)
        );
    }

    public static function doesStringMatchPattern(string $string, array $patterns): bool
    {
        if ($patterns === []) {
            return false;
        }
        return new CombinedRegexp($patterns)->matches($string);
    }
}
