<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Storage;

use InvalidArgumentException;

/**
 * Canonical debug entry id format, shared by every storage driver and by the API layer.
 *
 * {@see \AppDevPanel\Kernel\DebuggerIdGenerator} emits `uniqid('', true)` with the dot
 * stripped (lowercase hex + digits, 23 chars). Externally supplied ids (ingestion API,
 * MCP tools, URL segments) must stay within `[A-Za-z0-9_-]{1,64}` — no separators, dots
 * or glob characters — because {@see FileStorage} turns the id into a directory name.
 */
final class StorageIdValidator
{
    public const string PATTERN = '/^[A-Za-z0-9_-]{1,64}$/';

    public static function isValid(mixed $id): bool
    {
        return is_string($id) && preg_match(self::PATTERN, $id) === 1;
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function assertValid(string $id): string
    {
        if (!self::isValid($id)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid debug entry id "%s": expected 1-64 characters of [A-Za-z0-9_-].',
                $id,
            ));
        }

        return $id;
    }
}
