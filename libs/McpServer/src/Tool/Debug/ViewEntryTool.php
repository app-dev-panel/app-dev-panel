<?php

declare(strict_types=1);

namespace AppDevPanel\McpServer\Tool\Debug;

use AppDevPanel\Kernel\Storage\StorageInterface;
use AppDevPanel\McpServer\Tool\ToolInterface;
use AppDevPanel\McpServer\Tool\ToolResultTrait;

final class ViewEntryTool implements ToolInterface
{
    use ToolResultTrait;

    public function __construct(
        private readonly StorageInterface $storage,
    ) {}

    public function getName(): string
    {
        return 'view_debug_entry';
    }

    public function getDescription(): string
    {
        return 'View full collector data for a specific debug entry. Optionally filter by collector name (e.g., "log", "database", "exception", "request", "event", "timeline").';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'string',
                    'description' => 'Debug entry ID (from list_debug_entries)',
                ],
                'collector' => [
                    'type' => 'string',
                    'description' => 'Filter to a specific collector by short name (e.g., "log", "database", "exception") or FQCN',
                ],
            ],
            'required' => ['id'],
            'additionalProperties' => false,
        ];
    }

    public function execute(array $arguments): array
    {
        $id = $arguments['id'] ?? '';
        $collectorFilter = $arguments['collector'] ?? null;

        $allData = $this->storage->read(StorageInterface::TYPE_DATA, $id);

        if (!array_key_exists($id, $allData)) {
            return self::error(sprintf('Debug entry "%s" not found.', $id));
        }

        $data = $allData[$id];

        if ($collectorFilter !== null && $collectorFilter !== '') {
            $data = $this->filterByCollector($data, $collectorFilter);

            if ($data === []) {
                return self::error(sprintf(
                    'Collector "%s" not found in entry "%s". Available: %s',
                    $collectorFilter,
                    $id,
                    implode(', ', array_map($this->shortName(...), array_keys($allData[$id]))),
                ));
            }
        }

        $output = $this->formatData($id, $data);

        return self::text($output);
    }

    private function filterByCollector(array $data, string $filter): array
    {
        $filterLower = mb_strtolower($filter);
        $result = [];

        foreach ($data as $fqcn => $collectorData) {
            $short = $this->shortName($fqcn);

            if (mb_strtolower($short) === $filterLower || str_contains(mb_strtolower($fqcn), $filterLower)) {
                $result[$fqcn] = $collectorData;
            }
        }

        return $result;
    }

    private function formatData(string $id, array $data): string
    {
        $sections = ["# Debug Entry: {$id}\n"];

        foreach ($data as $fqcn => $collectorData) {
            $name = $this->shortName($fqcn);
            $sections[] = "## {$name}\n";
            $sections[] = $this->formatValue($collectorData, 0);
        }

        return implode("\n", $sections);
    }

    private function formatValue(mixed $value, int $depth): string
    {
        if ($depth > 5) {
            return is_array($value) ? '[...]' : (string) json_encode($value, JSON_UNESCAPED_SLASHES);
        }

        if (!is_array($value)) {
            return (string) json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        if ($value === []) {
            return '[]';
        }

        $json = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $length = mb_strlen($json);

        if ($length > 10_000) {
            return mb_substr($json, 0, 10_000) . "\n... (truncated, {$length} chars total)";
        }

        return $json;
    }

    private function shortName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);

        return end($parts);
    }
}
