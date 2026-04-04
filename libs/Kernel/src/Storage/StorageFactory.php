<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Storage;

use AppDevPanel\Kernel\DebuggerIdGenerator;

/**
 * Creates a StorageInterface instance from a driver name or class name.
 *
 * Built-in drivers: 'file' (default), 'sqlite'.
 * Custom drivers: pass any class implementing StorageInterface.
 */
final class StorageFactory
{
    /**
     * @param string $driver Built-in driver name ('sqlite', 'file') or FQCN of a StorageInterface implementation
     * @param string $path Storage path (directory for file driver, database file path for sqlite)
     * @param DebuggerIdGenerator $idGenerator ID generator instance
     * @param array $excludedClasses Classes to exclude from object dumps
     *
     * @throws \InvalidArgumentException if the driver is unknown or doesn't implement StorageInterface
     */
    public static function create(
        string $driver,
        string $path,
        DebuggerIdGenerator $idGenerator,
        array $excludedClasses = [],
    ): StorageInterface {
        return match ($driver) {
            'sqlite' => new SqliteStorage($path . '/debug.db', $idGenerator, $excludedClasses),
            'file' => new FileStorage($path, $idGenerator, $excludedClasses),
            default => self::createCustom($driver, $path, $idGenerator, $excludedClasses),
        };
    }

    private static function createCustom(
        string $class,
        string $path,
        DebuggerIdGenerator $idGenerator,
        array $excludedClasses,
    ): StorageInterface {
        if (!class_exists($class)) {
            throw new \InvalidArgumentException(sprintf(
                'Storage driver "%s" is not a valid built-in driver or class name.',
                $class,
            ));
        }

        if (!is_subclass_of($class, StorageInterface::class)) {
            throw new \InvalidArgumentException(sprintf(
                'Storage driver class "%s" must implement %s.',
                $class,
                StorageInterface::class,
            ));
        }

        return new $class($path, $idGenerator, $excludedClasses);
    }
}
