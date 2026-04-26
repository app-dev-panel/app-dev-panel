<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use App\Application\TracingPdo;

final class DatabaseAction
{
    public function __construct(
        private readonly TracingPdo $pdo,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        // Schema (skip if already created — the playground keeps an in-memory database
        // per request via the binding, so it's always fresh).
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY, name TEXT, email TEXT)');

        $insert = $this->pdo->prepare('INSERT INTO users (name, email) VALUES (?, ?)');
        $insert->execute(['Alice', 'alice@adp.test']);
        $insert->execute(['Bob', 'bob@adp.test']);
        $insert->execute(['Charlie', 'charlie@adp.test']);

        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE name LIKE ?');
        $stmt->execute(['A%']);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->pdo->query('SELECT COUNT(*) FROM users');

        return [
            'fixture' => 'database:basic',
            'status' => 'ok',
            'matched' => $rows,
        ];
    }
}
