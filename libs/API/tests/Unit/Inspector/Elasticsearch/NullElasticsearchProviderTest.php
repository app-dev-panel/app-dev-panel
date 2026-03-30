<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Elasticsearch;

use AppDevPanel\Api\Inspector\Elasticsearch\NullElasticsearchProvider;
use PHPUnit\Framework\TestCase;

final class NullElasticsearchProviderTest extends TestCase
{
    public function testGetHealthReturnsUnavailable(): void
    {
        $provider = new NullElasticsearchProvider();
        $health = $provider->getHealth();

        $this->assertSame('unavailable', $health['status']);
        $this->assertSame('', $health['clusterName']);
        $this->assertSame(0, $health['numberOfNodes']);
    }

    public function testGetIndicesReturnsEmpty(): void
    {
        $provider = new NullElasticsearchProvider();
        $this->assertSame([], $provider->getIndices());
    }

    public function testGetIndexReturnsEmptyStructure(): void
    {
        $provider = new NullElasticsearchProvider();
        $index = $provider->getIndex('users');

        $this->assertSame('users', $index['name']);
        $this->assertSame([], $index['mappings']);
        $this->assertSame([], $index['settings']);
        $this->assertSame([], $index['stats']);
    }

    public function testSearchReturnsEmpty(): void
    {
        $provider = new NullElasticsearchProvider();
        $result = $provider->search('users', ['match_all' => []], 10, 0);

        $this->assertSame([], $result['hits']);
        $this->assertSame(0, $result['total']);
        $this->assertSame(0, $result['took']);
    }

    public function testExecuteQueryReturnsEmpty(): void
    {
        $provider = new NullElasticsearchProvider();
        $this->assertSame([], $provider->executeQuery('GET', '/_cluster/health'));
    }
}
