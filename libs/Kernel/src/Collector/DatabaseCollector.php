<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

use Throwable;

use function array_values;
use function count;

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
    use DuplicateDetectionTrait;

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
        if (!$this->isActive() || !array_key_exists($id, $this->queries)) {
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
        if (!$this->isActive() || !array_key_exists($id, $this->queries)) {
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
    public function logQuery(QueryRecord $record): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->queries[] = [
            'position' => $this->position++,
            'transactionId' => $this->currentTransactionId,
            'sql' => $record->sql,
            'rawSql' => $record->rawSql,
            'params' => $record->params,
            'line' => $record->line,
            'status' => 'success',
            'actions' => [
                ['action' => 'query.start', 'time' => $record->startTime],
                ['action' => 'query.end', 'time' => $record->endTime],
            ],
            'rowsNumber' => $record->rowsNumber,
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

    public function collectTransactionEnd(string $status, string $line): void
    {
        if (!$this->isActive() || !array_key_exists($this->currentTransactionId, $this->transactions)) {
            return;
        }

        $this->transactions[$this->currentTransactionId]['status'] = $status;
        $this->transactions[$this->currentTransactionId]['actions'][] = [
            'action' => 'transaction.' . $status,
            'line' => $line,
            'time' => microtime(true),
        ];
        ++$this->currentTransactionId;
    }

    public function getCollected(): array
    {
        $queries = array_values($this->queries);

        return [
            'queries' => $queries,
            'transactions' => $this->transactions,
            'duplicates' => $this->detectDuplicates($queries, static fn(array $query) => $query['rawSql'] !== ''
                ? $query['rawSql']
                : $query['sql']),
        ];
    }

    public function getSummary(): array
    {
        $queries = array_values($this->queries);
        $duplicates = $this->detectDuplicates($queries, static fn(array $query) => $query['rawSql'] !== ''
            ? $query['rawSql']
            : $query['sql']);

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
                'duplicateGroups' => count($duplicates['groups']),
                'totalDuplicatedCount' => $duplicates['totalDuplicatedCount'],
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
