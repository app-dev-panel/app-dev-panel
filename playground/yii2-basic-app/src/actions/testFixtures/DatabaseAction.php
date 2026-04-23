<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use Throwable;
use yii\base\Action;

/**
 * Exercises a wide mix of SQL operations so DatabaseCollector has meaningful
 * data to display: DDL, inserts, updates, a parameterized select, a JOIN, an
 * aggregate, an IN/LIKE filter, a committed transaction, a rolled-back
 * transaction and a final cleanup delete.
 *
 * Uses a dedicated `fixture_orders` table so the pre-seeded `test_users` rows
 * (created by DatabaseBootstrap) are never modified.
 */
final class DatabaseAction extends Action
{
    public function run(): array
    {
        $db = \Yii::$app->db;

        $db->createCommand(
            'CREATE TABLE IF NOT EXISTS fixture_orders ('
            . 'id INTEGER PRIMARY KEY AUTOINCREMENT, '
            . 'user_id INTEGER NOT NULL, '
            . 'amount REAL NOT NULL, '
            . 'status TEXT NOT NULL, '
            . 'created_at TEXT NOT NULL)',
        )->execute();

        $db->createCommand('DELETE FROM fixture_orders')->execute();

        $insertSql =
            'INSERT INTO fixture_orders (user_id, amount, status, created_at)'
            . ' VALUES (:user_id, :amount, :status, :created_at)';

        $db->createCommand($insertSql, [
            'user_id' => 1,
            'amount' => 19.99,
            'status' => 'pending',
            'created_at' => '2026-01-10 10:00:00',
        ])->execute();

        $db->createCommand($insertSql, [
            'user_id' => 1,
            'amount' => 49.50,
            'status' => 'pending',
            'created_at' => '2026-01-11 11:30:00',
        ])->execute();

        $db->createCommand($insertSql, [
            'user_id' => 2,
            'amount' => 12.00,
            'status' => 'pending',
            'created_at' => '2026-01-12 09:15:00',
        ])->execute();

        $db->createCommand('UPDATE fixture_orders SET status = :status WHERE user_id = :user_id', [
            'status' => 'shipped',
            'user_id' => 1,
        ])->execute();

        $userOrders = $db->createCommand('SELECT id, user_id, amount, status FROM fixture_orders WHERE user_id = :user_id ORDER BY id', [
            'user_id' => 1,
        ])->queryAll();

        $joined = $db->createCommand(
            'SELECT o.id AS order_id, o.amount, o.status, u.name AS user_name'
            . ' FROM fixture_orders o INNER JOIN test_users u ON o.user_id = u.id'
            . ' ORDER BY o.id',
        )->queryAll();

        $totals = $db->createCommand('SELECT status, COUNT(*) AS cnt, SUM(amount) AS total'
        . ' FROM fixture_orders GROUP BY status ORDER BY status')->queryAll();

        $top = $db->createCommand('SELECT id, amount, status FROM fixture_orders'
        . ' WHERE status LIKE :pattern AND amount > :min'
        . ' ORDER BY amount DESC LIMIT 5', ['pattern' => 'ship%', 'min' => 10.0])->queryAll();

        $transaction = $db->beginTransaction();

        try {
            $db->createCommand($insertSql, [
                'user_id' => 2,
                'amount' => 5.00,
                'status' => 'confirmed',
                'created_at' => '2026-01-12 12:00:00',
            ])->execute();
            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        $rollback = $db->beginTransaction();
        $db->createCommand($insertSql, [
            'user_id' => 1,
            'amount' => 999.99,
            'status' => 'cancelled',
            'created_at' => '2026-01-13 12:00:00',
        ])->execute();
        $rollback->rollBack();

        $db->createCommand('DELETE FROM fixture_orders WHERE status = :status', ['status' => 'pending'])->execute();

        $finalCount = (int) $db->createCommand('SELECT COUNT(*) FROM fixture_orders')->queryScalar();

        return [
            'fixture' => 'database:basic',
            'status' => 'ok',
            'final_count' => $finalCount,
            'user_1_orders' => $userOrders,
            'orders_with_user' => $joined,
            'totals_by_status' => $totals,
            'top_shipped' => $top,
        ];
    }
}
