<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Storage;

use AppDevPanel\Kernel\Collector\CollectorInterface;

/**
 * Debug data storage responsibility is to store debug data from collectors added
 */
interface StorageInterface
{
    /**
     * @psalm-suppress MissingClassConstType
     */
    final public const TYPE_SUMMARY = 'summary';

    /**
     * @psalm-suppress MissingClassConstType
     */
    final public const TYPE_DATA = 'data';

    /**
     * @psalm-suppress MissingClassConstType
     */
    final public const TYPE_OBJECTS = 'objects';

    /**
     * Add collector to get debug data from
     *
     * @param CollectorInterface $collector collector instance
     */
    public function addCollector(CollectorInterface $collector): void;

    /**
     * Returns collected data from collectors added
     *
     * @return array collected data
     */
    public function getData(): array;

    /**
     * Read all data from storage
     *
     * @param string $type type of data being read. Available types:
     * - {@see TYPE_SUMMARY}
     * - {@see TYPE_DATA}
     * - {@see TYPE_OBJECTS}
     *
     * @return array data from storage
     */
    public function read(string $type, ?string $id = null): array;

    /**
     * Write a debug entry directly to storage without using collectors.
     * Used by the ingestion API for external (non-PHP) data.
     *
     * @param string $id unique debug entry ID
     * @param array $summary summary metadata
     * @param array $data collector data
     * @param array $objects serialized objects (can be empty)
     */
    public function write(string $id, array $summary, array $data, array $objects): void;

    /**
     * Flush data from collectors into storage
     */
    public function flush(): void;

    /**
     * Clear storage data
     */
    public function clear(): void;
}
