<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Elasticsearch;

interface ElasticsearchProviderInterface
{
    public const int DEFAULT_LIMIT = 50;

    /**
     * Get cluster health and basic node info.
     *
     * @return array{status: string, clusterName: string, numberOfNodes: int, numberOfDataNodes: int, activePrimaryShards: int, activeShards: int, unassignedShards: int}
     */
    public function getHealth(): array;

    /**
     * List all indices with stats.
     *
     * @return list<array{name: string, health: string, status: string, docsCount: int, storeSize: string, primaryShards: int, replicas: int}>
     */
    public function getIndices(): array;

    /**
     * Get single index details: mappings, settings, and stats.
     *
     * @return array{name: string, mappings: array, settings: array, stats: array}
     */
    public function getIndex(string $name): array;

    /**
     * Execute a search query against an index.
     *
     * @return array{hits: list<array>, total: int, took: int}
     */
    public function search(string $index, array $query, int $limit = self::DEFAULT_LIMIT, int $offset = 0): array;

    /**
     * Execute a raw Elasticsearch query.
     *
     * @return array Raw Elasticsearch response
     */
    public function executeQuery(string $method, string $endpoint, array $body = []): array;
}
