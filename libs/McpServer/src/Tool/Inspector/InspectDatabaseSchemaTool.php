<?php

declare(strict_types=1);

namespace AppDevPanel\McpServer\Tool\Inspector;

use AppDevPanel\McpServer\Inspector\InspectorClient;
use AppDevPanel\McpServer\Tool\ToolInterface;
use AppDevPanel\McpServer\Tool\ToolResultTrait;

/**
 * MCP tool that exposes database schema information (tables, columns, indexes)
 * by querying the live Inspector API.
 */
final class InspectDatabaseSchemaTool implements ToolInterface
{
    use ToolResultTrait;

    public function __construct(
        private readonly InspectorClient $client,
    ) {}

    public function getName(): string
    {
        return 'inspect_database_schema';
    }

    public function getDescription(): string
    {
        return 'View database schema: list tables, view table columns/indexes, or get detailed schema for a specific table. Requires a running application with ADP and a database schema provider installed.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'table' => [
                    'type' => 'string',
                    'description' => 'Table name to inspect. If omitted, lists all tables with summary info.',
                ],
                'filter' => [
                    'type' => 'string',
                    'description' => 'Filter table names by substring (for table listing). Case-insensitive.',
                ],
                'service' => [
                    'type' => 'string',
                    'description' => 'Target service name for multi-app inspection. Omit for local application.',
                ],
            ],
            'additionalProperties' => false,
        ];
    }

    public function execute(array $arguments): array
    {
        $table = $arguments['table'] ?? null;

        if (is_string($table) && $table !== '') {
            return $this->viewTable($table, $arguments['service'] ?? null);
        }

        return $this->listTables($arguments['filter'] ?? null, $arguments['service'] ?? null);
    }

    private function listTables(?string $filter, ?string $service): array
    {
        $query = $service !== null ? ['service' => $service] : [];
        $result = $this->client->get('/table', $query);

        if (!$result['success']) {
            return self::error($result['error'] ?? 'Failed to fetch database tables.');
        }

        $tables = $result['data'];

        if (!is_array($tables) || $tables === []) {
            return self::text('No database tables found. Ensure a SchemaProvider is configured.');
        }

        if (is_string($filter) && $filter !== '') {
            $lower = strtolower($filter);
            $tables = array_values(array_filter(
                $tables,
                static fn(mixed $t): bool => (
                    is_array($t) && str_contains(strtolower((string) ($t['name'] ?? '')), $lower)
                ),
            ));

            if ($tables === []) {
                return self::text(sprintf('No tables matching "%s".', $filter));
            }
        }

        return self::text($this->formatTableList($tables, $filter));
    }

    private function viewTable(string $tableName, ?string $service): array
    {
        $query = ['limit' => '0'];

        if ($service !== null) {
            $query['service'] = $service;
        }

        $result = $this->client->get('/table/' . urlencode($tableName), $query);

        if (!$result['success']) {
            return self::error($result['error'] ?? sprintf('Failed to fetch table "%s".', $tableName));
        }

        $data = $result['data'];

        if (!is_array($data)) {
            return self::error(sprintf('Table "%s" not found.', $tableName));
        }

        return self::text($this->formatTableDetail($tableName, $data));
    }

    private function formatTableList(array $tables, ?string $filter): string
    {
        $title = sprintf('# Database Tables (%d)', count($tables));

        if (is_string($filter) && $filter !== '') {
            $title .= sprintf(' — filtered: "%s"', $filter);
        }

        $lines = [$title, ''];
        $lines[] = '| Table | Rows | Size |';
        $lines[] = '|-------|-----:|------|';

        foreach ($tables as $table) {
            if (!is_array($table)) {
                continue;
            }

            $name = (string) ($table['name'] ?? '—');
            $rows = (string) ($table['rows'] ?? '—');
            $size = (string) ($table['size'] ?? '—');

            $lines[] = sprintf('| `%s` | %s | %s |', $name, $rows, $size);
        }

        $lines[] = '';
        $lines[] = 'Use `table` parameter to view columns and indexes for a specific table.';

        return implode("\n", $lines);
    }

    private function formatTableDetail(string $tableName, array $data): string
    {
        $lines = [sprintf('# Table: %s', $tableName), ''];

        $totalCount = $data['totalCount'] ?? null;
        if ($totalCount !== null) {
            $lines[] = sprintf('**Total rows**: %s', (string) $totalCount);
            $lines[] = '';
        }

        // Primary keys
        $primaryKeys = $data['primaryKeys'] ?? [];
        if (is_array($primaryKeys) && $primaryKeys !== []) {
            $lines[] = sprintf('**Primary key**: %s', implode(', ', array_map(
                static fn(mixed $k): string => '`' . (string) $k . '`',
                $primaryKeys,
            )));
            $lines[] = '';
        }

        // Columns
        $columns = $data['columns'] ?? [];
        if (is_array($columns) && $columns !== []) {
            $lines[] = '## Columns';
            $lines[] = '';
            $lines[] = '| Column | Type | Nullable | Default | PK |';
            $lines[] = '|--------|------|:--------:|---------|:--:|';

            foreach ($columns as $column) {
                if (!is_array($column)) {
                    continue;
                }

                $name = (string) ($column['name'] ?? '—');
                $type = (string) ($column['type'] ?? '—');
                $nullable = $column['allowNull'] ?? false ? 'YES' : 'NO';
                $default = $column['defaultValue'] ?? '—';
                if ($default === null) {
                    $default = 'NULL';
                }
                $pk = $column['isPrimaryKey'] ?? false ? 'YES' : '';

                $lines[] = sprintf('| `%s` | %s | %s | %s | %s |', $name, $type, $nullable, (string) $default, $pk);
            }
        }

        // Indexes (if present in response)
        $indexes = $data['indexes'] ?? [];
        if (is_array($indexes) && $indexes !== []) {
            $lines[] = '';
            $lines[] = '## Indexes';
            $lines[] = '';

            foreach ($indexes as $index) {
                if (!is_array($index)) {
                    continue;
                }

                $indexName = (string) ($index['name'] ?? 'unnamed');
                $indexColumns = is_array($index['columns'] ?? null) ? implode(', ', $index['columns']) : '—';
                $unique = $index['isUnique'] ?? false ? ' (unique)' : '';

                $lines[] = sprintf('- **%s**%s: %s', $indexName, $unique, $indexColumns);
            }
        }

        return implode("\n", $lines);
    }
}
