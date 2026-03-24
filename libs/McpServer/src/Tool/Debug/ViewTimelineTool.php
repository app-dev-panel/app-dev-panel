<?php

declare(strict_types=1);

namespace AppDevPanel\McpServer\Tool\Debug;

use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Storage\StorageInterface;
use AppDevPanel\McpServer\Tool\ToolInterface;
use AppDevPanel\McpServer\Tool\ToolResultTrait;

final class ViewTimelineTool implements ToolInterface
{
    use ToolResultTrait;

    public function __construct(
        private readonly StorageInterface $storage,
    ) {}

    public function getName(): string
    {
        return 'view_timeline';
    }

    public function getDescription(): string
    {
        return 'View the performance timeline for a debug entry — chronological sequence of events from all collectors with timestamps. Useful for understanding request flow and finding bottlenecks.';
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
            ],
            'additionalProperties' => false,
        ];
    }

    public function execute(array $arguments): array
    {
        $id = $arguments['id'] ?? null;

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
        $timelineData = $data[TimelineCollector::class] ?? null;

        if ($timelineData === null || $timelineData === []) {
            return self::text(sprintf('No timeline data in entry "%s".', $id));
        }

        if (!is_array($timelineData)) {
            return self::text(sprintf('No timeline data in entry "%s".', $id));
        }

        $output = $this->formatTimeline($id, $timelineData);

        return self::text($output);
    }

    private function findLatestEntry(): ?string
    {
        $summaries = $this->storage->read(StorageInterface::TYPE_SUMMARY);
        $entries = array_reverse($summaries, true);
        $id = array_key_first($entries);

        return $id !== null ? (string) $id : null;
    }

    private function formatTimeline(string $entryId, array $events): string
    {
        $sections = ["# Timeline — Entry: {$entryId}\n"];
        $sections[] = sprintf("Total events: %d\n", count($events));

        $firstTime = null;

        foreach ($events as $i => $event) {
            if ($i >= 100) {
                $sections[] = sprintf("\n... and %d more events", count($events) - 100);
                break;
            }

            $time = $event[0] ?? null;
            $reference = $event[1] ?? '?';
            $collector = $event[2] ?? '?';
            $extra = $event[3] ?? [];

            if ($firstTime === null && $time !== null) {
                $firstTime = (float) $time;
            }

            $offset =
                $time !== null && $firstTime !== null ? sprintf('+%.1fms', ((float) $time - $firstTime) * 1000) : '?';

            $collectorShort = $this->shortName($collector);
            $extraStr = is_array($extra) && $extra !== []
                ? ' ' . json_encode($extra, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                : '';

            $sections[] = sprintf('- %s [%s] %s%s', $offset, $collectorShort, $reference, $extraStr);
        }

        return implode("\n", $sections);
    }

    private function shortName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);

        return end($parts);
    }
}
