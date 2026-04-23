<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

/**
 * Exercises a wide mix of SQL operations so DatabaseCollector has meaningful
 * data to display: DDL, inserts, updates, a parameterized select, a JOIN, an
 * aggregate, an IN/LIKE filter, a committed transaction, a rolled-back
 * transaction and a final cleanup delete.
 *
 * Uses a dedicated `fixture_orders` table so the pre-seeded `test_users` rows
 * (required by InspectorApiTest) are never modified.
 */
#[Route('/test/fixtures/database', name: 'test_database', methods: ['GET'])]
final readonly class DatabaseAction
{
    public function __construct(
        private Connection $connection,
    ) {}

    public function __invoke(): JsonResponse
    {
        $db = $this->connection;

        $db->executeStatement(
            'CREATE TABLE IF NOT EXISTS fixture_orders ('
            . 'id INTEGER PRIMARY KEY AUTOINCREMENT, '
            . 'user_id INTEGER NOT NULL, '
            . 'amount REAL NOT NULL, '
            . 'status TEXT NOT NULL, '
            . 'created_at TEXT NOT NULL)',
        );

        $db->executeStatement('DELETE FROM fixture_orders');

        $insertSql = 'INSERT INTO fixture_orders (user_id, amount, status, created_at) VALUES (?, ?, ?, ?)';

        $db->executeStatement($insertSql, [1, 19.99, 'pending', '2026-01-10 10:00:00']);
        $db->executeStatement($insertSql, [1, 49.50, 'pending', '2026-01-11 11:30:00']);
        $db->executeStatement($insertSql, [2, 12.00, 'pending', '2026-01-12 09:15:00']);

        $db->executeStatement('UPDATE fixture_orders SET status = ? WHERE user_id = ?', ['shipped', 1]);

        $userOrders = $db->fetchAllAssociative('SELECT id, user_id, amount, status FROM fixture_orders WHERE user_id = ? ORDER BY id', [
            1,
        ]);

        $joined = $db->fetchAllAssociative(
            'SELECT o.id AS order_id, o.amount, o.status, u.name AS user_name'
            . ' FROM fixture_orders o INNER JOIN test_users u ON o.user_id = u.id'
            . ' ORDER BY o.id',
        );

        $totals = $db->fetchAllAssociative('SELECT status, COUNT(*) AS cnt, SUM(amount) AS total'
        . ' FROM fixture_orders GROUP BY status ORDER BY status');

        $top = $db->fetchAllAssociative('SELECT id, amount, status FROM fixture_orders'
        . ' WHERE status LIKE ? AND amount > ?'
        . ' ORDER BY amount DESC LIMIT 5', ['ship%', 10.0]);

        $db->beginTransaction();

        try {
            $db->executeStatement($insertSql, [2, 5.00, 'confirmed', '2026-01-12 12:00:00']);
            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();

            throw $e;
        }

        $db->beginTransaction();
        $db->executeStatement($insertSql, [1, 999.99, 'cancelled', '2026-01-13 12:00:00']);
        $db->rollBack();

        $db->executeStatement('DELETE FROM fixture_orders WHERE status = ?', ['pending']);

        $finalCount = (int) $db->fetchOne('SELECT COUNT(*) FROM fixture_orders');

        return new JsonResponse([
            'fixture' => 'database:basic',
            'status' => 'ok',
            'final_count' => $finalCount,
            'user_1_orders' => $userOrders,
            'orders_with_user' => $joined,
            'totals_by_status' => $totals,
            'top_shipped' => $top,
        ]);
    }
}
