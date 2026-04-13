<?php

declare(strict_types=1);

namespace AppDevPanel\McpServer\Tests\Unit\Tool\Inspector;

use AppDevPanel\McpServer\Inspector\InspectorInterface;
use AppDevPanel\McpServer\Tool\Inspector\InspectDatabaseSchemaTool;
use PHPUnit\Framework\TestCase;

final class InspectDatabaseSchemaToolTest extends TestCase
{
    public function testGetName(): void
    {
        $client = $this->createMock(InspectorInterface::class);
        $tool = new InspectDatabaseSchemaTool($client);

        $this->assertSame('inspect_database_schema', $tool->getName());
    }

    public function testGetDescription(): void
    {
        $client = $this->createMock(InspectorInterface::class);
        $tool = new InspectDatabaseSchemaTool($client);

        $this->assertNotEmpty($tool->getDescription());
    }

    public function testGetInputSchema(): void
    {
        $client = $this->createMock(InspectorInterface::class);
        $tool = new InspectDatabaseSchemaTool($client);

        $schema = $tool->getInputSchema();

        $this->assertSame('object', $schema['type']);
        $this->assertArrayHasKey('table', $schema['properties']);
        $this->assertArrayHasKey('filter', $schema['properties']);
    }

    public function testListTablesSuccess(): void
    {
        $client = $this->createMock(InspectorInterface::class);
        $client
            ->method('get')
            ->with('/table', [])
            ->willReturn([
                'success' => true,
                'data' => [
                    ['name' => 'users', 'rows' => 150, 'size' => '64 KB'],
                    ['name' => 'orders', 'rows' => 5000, 'size' => '2 MB'],
                ],
                'error' => null,
            ]);

        $tool = new InspectDatabaseSchemaTool($client);
        $result = $tool->execute([]);

        $this->assertArrayNotHasKey('isError', $result);
        $text = $result['content'][0]['text'];
        $this->assertStringContainsString('Database Tables (2)', $text);
        $this->assertStringContainsString('users', $text);
        $this->assertStringContainsString('orders', $text);
        $this->assertStringContainsString('5000', $text);
    }

    public function testListTablesWithFilter(): void
    {
        $client = $this->createMock(InspectorInterface::class);
        $client
            ->method('get')
            ->willReturn([
                'success' => true,
                'data' => [
                    ['name' => 'users', 'rows' => 150, 'size' => '64 KB'],
                    ['name' => 'user_roles', 'rows' => 10, 'size' => '4 KB'],
                    ['name' => 'orders', 'rows' => 5000, 'size' => '2 MB'],
                ],
                'error' => null,
            ]);

        $tool = new InspectDatabaseSchemaTool($client);
        $result = $tool->execute(['filter' => 'user']);

        $text = $result['content'][0]['text'];
        $this->assertStringContainsString('Database Tables (2)', $text);
        $this->assertStringContainsString('users', $text);
        $this->assertStringContainsString('user_roles', $text);
        $this->assertStringNotContainsString('orders', $text);
    }

    public function testListTablesEmpty(): void
    {
        $client = $this->createMock(InspectorInterface::class);
        $client->method('get')->willReturn(['success' => true, 'data' => [], 'error' => null]);

        $tool = new InspectDatabaseSchemaTool($client);
        $result = $tool->execute([]);

        $this->assertStringContainsString('No database tables found', $result['content'][0]['text']);
    }

