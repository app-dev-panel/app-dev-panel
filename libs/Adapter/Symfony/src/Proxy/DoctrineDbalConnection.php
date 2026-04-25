<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Proxy;

use AppDevPanel\Kernel\Collector\DatabaseCollector;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;

/**
 * DBAL connection wrapper that intercepts prepare(), query(), and exec()
 * to feed query data to DatabaseCollector.
 */
final class DoctrineDbalConnection extends AbstractConnectionMiddleware
{
    public function __construct(
        DriverConnection $connection,
        private readonly DatabaseCollector $collector,
    ) {
        parent::__construct($connection);
    }

    public function prepare(string $sql): Statement
    {
        return new DoctrineDbalStatement(parent::prepare($sql), $this->collector, $sql);
    }

    public function query(string $sql): Result
    {
        $line = $this->getCallerLine();
        $id = uniqid('dbal_', true);

        $this->collector->collectQueryStart($id, $sql, $sql, [], $line);

        try {
            $result = parent::query($sql);
        } catch (\Throwable $e) {
            $this->collector->collectQueryError($id, $e);

            throw $e;
        }

        $this->collector->collectQueryEnd($id, $result->rowCount());

        return $result;
    }

    public function exec(string $sql): int|string
    {
        $line = $this->getCallerLine();
        $id = uniqid('dbal_', true);

        $this->collector->collectQueryStart($id, $sql, $sql, [], $line);

        try {
            $rows = parent::exec($sql);
        } catch (\Throwable $e) {
            $this->collector->collectQueryError($id, $e);

            throw $e;
        }

        $this->collector->collectQueryEnd($id, (int) $rows);

        return $rows;
    }

    public function beginTransaction(): void
    {
        $this->collector->collectTransactionStart(null, $this->getCallerLine());
        parent::beginTransaction();
    }

    public function commit(): void
    {
        parent::commit();
        $this->collector->collectTransactionEnd('commit', $this->getCallerLine());
    }

    public function rollBack(): void
    {
        parent::rollBack();
        $this->collector->collectTransactionEnd('rollback', $this->getCallerLine());
    }

    private function getCallerLine(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);

        foreach ($trace as $frame) {
            if (!isset($frame['file'])) {
                continue;
            }

            if (str_contains($frame['file'], 'vendor/') || str_contains($frame['file'], 'DoctrineDbal')) {
                continue;
            }

            return $frame['file'] . ':' . ($frame['line'] ?? 0);
        }

        return '';
    }
}
