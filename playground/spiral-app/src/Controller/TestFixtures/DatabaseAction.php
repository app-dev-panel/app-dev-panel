<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use Cycle\Database\DatabaseProviderInterface;

final class DatabaseAction
{
    public function __construct(
        private readonly DatabaseProviderInterface $dbal,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        $db = $this->dbal->database();

        // Schema — table is fresh per request because the connection is in-memory.
        $db->execute('CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT)');

        $db->execute('INSERT INTO users (name, email) VALUES (?, ?)', ['Alice', 'alice@adp.test']);
        $db->execute('INSERT INTO users (name, email) VALUES (?, ?)', ['Bob', 'bob@adp.test']);
        $db->execute('INSERT INTO users (name, email) VALUES (?, ?)', ['Charlie', 'charlie@adp.test']);

        $matched = $db->query('SELECT * FROM users WHERE name LIKE ?', ['A%'])->fetchAll();
        $count = $db->query('SELECT COUNT(*) AS n FROM users')->fetch();

        return [
            'fixture' => 'database:basic',
            'status' => 'ok',
            'matched' => $matched,
            'total' => $count,
        ];
    }
}