    public function testViewTableDetail(): void
    {
        $client = $this->createMock(InspectorInterface::class);
        $client
            ->method('get')
            ->with('/table/users', ['limit' => '0'])
            ->willReturn([
                'success' => true,
                'data' => [
                    'table' => 'users',
                    'totalCount' => 150,
                    'primaryKeys' => ['id'],
                    'columns' => [
                        [
                            'name' => 'id',
                            'type' => 'int',
                            'allowNull' => false,
                            'defaultValue' => null,
                            'isPrimaryKey' => true,
                        ],
                        [
                            'name' => 'email',
                            'type' => 'varchar(255)',
                            'allowNull' => false,
                            'defaultValue' => null,
                            'isPrimaryKey' => false,
                        ],
                        [
                            'name' => 'name',
                            'type' => 'varchar(100)',
                            'allowNull' => true,
                            'defaultValue' => null,
                            'isPrimaryKey' => false,
                        ],
                    ],
                    'records' => [],
                ],
                'error' => null,
            ]);

        $tool = new InspectDatabaseSchemaTool($client);
        $result = $tool->execute(['table' => 'users']);

        $this->assertArrayNotHasKey('isError', $result);
        $text = $result['content'][0]['text'];
        $this->assertStringContainsString('Table: users', $text);
        $this->assertStringContainsString('Total rows', $text);
        $this->assertStringContainsString('150', $text);
        $this->assertStringContainsString('Primary key', $text);
        $this->assertStringContainsString('email', $text);
        $this->assertStringContainsString('varchar(255)', $text);
        $this->assertStringContainsString('YES', $text); // nullable for 'name'
    }

    public function testViewTableNotFound(): void
    {
        $client = $this->createMock(InspectorInterface::class);
        $client->method('get')->willReturn(['success' => false, 'data' => null, 'error' => 'Table not found']);

        $tool = new InspectDatabaseSchemaTool($client);
        $result = $tool->execute(['table' => 'nonexistent']);

        $this->assertTrue($result['isError']);
    }

    public function testConnectionError(): void
    {
        $client = $this->createMock(InspectorInterface::class);
        $client->method('get')->willReturn(['success' => false, 'data' => null, 'error' => 'Connection refused']);

        $tool = new InspectDatabaseSchemaTool($client);
        $result = $tool->execute([]);

        $this->assertTrue($result['isError']);
        $this->assertStringContainsString('Connection refused', $result['content'][0]['text']);
    }

    public function testViewTableWithIndexes(): void
    {
        $client = $this->createMock(InspectorInterface::class);
        $client
            ->method('get')
            ->willReturn([
                'success' => true,
                'data' => [
                    'table' => 'users',
                    'totalCount' => 10,
                    'primaryKeys' => ['id'],
                    'columns' => [
                        [
                            'name' => 'id',
                            'type' => 'int',
                            'allowNull' => false,
                            'defaultValue' => null,
                            'isPrimaryKey' => true,
                        ],
                    ],
                    'indexes' => [
                        ['name' => 'idx_email', 'columns' => ['email'], 'isUnique' => true],
                        ['name' => 'idx_created', 'columns' => ['created_at'], 'isUnique' => false],
                    ],
                    'records' => [],
                ],
                'error' => null,
            ]);

        $tool = new InspectDatabaseSchemaTool($client);
        $result = $tool->execute(['table' => 'users']);

        $text = $result['content'][0]['text'];
        $this->assertStringContainsString('Indexes', $text);
        $this->assertStringContainsString('idx_email', $text);
        $this->assertStringContainsString('unique', $text);
    }

    public function testServiceParameter(): void
    {
        $client = $this->createMock(InspectorInterface::class);
        $client
            ->expects($this->once())
            ->method('get')
            ->with('/table', ['service' => 'api-service'])
            ->willReturn(['success' => true, 'data' => [], 'error' => null]);

        $tool = new InspectDatabaseSchemaTool($client);
        $tool->execute(['service' => 'api-service']);
    }

    public function testFilterNoMatches(): void
    {
        $client = $this->createMock(InspectorInterface::class);
        $client
            ->method('get')
            ->willReturn([
                'success' => true,
                'data' => [['name' => 'users', 'rows' => 10, 'size' => '4 KB']],
                'error' => null,
            ]);

        $tool = new InspectDatabaseSchemaTool($client);
        $result = $tool->execute(['filter' => 'nonexistent']);

        $this->assertStringContainsString('No tables matching', $result['content'][0]['text']);
    }
}
