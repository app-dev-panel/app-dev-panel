<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Tests\Unit\Inspector;

use AppDevPanel\Adapter\Laravel\Inspector\NullSchemaProvider;
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
        $result = $provider->getTable('users');

        $this->assertSame('users', $result['table']);
        $this->assertSame([], $result['primaryKeys']);
        $this->assertSame([], $result['columns']);
        $this->assertSame([], $result['records']);
        $this->assertSame(0, $result['totalCount']);
        $this->assertSame(SchemaProviderInterface::DEFAULT_LIMIT, $result['limit']);
        $this->assertSame(0, $result['offset']);
    }

    public function testGetTableWithCustomLimitAndOffset(): void
    {
        $provider = new NullSchemaProvider();
        $result = $provider->getTable('orders', 25, 50);

        $this->assertSame('orders', $result['table']);
        $this->assertSame(25, $result['limit']);
        $this->assertSame(50, $result['offset']);
    }

    public function testExplainQueryReturnsEmptyArray(): void
    {
        $provider = new NullSchemaProvider();
        $this->assertSame([], $provider->explainQuery('SELECT 1'));
    }

    public function testExplainQueryWithParamsReturnsEmptyArray(): void
    {
        $provider = new NullSchemaProvider();
        $this->assertSame([], $provider->explainQuery('SELECT * FROM users WHERE id = ?', [1]));
    }

    public function testExplainQueryWithAnalyzeReturnsEmptyArray(): void
    {
        $provider = new NullSchemaProvider();
        $this->assertSame([], $provider->explainQuery('SELECT * FROM users', [], true));
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
}
