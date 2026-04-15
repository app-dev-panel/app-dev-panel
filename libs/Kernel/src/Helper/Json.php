<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Helper;

/**
 * Internal JSON helper with ADP-default flags. Kept intentionally minimal —
 * we only encode arrays/scalars (collector payloads) and always decode to
 * arrays. Throws `\JsonException` on failure via `JSON_THROW_ON_ERROR`.
 */
final class Json
{
    /**
     * Default encode flags: pretty URLs, UTF-8 intact, exceptions on error.
     */
    public const int DEFAULT_ENCODE_FLAGS = JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

    public static function encode(mixed $value, int $depth = 512): string
    {
        return json_encode($value, self::DEFAULT_ENCODE_FLAGS, $depth);
    }

    public static function decode(string $json, int $depth = 512): mixed
    {
        if ($json === '') {
            return null;
        }

        return json_decode($json, true, $depth, JSON_THROW_ON_ERROR);
    }
}
