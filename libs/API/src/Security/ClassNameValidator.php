<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Security;

/**
 * Guards `class_exists()` / `is_subclass_of()` calls on request-supplied class names.
 *
 * Autoloading is only triggered for syntactically valid fully-qualified PHP identifiers,
 * so a Composer PSR-4 autoloader can never be steered outside its registered
 * directories (`\` maps to `/`, no `.`, `/` or other path characters are accepted).
 */
final class ClassNameValidator
{
    private const string PATTERN = '/^\\\\?[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*(?:\\\\[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*)*$/';

    private const int MAX_LENGTH = 255;

    public static function isValid(mixed $class): bool
    {
        return is_string($class) && strlen($class) <= self::MAX_LENGTH && preg_match(self::PATTERN, $class) === 1;
    }

    /**
     * `class_exists()` restricted to well-formed identifiers.
     */
    public static function classExists(mixed $class): bool
    {
        return self::isValid($class) && class_exists($class);
    }

    /**
     * `is_subclass_of()` restricted to well-formed identifiers; never autoloads garbage.
     *
     * @param class-string $parent
     */
    public static function isSubclassOf(mixed $class, string $parent): bool
    {
        return self::isValid($class) && is_subclass_of($class, $parent, true);
    }
}
