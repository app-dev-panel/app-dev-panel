<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Elasticsearch;

class NullElasticsearchProvider implements ElasticsearchProviderInterface
{
    public function getHealth(): array
    {
        return [
            'status' => 'unavailable',
            'clusterName' => '',
            'numberOfNodes' => 0,
            'numberOfDataNodes' => 0,
            'activePrimaryShards' => 0,
            'activeShards' => 0,
            'unassignedShards' => 0,
        ];
    }

    public function getIndices(): array
    {
        return [];
    }

    public function getIndex(string $name): array
    {
        return [
            'name' => $name,
            'mappings' => [],
            'settings' => [],
            'stats' => [],
        ];
    }

    public function search(
        string $index,
        array $query,
        int $limit = ElasticsearchProviderInterface::DEFAULT_LIMIT,
        int $offset = 0,
    ): array {
        return [
            'hits' => [],
            'total' => 0,
            'took' => 0,
        ];
    }

    public function executeQuery(string $method, string $endpoint, array $body = []): array
    {
        return [];
    }
}
