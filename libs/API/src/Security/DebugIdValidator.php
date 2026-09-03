<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Security;

use AppDevPanel\Api\Debug\Exception\BadRequestException;
use AppDevPanel\Kernel\Storage\StorageIdValidator;

/**
 * HTTP-layer wrapper around Kernel's {@see StorageIdValidator}: same `[A-Za-z0-9_-]{1,64}`
 * format, but an invalid id becomes a {@see BadRequestException} (400) instead of the
 * Kernel's `InvalidArgumentException`. Applied before an id reaches storage so a
 * traversal attempt never turns into a 500 from `FileStorage` / `SqliteStorage`.
 */
final class DebugIdValidator
{
    public const string PATTERN = StorageIdValidator::PATTERN;

    public static function isValid(mixed $id): bool
    {
        return StorageIdValidator::isValid($id);
    }

    /**
     * @throws BadRequestException
     */
    public static function assertValid(mixed $id, string $field = 'id'): string
    {
        if (!self::isValid($id)) {
            throw new BadRequestException(sprintf(
                'Invalid debug entry %s: expected 1-64 characters of [A-Za-z0-9_-].',
                $field,
            ));
        }

        /** @var string $id */
        return $id;
    }
}
