<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Controller;

use AppDevPanel\Api\Inspector\Controller\ElasticsearchController;
use AppDevPanel\Api\Inspector\Elasticsearch\ElasticsearchProviderInterface;

final class ElasticsearchControllerTest extends ControllerTestCase
{
    private function createController(?ElasticsearchProviderInterface $provider = null): ElasticsearchController
    {
        return new ElasticsearchController(
            $this->createResponseFactory(),
            $provider ?? $this->createMock(ElasticsearchProviderInterface::class),
        );
    }

    public function testHealth(): void
    {
        $health = [
            'status' => 'green',
            'clusterName' => 'test-cluster',
            'numberOfNodes' => 3,
            'numberOfDataNodes' => 2,
            'activePrimaryShards' => 10,
            'activeShards' => 20,
            'unassignedShards' => 0,
        ];
        $indices = [
            [
                'name' => 'users',
                'health' => 'green',
                'status' => 'open',
                'docsCount' => 100,
                'storeSize' => '1mb',
                'primaryShards' => 1,
                'replicas' => 1,
            ],
        ];

        $provider = $this->createMock(ElasticsearchProviderInterface::class);
        $provider->expects($this->once())->method('getHealth')->willReturn($health);
        $provider->expects($this->once())->method('getIndices')->willReturn($indices);

        $controller = $this->createController($provider);
        $response = $controller->health($this->get());

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame('green', $data['health']['status']);
        $this->assertCount(1, $data['indices']);
    }

    public function testGetIndex(): void
    {
        $indexData = [
            'name' => 'users',
            'mappings' => ['properties' => ['name' => ['type' => 'text']]],
            'settings' => ['number_of_shards' => 1],
            'stats' => ['docs' => ['count' => 100]],
        ];

        $provider = $this->createMock(ElasticsearchProviderInterface::class);
        $provider->expects($this->once())->method('getIndex')->with('users')->willReturn($indexData);

        $controller = $this->createController($provider);
        $request = $this->get();
        $request = $request->withAttribute('name', 'users');
        $response = $controller->getIndex($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame('users', $data['name']);
    }

    public function testSearch(): void
    {
        $searchResult = [
            'hits' => [['_id' => '1', '_source' => ['name' => 'Alice']]],
            'total' => 1,
            'took' => 5,
        ];

        $provider = $this->createMock(ElasticsearchProviderInterface::class);
        $provider
            ->expects($this->once())
            ->method('search')
            ->with('users', ['match_all' => ['boost' => 1]], 50, 0)
            ->willReturn($searchResult);

        $controller = $this->createController($provider);
        $response = $controller->search($this->post([
            'index' => 'users',
            'query' => ['match_all' => ['boost' => 1]],
        ]));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame(1, $data['total']);
    }

    public function testSearchEmptyIndexReturns400(): void
    {
        $controller = $this->createController();
        $response = $controller->search($this->post(['index' => '']));

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testSearchExceptionReturns500(): void
    {
        $provider = $this->createMock(ElasticsearchProviderInterface::class);
        $provider->method('search')->willThrowException(new \RuntimeException('Connection refused'));

        $controller = $this->createController($provider);
        $response = $controller->search($this->post(['index' => 'users', 'query' => []]));

        $this->assertSame(500, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame('Connection refused', $data['error']);
    }

    public function testQuery(): void
    {
        $result = ['acknowledged' => true];

        $provider = $this->createMock(ElasticsearchProviderInterface::class);
        $provider
            ->expects($this->once())
            ->method('executeQuery')
            ->with('GET', '/_cluster/health', [])
            ->willReturn($result);

        $controller = $this->createController($provider);
        $response = $controller->query($this->post([
            'method' => 'GET',
            'endpoint' => '/_cluster/health',
        ]));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testQueryMissingMethodReturns400(): void
    {
        $controller = $this->createController();
        $response = $controller->query($this->post(['method' => '', 'endpoint' => '/test']));

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testQueryMissingEndpointReturns400(): void
    {
        $controller = $this->createController();
        $response = $controller->query($this->post(['method' => 'GET', 'endpoint' => '']));

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testQueryExceptionReturns500(): void
    {
        $provider = $this->createMock(ElasticsearchProviderInterface::class);
        $provider->method('executeQuery')->willThrowException(new \RuntimeException('Timeout'));

        $controller = $this->createController($provider);
        $response = $controller->query($this->post(['method' => 'GET', 'endpoint' => '/_cat/indices']));

        $this->assertSame(500, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame('Timeout', $data['error']);
    }
}
