<?php

declare(strict_types=1);

namespace AppDevPanel\McpServer\Tool\Debug;

use AppDevPanel\Kernel\Storage\StorageInterface;
use AppDevPanel\McpServer\Tool\ToolInterface;
use AppDevPanel\McpServer\Tool\ToolResultTrait;

final class ListEntriesTool implements ToolInterface
{
    use ToolResultTrait;

    public function __construct(
        private readonly StorageInterface $storage,
    ) {}

    public function getName(): string
    {
        return 'list_debug_entries';
    }

    public function getDescription(): string
    {
        return 'List recent debug entries with summary info (ID, timestamp, HTTP method, URL, status code, duration, collectors). Use this to find specific debug entries for further inspection.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Maximum number of entries to return (default: 20)',
                    'default' => 20,
                ],
                'filter' => [
                    'type' => 'string',
                    'description' => 'Filter entries by URL path, HTTP method, or status code (e.g., "/api/users", "POST", "500")',
                ],
            ],
            'additionalProperties' => false,
        ];
    }

    public function execute(array $arguments): array
    {
        $limit = (int) ($arguments['limit'] ?? 20);
        $filter = $arguments['filter'] ?? null;

        $summaries = $this->storage->read(StorageInterface::TYPE_SUMMARY);
        $entries = array_values(array_reverse($summaries));

        if ($filter !== null && $filter !== '') {
            $entries = $this->filterEntries($entries, $filter);
        }

        $entries = array_slice($entries, 0, $limit);

        if ($entries === []) {
            return self::text('No debug entries found.');
        }

        $lines = [];
        foreach ($entries as $id => $entry) {
            $lines[] = $this->formatEntry($entry, is_string($id) ? $id : null);
        }

        return self::text(implode("\n\n", $lines));
    }

    private function filterEntries(array $entries, string $filter): array
    {
        $filter = mb_strtolower($filter);

        return array_filter($entries, static function (array $entry) use ($filter): bool {
            $haystack = mb_strtolower(json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            return str_contains($haystack, $filter);
        });
    }

    private function formatEntry(array $entry, ?string $fallbackId): string
    {
        $id = $entry['id'] ?? $fallbackId ?? 'unknown';
        $method = $this->extractNested($entry, ['request', 'web', 'command'], 'method') ?? '?';
        $url =
            $this->extractNested($entry, ['request', 'web'], 'url') ?? $this->extractNested(
                $entry,
                ['request', 'web'],
                'uri',
            ) ?? $this->extractNested($entry, ['command'], 'command') ?? '?';
        $status =
            $this->extractNested($entry, ['request', 'web'], 'statusCode') ?? $this->extractNested(
                $entry,
                ['request', 'web'],
                'status',
            ) ?? '?';
        $collectors = '';
        if (array_key_exists('collectors', $entry)) {
            $collectors = ' | collectors: ' . implode(', ', array_keys($entry['collectors']));
        }

        return sprintf('- **%s** %s %s → %s%s', $id, $method, $url, $status, $collectors);
    }

    private function extractNested(array $data, array $keys, string $field): ?string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data) && array_key_exists($field, $data[$key])) {
                return (string) $data[$key][$field];
            }
        }

        return null;
    }
}
