<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus\Storage;

use PDO;

final class PdoFactory
{
    public static function create(string $databasePath): PDO
    {
        $pdo = new PDO("sqlite:{$databasePath}", null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        SqliteSchema::create($pdo);

        return $pdo;
    }

    public static function createInMemory(): PDO
    {
        return self::create(':memory:');
    }
}
