<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

use Throwable;

/**
 * Captures SQL queries and transactions from the application.
 *
 * Supports two usage patterns:
 * - Paired: collectQueryStart() → collectQueryEnd()/collectQueryError() (for proxy-based adapters)
 * - Simple: logQuery() (for event-based adapters that measure timing externally)
 *
 * Transaction tracking: collectTransactionStart() → collectTransactionCommit()/collectTransactionRollback()
 */
final class DatabaseCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    private array $queries = [];
    private array $transactions = [];

    private int $position = 0;
    private int $currentTransactionId = 0;

    public function __construct(
        private readonly TimelineCollector $timelineCollector,
    ) {}

    /**
     * Start tracking a query (paired with collectQueryEnd/collectQueryError).
     */
    public function collectQueryStart(string $id, string $sql, string $rawSql, array $params, string $line): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->queries[$id] = [
            'position' => $this->position++,
            'transactionId' => $this->currentTransactionId,
            'sql' => $sql,
            'rawSql' => $rawSql,
            'params' => $params,
            'line' => $line,
            'status' => 'initialized',
            'actions' => [
                ['action' => 'query.start', 'time' => microtime(true)],
            ],
        ];
    }

    /**
     * Mark a tracked query as completed.
     */
    public function collectQueryEnd(string $id, int $rowsNumber): void
    {
        if (!$this->isActive() || !isset($this->queries[$id])) {
            return;
        }

        $this->queries[$id]['rowsNumber'] = $rowsNumber;
        $this->queries[$id]['status'] = 'success';
        $this->queries[$id]['actions'][] = [
            'action' => 'query.end',
            'time' => microtime(true),
        ];

        $this->timelineCollector->collect($this, count($this->queries));
    }

    /**
     * Mark a tracked query as failed.
     */
    public function collectQueryError(string $id, Throwable $exception): void
    {
        if (!$this->isActive() || !isset($this->queries[$id])) {
            return;
        }

        $this->queries[$id]['exception'] = $exception;
        $this->queries[$id]['status'] = 'error';
        $this->queries[$id]['actions'][] = [
            'action' => 'query.error',
            'time' => microtime(true),
        ];

        $this->timelineCollector->collect($this, count($this->queries));
    }

    /**
     * Log a completed query in one call (for adapters that measure timing externally).
     */
    public function logQuery(
        string $sql,
        string $rawSql,
        array $params,
        string $line,
        float $startTime,
        float $endTime,
        int $rowsNumber = 0,
    ): void {
        if (!$this->isActive()) {
            return;
        }

        $this->queries[] = [
            'position' => $this->position++,
            'transactionId' => $this->currentTransactionId,
            'sql' => $sql,
            'rawSql' => $rawSql,
            'params' => $params,
            'line' => $line,
            'status' => 'success',
            'actions' => [
                ['action' => 'query.start', 'time' => $startTime],
                ['action' => 'query.end', 'time' => $endTime],
            ],
            'rowsNumber' => $rowsNumber,
        ];

        $this->timelineCollector->collect($this, count($this->queries));
    }

    public function collectTransactionStart(?string $isolationLevel, string $line): void
    {
        if (!$this->isActive()) {
            return;
        }

        $id = ++$this->currentTransactionId;
        $this->transactions[$id] = [
            'id' => $id,
            'position' => $this->position++,
            'status' => 'start',
            'line' => $line,
            'level' => $isolationLevel,
            'actions' => [
                ['action' => 'transaction.start', 'time' => microtime(true)],
            ],
        ];
    }

    public function collectTransactionRollback(string $line): void
    {
        if (!$this->isActive() || !isset($this->transactions[$this->currentTransactionId])) {
            return;
        }

        $this->transactions[$this->currentTransactionId]['status'] = 'rollback';
        $this->transactions[$this->currentTransactionId]['actions'][] = [
            'action' => 'transaction.rollback',
            'line' => $line,
            'time' => microtime(true),
        ];
        ++$this->currentTransactionId;
    }

    public function collectTransactionCommit(string $line): void
    {
        if (!$this->isActive() || !isset($this->transactions[$this->currentTransactionId])) {
            return;
        }

        $this->transactions[$this->currentTransactionId]['status'] = 'commit';
        $this->transactions[$this->currentTransactionId]['actions'][] = [
            'action' => 'transaction.commit',
            'line' => $line,
            'time' => microtime(true),
        ];
        ++$this->currentTransactionId;
    }

    public function getCollected(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        return [
            'queries' => $this->queries,
            'transactions' => $this->transactions,
        ];
    }

    public function getSummary(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        return [
            'db' => [
                'queries' => [
                    'error' => count(array_filter(
                        $this->queries,
                        static fn(array $query) => $query['status'] === 'error',
                    )),
                    'total' => count($this->queries),
                ],
                'transactions' => [
                    'error' => count(array_filter(
                        $this->transactions,
                        static fn(array $tx) => $tx['status'] === 'rollback',
                    )),
                    'total' => count($this->transactions),
                ],
            ],
        ];
    }

    protected function reset(): void
    {
        $this->queries = [];
        $this->transactions = [];
        $this->position = 0;
        $this->currentTransactionId = 0;
    }
}
