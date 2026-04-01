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

    public function testGetTablesEmpty(): void
    {
        $provider = $this->createMock(SchemaProviderInterface::class);
        $provider->expects($this->once())->method('getTables')->willReturn([]);

        $controller = $this->createController($provider);
        $response = $controller->getTables($this->get());

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame([], $data);
    }

    public function testGetTable(): void
    {
        $tableData = [
            'table' => 'users',
            'primaryKeys' => ['id'],
            'columns' => [],
            'records' => [['id' => 1, 'name' => 'Alice']],
            'totalCount' => 1,
            'limit' => SchemaProviderInterface::DEFAULT_LIMIT,
            'offset' => 0,
        ];

        $provider = $this->createMock(SchemaProviderInterface::class);
        $provider
            ->expects($this->once())
            ->method('getTable')
            ->with('users', SchemaProviderInterface::DEFAULT_LIMIT, 0)
            ->willReturn($tableData);

        $controller = $this->createController($provider);
        $request = $this->get();
        $request = $request->withAttribute('name', 'users');
        $response = $controller->getTable($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testGetTableUsesDefaultLimitFromInterface(): void
    {
        $provider = $this->createMock(SchemaProviderInterface::class);
        $provider
            ->expects($this->once())
            ->method('getTable')
            ->with('users', SchemaProviderInterface::DEFAULT_LIMIT, 0)
            ->willReturn([
                'table' => 'users',
                'records' => [],
                'totalCount' => 0,
            ]);

        $controller = $this->createController($provider);
        $request = $this->get();
        $request = $request->withAttribute('name', 'users');
        $controller->getTable($request);
    }

    public function testGetTableWithPagination(): void
    {
        $provider = $this->createMock(SchemaProviderInterface::class);
        $provider
            ->expects($this->once())
            ->method('getTable')
            ->with('orders', SchemaProviderInterface::DEFAULT_LIMIT, 100)
            ->willReturn([
                'table' => 'orders',
                'records' => [],
                'totalCount' => 200,
                'limit' => SchemaProviderInterface::DEFAULT_LIMIT,
                'offset' => 100,
            ]);

        $controller = $this->createController($provider);
        $request = $this->get(['limit' => (string) SchemaProviderInterface::DEFAULT_LIMIT, 'offset' => '100']);
        $request = $request->withAttribute('name', 'orders');
        $response = $controller->getTable($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testGetTableLimitCappedAt1000(): void
    {
        $provider = $this->createMock(SchemaProviderInterface::class);
        $provider
            ->expects($this->once())
            ->method('getTable')
            ->with('big', 1000, 0)
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

    public function testGetTableLimitMinimumIsOne(): void
    {
        $provider = $this->createMock(SchemaProviderInterface::class);
        $provider
            ->expects($this->once())
            ->method('getTable')
            ->with('t', 1, 0)
            ->willReturn([
                'table' => 't',
                'records' => [],
                'totalCount' => 0,
            ]);

        $controller = $this->createController($provider);
        $request = $this->get(['limit' => '0']);
        $request = $request->withAttribute('name', 't');
        $controller->getTable($request);
    }

    public function testGetTableNegativeLimitClampedToOne(): void
    {
        $provider = $this->createMock(SchemaProviderInterface::class);
        $provider
            ->expects($this->once())
            ->method('getTable')
            ->with('t', 1, 0)
            ->willReturn([
                'table' => 't',
                'records' => [],
                'totalCount' => 0,
            ]);

        $controller = $this->createController($provider);
        $request = $this->get(['limit' => '-10']);
        $request = $request->withAttribute('name', 't');
        $controller->getTable($request);
    }

    public function testGetTableNegativeOffsetClampedToZero(): void
    {
        $provider = $this->createMock(SchemaProviderInterface::class);
        $provider
            ->expects($this->once())
            ->method('getTable')
            ->with('t', SchemaProviderInterface::DEFAULT_LIMIT, 0)
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

    public function testGetTableWithCustomLimit(): void
    {
        $provider = $this->createMock(SchemaProviderInterface::class);
        $provider
            ->expects($this->once())
            ->method('getTable')
            ->with('items', 25, 0)
            ->willReturn([
                'table' => 'items',
                'records' => [],
                'totalCount' => 0,
            ]);

        $controller = $this->createController($provider);
        $request = $this->get(['limit' => '25']);
        $request = $request->withAttribute('name', 'items');
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
        $data = $this->responseData($response);
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('SQL query is required', $data['error']);
    }

    public function testExplainMissingSqlReturns400(): void
    {
        $controller = $this->createController();
        $response = $controller->explain($this->post([]));

        $this->assertSame(400, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertArrayHasKey('error', $data);
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

    public function testExplainWithParams(): void
    {
        $provider = $this->createMock(SchemaProviderInterface::class);
        $provider
            ->expects($this->once())
            ->method('explainQuery')
            ->with('SELECT * FROM users WHERE id = ?', [42], false)
            ->willReturn([['id' => 1, 'detail' => 'INDEX SCAN']]);

        $controller = $this->createController($provider);
        $response = $controller->explain($this->post([
            'sql' => 'SELECT * FROM users WHERE id = ?',
            'params' => [42],
        ]));

        $this->assertSame(200, $response->getStatusCode());
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
        $data = $this->responseData($response);
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('SQL query is required', $data['error']);
    }

    public function testQueryMissingSqlReturns400(): void
    {
        $controller = $this->createController();
        $response = $controller->query($this->post([]));

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

    public function testQueryWithoutParams(): void
    {
        $provider = $this->createMock(SchemaProviderInterface::class);
        $provider
            ->expects($this->once())
            ->method('executeQuery')
            ->with('SELECT 1', [])
            ->willReturn([['1' => 1]]);

        $controller = $this->createController($provider);
        $response = $controller->query($this->post(['sql' => 'SELECT 1']));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testQueryReturnsEmptyResults(): void
    {
        $provider = $this->createMock(SchemaProviderInterface::class);
        $provider->expects($this->once())->method('executeQuery')->willReturn([]);

        $controller = $this->createController($provider);
        $response = $controller->query($this->post(['sql' => 'SELECT * FROM empty_table']));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame([], $data);
    }

    public function testExplainWithDefaultParams(): void
    {
        $provider = $this->createMock(SchemaProviderInterface::class);
        $provider
            ->expects($this->once())
            ->method('explainQuery')
            ->with('SELECT 1', [], false)
            ->willReturn([['detail' => 'result']]);

        $controller = $this->createController($provider);
        // Post with only sql, no params or analyze
        $response = $controller->explain($this->post(['sql' => 'SELECT 1']));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testGetTableWithZeroOffset(): void
    {
        $provider = $this->createMock(SchemaProviderInterface::class);
        $provider
            ->expects($this->once())
            ->method('getTable')
            ->with('data', 10, 0)
            ->willReturn(['table' => 'data', 'records' => [], 'totalCount' => 0]);

        $controller = $this->createController($provider);
        $request = $this->get(['limit' => '10', 'offset' => '0']);
        $request = $request->withAttribute('name', 'data');
        $controller->getTable($request);
    }

    public function testQueryWithParams(): void
    {
        $provider = $this->createMock(SchemaProviderInterface::class);
        $provider
            ->expects($this->once())
            ->method('executeQuery')
            ->with('SELECT * FROM users WHERE id = ?', [1])
            ->willReturn([['id' => 1, 'name' => 'Test']]);

        $controller = $this->createController($provider);
        $response = $controller->query($this->post(['sql' => 'SELECT * FROM users WHERE id = ?', 'params' => [1]]));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame('Test', $data[0]['name']);
    }

    public function testExplainAnalyzeTrue(): void
    {
        $provider = $this->createMock(SchemaProviderInterface::class);
        $provider
            ->expects($this->once())
            ->method('explainQuery')
            ->with('SELECT * FROM t', [], true)
            ->willReturn([['plan' => 'analyzed']]);

        $controller = $this->createController($provider);
        $response = $controller->explain($this->post(['sql' => 'SELECT * FROM t', 'analyze' => true]));

        $this->assertSame(200, $response->getStatusCode());
    }
}
