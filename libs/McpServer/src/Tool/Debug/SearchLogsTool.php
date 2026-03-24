<?php

declare(strict_types=1);

namespace AppDevPanel\McpServer\Tool\Debug;

use AppDevPanel\Kernel\Collector\LogCollector;
use AppDevPanel\Kernel\Storage\StorageInterface;
use AppDevPanel\McpServer\Tool\ToolInterface;
use AppDevPanel\McpServer\Tool\ToolResultTrait;

final class SearchLogsTool implements ToolInterface
{
    use ToolResultTrait;

    public function __construct(
        private readonly StorageInterface $storage,
    ) {}

    public function getName(): string
    {
        return 'search_logs';
    }

    public function getDescription(): string
    {
        return 'Search log messages across all debug entries. Matches against message text and context. Useful for finding errors, warnings, or specific log patterns.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'query' => [
                    'type' => 'string',
                    'description' => 'Search term to match against log messages and context',
                ],
                'level' => [
                    'type' => 'string',
                    'description' => 'Filter by log level: debug, info, notice, warning, error, critical, alert, emergency',
                    'enum' => ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'],
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Maximum number of log entries to return (default: 50)',
                    'default' => 50,
                ],
            ],
            'required' => ['query'],
            'additionalProperties' => false,
        ];
    }

    public function execute(array $arguments): array
    {
        $query = mb_strtolower($arguments['query'] ?? '');
        $level = $arguments['level'] ?? null;
        $limit = (int) ($arguments['limit'] ?? 50);

        if ($query === '') {
            return self::error('Search query cannot be empty.');
        }

        $summaries = $this->storage->read(StorageInterface::TYPE_SUMMARY);
        $entryIds = array_keys(array_reverse($summaries));

        $results = [];
        $collectorFqcn = LogCollector::class;

        foreach ($entryIds as $entryId) {
            if (count($results) >= $limit) {
                break;
            }

            $allData = $this->storage->read(StorageInterface::TYPE_DATA, $entryId);

            if (!array_key_exists($entryId, $allData) || !array_key_exists($collectorFqcn, $allData[$entryId])) {
                continue;
            }

            $logs = $allData[$entryId][$collectorFqcn];

            if (!is_array($logs)) {
                continue;
            }

            foreach ($logs as $log) {
                if (count($results) >= $limit) {
                    break;
                }

                if ($level !== null && ($log['level'] ?? '') !== $level) {
                    continue;
                }

                $haystack = mb_strtolower(
                    ($log['message'] ?? '') . ' ' . json_encode($log['context'] ?? [], JSON_UNESCAPED_SLASHES),
                );

                if (!str_contains($haystack, $query)) {
                    continue;
                }

                $results[] = $this->formatLog($entryId, $log);
            }
        }

        if ($results === []) {
            return self::text(sprintf('No log entries matching "%s" found.', $arguments['query']));
        }

        $header = sprintf("Found %d log entries matching \"%s\":\n\n", count($results), $arguments['query']);

        return self::text($header . implode("\n", $results));
    }

    private function formatLog(string $entryId, array $log): string
    {
        $level = strtoupper($log['level'] ?? 'unknown');
        $message = $log['message'] ?? '';
        $line = $log['line'] ?? '';
        $context = $log['context'] ?? [];
        $contextStr = $context !== []
            ? ' | context: ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            : '';

        return sprintf(
            '- [%s] **%s** %s%s (entry: %s, at: %s)',
            $level,
            $message,
            $contextStr,
            $line !== '' ? " @ {$line}" : '',
            $entryId,
            $line,
        );
    }
}
