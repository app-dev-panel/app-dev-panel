<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Inspector;

use Closure;
use Yiisoft\VarDumper\VarDumper;

/**
 * Canonical serializer for the inspector HTTP layer.
 *
 * Wraps {@see VarDumper::asPrimitives()} with a recursive pre-walk that converts every
 * {@see Closure} instance reachable through arrays into a {@see ClosureDescriptor} marker.
 * This keeps closure source code structured end-to-end so the frontend can render it as
 * a syntax-highlighted code block rather than a truncated string preview.
 */
final class Primitives
{
    public static function dump(mixed $value, int $depth = 255): mixed
    {
        return VarDumper::create(self::walk($value))->asPrimitives($depth);
    }

    private static function walk(mixed $value): mixed
    {
        if ($value instanceof Closure) {
            return ClosureDescriptor::describe($value);
        }
        if (is_array($value)) {
            return array_map(self::walk(...), $value);
        }
        return $value;
    }
}
