<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/test/fixtures/database', name: 'test_database', methods: ['GET'])]
final readonly class DatabaseAction
{
    public function __construct(
        private Connection $connection,
    ) {}

    public function __invoke(): JsonResponse
    {
        // Execute real SQL queries via Doctrine DBAL — the DoctrineDbalMiddleware
        // intercepts these calls and feeds query data to DatabaseCollector.
        // Drop any pre-existing table so the schema stays consistent across runs
        // (other fixtures/collectors may create a stricter schema).
        $this->connection->executeStatement('DROP TABLE IF EXISTS test_users');

        $this->connection->executeStatement(
            'CREATE TABLE IF NOT EXISTS test_users (id INTEGER PRIMARY KEY, name TEXT, email TEXT)',
        );

        $this->connection->executeStatement('INSERT OR REPLACE INTO test_users (id, name, email) VALUES (?, ?, ?)', [
            1,
            'John Doe',
            'john@example.com',
        ]);

        $result = $this->connection->fetchAssociative('SELECT * FROM test_users WHERE id = ?', [1]);

        return new JsonResponse(['fixture' => 'database:basic', 'status' => 'ok', 'user' => $result]);
    }
}
