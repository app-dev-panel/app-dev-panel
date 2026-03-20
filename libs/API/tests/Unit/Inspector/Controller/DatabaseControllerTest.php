<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Controller;

use AppDevPanel\Api\Inspector\Controller\DatabaseController;
use AppDevPanel\Api\Inspector\Database\SchemaProviderInterface;

final class DatabaseControllerTest extends ControllerTestCase
{
    private function createController(?SchemaProviderInterface $schemaProvider = null): DatabaseController
    {
        return new DatabaseController(
            $this->createResponseFactory(),
            $schemaProvider ?? $this->createMock(SchemaProviderInterface::class),
        );
    }

    public function testGetTables(): void
    {
        $tables = [
            ['table' => 'users', 'primaryKeys' => ['id'], 'columns' => [], 'records' => 10],
            ['table' => 'posts', 'primaryKeys' => ['id'], 'columns' => [], 'records' => 5],
        ];

        $provider = $this->createMock(SchemaProviderInterface::class);
        $provider->expects($this->once())->method('getTables')->willReturn($tables);

        $controller = $this->createController($provider);
        $response = $controller->getTables($this->get());

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertCount(2, $data);
        $this->assertSame('users', $data[0]['table']);
    }

    public function testGetTable(): void
    {
        $tableData = [
            'table' => 'users',
            'primaryKeys' => ['id'],
            'columns' => [],
            'records' => [['id' => 1, 'name' => 'Alice']],
            'totalCount' => 1,
            'limit' => 1000,
            'offset' => 0,
        ];

        $provider = $this->createMock(SchemaProviderInterface::class);
        $provider->expects($this->once())->method('getTable')->with('users', 1000, 0)->willReturn($tableData);

        $controller = $this->createController($provider);
        $request = $this->get();
        $request = $request->withAttribute('name', 'users');
        $response = $controller->getTable($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testGetTableWithPagination(): void
    {
        $provider = $this->createMock(SchemaProviderInterface::class);
        $provider
            ->expects($this->once())
            ->method('getTable')
            ->with('orders', 50, 100)
            ->willReturn([
                'table' => 'orders',
                'records' => [],
                'totalCount' => 200,
                'limit' => 50,
                'offset' => 100,
            ]);

        $controller = $this->createController($provider);
        $request = $this->get(['limit' => '50', 'offset' => '100']);
        $request = $request->withAttribute('name', 'orders');
        $response = $controller->getTable($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testGetTableLimitCappedAt10000(): void
    {
        $provider = $this->createMock(SchemaProviderInterface::class);
        $provider
            ->expects($this->once())
            ->method('getTable')
            ->with('big', 10000, 0)
            ->willReturn([
                'table' => 'big',
                'records' => [],
                'totalCount' => 0,
            ]);

        $controller = $this->createController($provider);
        $request = $this->get(['limit' => '999999']);
        $request = $request->withAttribute('name', 'big');
        $controller->getTable($request);
    }

    public function testGetTableNegativeOffsetClampedToZero(): void
    {
        $provider = $this->createMock(SchemaProviderInterface::class);
        $provider
            ->expects($this->once())
            ->method('getTable')
            ->with('t', 1000, 0)
            ->willReturn([
                'table' => 't',
                'records' => [],
                'totalCount' => 0,
            ]);

        $controller = $this->createController($provider);
        $request = $this->get(['offset' => '-5']);
        $request = $request->withAttribute('name', 't');
        $controller->getTable($request);
    }

    public function testExplain(): void
    {
        $plan = [['id' => 1, 'detail' => 'SCAN users']];

        $provider = $this->createMock(SchemaProviderInterface::class);
        $provider
            ->expects($this->once())
            ->method('explainQuery')
            ->with('SELECT * FROM users', [], false)
            ->willReturn($plan);

        $controller = $this->createController($provider);
        $response = $controller->explain($this->post(['sql' => 'SELECT * FROM users']));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertCount(1, $data);
        $this->assertSame('SCAN users', $data[0]['detail']);
    }

    public function testExplainAnalyze(): void
    {
        $plan = [['id' => 1, 'detail' => 'SCAN users (actual rows=10)']];

        $provider = $this->createMock(SchemaProviderInterface::class);
        $provider
            ->expects($this->once())
            ->method('explainQuery')
            ->with('SELECT * FROM users', ['status' => 'active'], true)
            ->willReturn($plan);

        $controller = $this->createController($provider);
        $response = $controller->explain($this->post([
            'sql' => 'SELECT * FROM users',
            'params' => ['status' => 'active'],
            'analyze' => true,
        ]));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertCount(1, $data);
    }

    public function testExplainEmptySqlReturns400(): void
    {
        $controller = $this->createController();
        $response = $controller->explain($this->post(['sql' => '']));

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testExplainExceptionReturns500(): void
    {
        $provider = $this->createMock(SchemaProviderInterface::class);
        $provider->method('explainQuery')->willThrowException(new \RuntimeException('Syntax error'));

        $controller = $this->createController($provider);
        $response = $controller->explain($this->post(['sql' => 'INVALID SQL']));

        $this->assertSame(500, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame('Syntax error', $data['error']);
    }

    public function testQuery(): void
    {
        $rows = [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ];

        $provider = $this->createMock(SchemaProviderInterface::class);
        $provider
            ->expects($this->once())
            ->method('executeQuery')
            ->with('SELECT * FROM users', ['active' => 1])
            ->willReturn($rows);

        $controller = $this->createController($provider);
        $response = $controller->query($this->post(['sql' => 'SELECT * FROM users', 'params' => ['active' => 1]]));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertCount(2, $data);
        $this->assertSame('Alice', $data[0]['name']);
    }

    public function testQueryEmptySqlReturns400(): void
    {
        $controller = $this->createController();
        $response = $controller->query($this->post(['sql' => '']));

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testQueryExceptionReturns500(): void
    {
        $provider = $this->createMock(SchemaProviderInterface::class);
        $provider->method('executeQuery')->willThrowException(new \RuntimeException('Connection refused'));

        $controller = $this->createController($provider);
        $response = $controller->query($this->post(['sql' => 'SELECT 1']));

        $this->assertSame(500, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame('Connection refused', $data['error']);
    }
}
