<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus\Scheduler;

use DateTimeImmutable;
use PDO;
use Symfony\Component\Uid\Uuid;

final readonly class ScheduleRegistry
{
    public function __construct(
        private PDO $pdo,
    ) {}

    public function create(string $name, string $cron, string $messageType, array $messagePayload = []): string
    {
        $cronExpr = new CronExpression($cron);
        $id = Uuid::v7()->toRfc4122();
        $nextRun = $cronExpr->nextRunAfter(new DateTimeImmutable());

        $stmt = $this->pdo->prepare(<<<'SQL'
            INSERT INTO schedules (id, name, cron, message_type, message_payload, enabled, next_run_at)
            VALUES (:id, :name, :cron, :message_type, :message_payload, 1, :next_run_at)
            SQL);
        $stmt->execute([
            'id' => $id,
            'name' => $name,
            'cron' => $cron,
            'message_type' => $messageType,
            'message_payload' => json_encode($messagePayload, JSON_THROW_ON_ERROR),
            'next_run_at' => $nextRun->format('Y-m-d H:i:s'),
        ]);

        return $id;
    }

    public function delete(string $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM schedules WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public function toggle(string $id, bool $enabled): bool
    {
        $stmt = $this->pdo->prepare('UPDATE schedules SET enabled = :enabled WHERE id = :id');
        $stmt->execute(['id' => $id, 'enabled' => $enabled ? 1 : 0]);

        return $stmt->rowCount() > 0;
    }

    public function list(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM schedules ORDER BY name ASC');

        return array_map(static fn(array $row): array => [
            'id' => $row['id'],
            'name' => $row['name'],
            'cron' => $row['cron'],
            'message_type' => $row['message_type'],
            'message_payload' => json_decode($row['message_payload'], true, 512, JSON_THROW_ON_ERROR),
            'enabled' => (bool) $row['enabled'],
            'last_run_at' => $row['last_run_at'],
            'next_run_at' => $row['next_run_at'],
        ], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Find schedules that are due and return them. Updates last_run_at and next_run_at.
     */
    public function findDue(): array
    {
        $now = new DateTimeImmutable();
        $stmt = $this->pdo->prepare(<<<'SQL'
            SELECT * FROM schedules
            WHERE enabled = 1
              AND next_run_at IS NOT NULL
              AND next_run_at <= :now
            SQL);
        $stmt->execute(['now' => $now->format('Y-m-d H:i:s')]);

        $due = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $cronExpr = new CronExpression($row['cron']);
            $nextRun = $cronExpr->nextRunAfter($now);

            $update = $this->pdo->prepare(<<<'SQL'
                UPDATE schedules SET last_run_at = :last_run, next_run_at = :next_run WHERE id = :id
                SQL);
            $update->execute([
                'id' => $row['id'],
                'last_run' => $now->format('Y-m-d H:i:s'),
                'next_run' => $nextRun->format('Y-m-d H:i:s'),
            ]);

            $due[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'message_type' => $row['message_type'],
                'message_payload' => json_decode($row['message_payload'], true, 512, JSON_THROW_ON_ERROR),
            ];
        }

        return $due;
    }
}
