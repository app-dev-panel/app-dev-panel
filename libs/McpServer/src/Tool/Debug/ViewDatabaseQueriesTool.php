<?php

declare(strict_types=1);

namespace AppDevPanel\McpServer\Tool\Debug;

use AppDevPanel\Kernel\Collector\DatabaseCollector;
use AppDevPanel\Kernel\Storage\StorageInterface;
use AppDevPanel\McpServer\Tool\ToolInterface;
use AppDevPanel\McpServer\Tool\ToolResultTrait;

final class ViewDatabaseQueriesTool implements ToolInterface
{
    use ToolResultTrait;

    private const float SLOW_THRESHOLD_MS = 100.0;

    public function __construct(
        private readonly StorageInterface $storage,
    ) {}

    public function getName(): string
    {
        return 'view_database_queries';
    }

    public function getDescription(): string
    {
        return 'List SQL queries from a debug entry with timing, parameters, and row counts. Can filter to slow queries only. Useful for finding N+1 problems and slow queries.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'string',
                    'description' => 'Debug entry ID. If omitted, uses the most recent entry.',
                ],
                'slow_only' => [
                    'type' => 'boolean',
                    'description' => 'Only show queries slower than 100ms (default: false)',
                    'default' => false,
                ],
            ],
            'additionalProperties' => false,
        ];
    }

    public function execute(array $arguments): array
    {
        $id = $arguments['id'] ?? null;
        $slowOnly = (bool) ($arguments['slow_only'] ?? false);

        if ($id === null || $id === '') {
            $id = $this->findLatestEntry();

            if ($id === null) {
                return self::text('No debug entries found.');
            }
        }

        $allData = $this->storage->read(StorageInterface::TYPE_DATA, $id);

        if (!array_key_exists($id, $allData)) {
            return self::error(sprintf('Debug entry "%s" not found.', $id));
        }

        $data = $allData[$id];
        $dbData = $data[DatabaseCollector::class] ?? null;

        if ($dbData === null || $dbData === []) {
            return self::text(sprintf('No database queries in entry "%s".', $id));
        }

        $queries = $dbData['queries'] ?? $dbData;

        if (!is_array($queries)) {
            return self::text(sprintf('No database queries in entry "%s".', $id));
        }

        $output = $this->formatQueries($id, $queries, $slowOnly, $dbData);

        return self::text($output);
    }

    private function findLatestEntry(): ?string
    {
        $summaries = $this->storage->read(StorageInterface::TYPE_SUMMARY);
        $entries = array_reverse($summaries, true);
        $id = array_key_first($entries);

        return $id !== null ? (string) $id : null;
    }

    private function formatQueries(string $entryId, array $queries, bool $slowOnly, array $rawData): string
    {
        $sections = ["# Database Queries — Entry: {$entryId}\n"];

        $totalQueries = 0;
        $slowQueries = 0;
        $totalTime = 0.0;
        $formatted = [];

        foreach ($queries as $query) {
            $duration = $this->calculateDuration($query);
            $totalQueries++;
            $totalTime += $duration;

            $isSlow = $duration >= self::SLOW_THRESHOLD_MS;
            if ($isSlow) {
                $slowQueries++;
            }

            if ($slowOnly && !$isSlow) {
                continue;
            }

            $formatted[] = $this->formatQuery($query, $duration, $isSlow);
        }

        $sections[] = sprintf(
            "**Summary**: %d queries, %.1fms total, %d slow (>%.0fms)\n",
            $totalQueries,
            $totalTime,
            $slowQueries,
            self::SLOW_THRESHOLD_MS,
        );

        if (
            array_key_exists('duplicates', $rawData)
            && is_array($rawData['duplicates'])
            && $rawData['duplicates'] !== []
        ) {
            $sections[] = sprintf("**Duplicate groups**: %d (potential N+1)\n", count($rawData['duplicates']));
        }

        if ($formatted === []) {
            $sections[] = $slowOnly ? 'No slow queries found.' : 'No queries found.';
        } else {
            $sections[] = implode("\n\n", $formatted);
        }

        return implode("\n", $sections);
    }

    private function formatQuery(array $query, float $duration, bool $isSlow): string
    {
        $sql = $query['rawSql'] ?? $query['sql'] ?? '?';
        $params = $query['params'] ?? [];
        $rows = $query['rowsNumber'] ?? '?';
        $status = $query['status'] ?? 'unknown';
        $line = $query['line'] ?? '';
        $slowMarker = $isSlow ? ' **[SLOW]**' : '';

        $result = sprintf("### Query #%s%s\n", $query['position'] ?? '?', $slowMarker);
        $result .= "```sql\n{$sql}\n```\n";
        $result .= sprintf('Duration: %.1fms | Rows: %s | Status: %s', $duration, $rows, $status);

        if ($params !== []) {
            $result .= ' | Params: ' . json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        if ($line !== '') {
            $result .= sprintf(' | at: %s', $line);
        }

        return $result;
    }

    private function calculateDuration(array $query): float
    {
        $actions = $query['actions'] ?? [];
        $start = null;
        $end = null;

        foreach ($actions as $action) {
            $time = $action['time'] ?? null;

            if ($time === null) {
                continue;
            }

            if (($action['action'] ?? '') === 'query.start') {
                $start = (float) $time;
            }

            if (in_array($action['action'] ?? '', ['query.end', 'query.error'], true)) {
                $end = (float) $time;
            }
        }

        if ($start !== null && $end !== null) {
            return ($end - $start) * 1000.0;
        }

        return 0.0;
    }
}
