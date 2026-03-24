<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

use function array_keys;
use function array_values;
use function count;

/**
 * Detects duplicate items in collector data by grouping them by a key.
 *
 * Used by ViewCollector (file path), DatabaseCollector (SQL), and QueueCollector (message class)
 * to detect N+1 patterns where the same operation is repeated many times.
 */
trait DuplicateDetectionTrait
{
    private const int DUPLICATE_THRESHOLD = 2;

    /**
     * Detect duplicate groups from a list of items.
     *
     * @param array<int, array> $items Items to group.
     * @param callable(array): string $keyExtractor Extracts the grouping key from an item.
     * @return array{groups: array<int, array{key: string, count: int, indices: int[]}>, totalDuplicatedCount: int}
     */
    private function detectDuplicates(array $items, callable $keyExtractor): array
    {
        /** @var array<string, int[]> $grouped */
        $grouped = [];

        foreach ($items as $index => $item) {
            $key = $keyExtractor($item);
            $grouped[$key][] = $index;
        }

        $groups = [];
        $totalDuplicatedCount = 0;

        foreach ($grouped as $key => $indices) {
            if (count($indices) > self::DUPLICATE_THRESHOLD) {
                $groups[] = [
                    'key' => $key,
                    'count' => count($indices),
                    'indices' => array_values($indices),
                ];
                $totalDuplicatedCount += count($indices);
            }
        }

        return [
            'groups' => $groups,
            'totalDuplicatedCount' => $totalDuplicatedCount,
        ];
    }
}
