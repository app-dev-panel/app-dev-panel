<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Unit\Inspector;

use AppDevPanel\Adapter\Yii2\Inspector\NullSchemaProvider;
use AppDevPanel\Api\Inspector\Database\SchemaProviderInterface;
use PHPUnit\Framework\TestCase;

final class NullSchemaProviderTest extends TestCase
{
    public function testImplementsSchemaProviderInterface(): void
    {
        $provider = new NullSchemaProvider();

        $this->assertInstanceOf(SchemaProviderInterface::class, $provider);
    }

    public function testGetTablesReturnsEmptyArray(): void
    {
        $provider = new NullSchemaProvider();

        $this->assertSame([], $provider->getTables());
    }

    public function testGetTableReturnsEmptyStructure(): void
    {
        $provider = new NullSchemaProvider();

        $result = $provider->getTable('users', 100, 0);

        $this->assertSame('users', $result['table']);
        $this->assertSame([], $result['primaryKeys']);
        $this->assertSame([], $result['columns']);
        $this->assertSame([], $result['records']);
        $this->assertSame(0, $result['totalCount']);
        $this->assertSame(100, $result['limit']);
        $this->assertSame(0, $result['offset']);
    }

    public function testGetTableRespectsArguments(): void
    {
        $provider = new NullSchemaProvider();

        $result = $provider->getTable('orders', 50, 10);

        $this->assertSame('orders', $result['table']);
        $this->assertSame(50, $result['limit']);
        $this->assertSame(10, $result['offset']);
    }

    public function testGetTableWithDefaultParams(): void
    {
        $provider = new NullSchemaProvider();

        $result = $provider->getTable('products');

        $this->assertSame('products', $result['table']);
        $this->assertSame(SchemaProviderInterface::DEFAULT_LIMIT, $result['limit']);
        $this->assertSame(0, $result['offset']);
    }

    public function testExecuteQueryReturnsEmptyArray(): void
    {
        $provider = new NullSchemaProvider();

        $this->assertSame([], $provider->executeQuery('SELECT * FROM users'));
    }

    public function testExecuteQueryWithParamsReturnsEmptyArray(): void
    {
        $provider = new NullSchemaProvider();

        $this->assertSame([], $provider->executeQuery('SELECT * FROM users WHERE id = ?', [1]));
    }

    public function testExplainQueryReturnsEmptyArray(): void
    {
        $provider = new NullSchemaProvider();

        $this->assertSame([], $provider->explainQuery('SELECT * FROM users'));
    }

    public function testExplainQueryWithAnalyzeReturnsEmptyArray(): void
    {
        $provider = new NullSchemaProvider();

        $this->assertSame([], $provider->explainQuery('SELECT * FROM users', [], true));
    }

    public function testExplainQueryWithParamsAndAnalyze(): void
    {
        $provider = new NullSchemaProvider();

        $this->assertSame([], $provider->explainQuery('SELECT * FROM users WHERE id = ?', [42], true));
    }

    public function testExecuteQueryWithComplexSql(): void
    {
        $provider = new NullSchemaProvider();

        $this->assertSame(
            [],
            $provider->executeQuery('SELECT u.*, o.total FROM users u JOIN orders o ON u.id = o.user_id WHERE u.active = ?', [
                true,
            ]),
        );
    }

    public function testGetTableReturnsSameStructureForDifferentTables(): void
    {
        $provider = new NullSchemaProvider();

        $result1 = $provider->getTable('users');
        $result2 = $provider->getTable('posts');

        $this->assertSame('users', $result1['table']);
        $this->assertSame('posts', $result2['table']);
        $this->assertSame($result1['columns'], $result2['columns']);
        $this->assertSame($result1['records'], $result2['records']);
        $this->assertSame($result1['totalCount'], $result2['totalCount']);
    }
}
