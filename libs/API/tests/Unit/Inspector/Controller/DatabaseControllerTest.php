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
}
