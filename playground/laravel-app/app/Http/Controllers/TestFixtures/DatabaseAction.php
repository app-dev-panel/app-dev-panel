<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
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
final class DatabaseAction
{
    public function __invoke(): JsonResponse
    {
        DB::statement(
            'CREATE TABLE IF NOT EXISTS fixture_orders ('
            . 'id INTEGER PRIMARY KEY AUTOINCREMENT, '
            . 'user_id INTEGER NOT NULL, '
            . 'amount REAL NOT NULL, '
            . 'status TEXT NOT NULL, '
            . 'created_at TEXT NOT NULL)',
        );

        DB::delete('DELETE FROM fixture_orders');

        $insertSql = 'INSERT INTO fixture_orders (user_id, amount, status, created_at) VALUES (?, ?, ?, ?)';

        DB::insert($insertSql, [1, 19.99, 'pending', '2026-01-10 10:00:00']);
        DB::insert($insertSql, [1, 49.50, 'pending', '2026-01-11 11:30:00']);
        DB::insert($insertSql, [2, 12.00, 'pending', '2026-01-12 09:15:00']);

        DB::update('UPDATE fixture_orders SET status = ? WHERE user_id = ?', ['shipped', 1]);

        $userOrders = DB::select('SELECT id, user_id, amount, status FROM fixture_orders WHERE user_id = ? ORDER BY id', [
            1,
        ]);

        $joined = DB::select(
            'SELECT o.id AS order_id, o.amount, o.status, u.name AS user_name'
            . ' FROM fixture_orders o INNER JOIN test_users u ON o.user_id = u.id'
            . ' ORDER BY o.id',
        );

        $totals = DB::select('SELECT status, COUNT(*) AS cnt, SUM(amount) AS total'
        . ' FROM fixture_orders GROUP BY status ORDER BY status');

        $top = DB::select('SELECT id, amount, status FROM fixture_orders'
        . ' WHERE status LIKE ? AND amount > ?'
        . ' ORDER BY amount DESC LIMIT 5', ['ship%', 10.0]);

        DB::beginTransaction();

        try {
            DB::insert($insertSql, [2, 5.00, 'confirmed', '2026-01-12 12:00:00']);
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        DB::beginTransaction();
        DB::insert($insertSql, [1, 999.99, 'cancelled', '2026-01-13 12:00:00']);
        DB::rollBack();

        DB::delete('DELETE FROM fixture_orders WHERE status = ?', ['pending']);

        $finalCountRow = DB::select('SELECT COUNT(*) AS cnt FROM fixture_orders');
        $finalCount = (int) ($finalCountRow[0]->cnt ?? 0);

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
