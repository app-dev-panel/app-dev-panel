<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Unit\Inspector;

use AppDevPanel\Adapter\Symfony\Inspector\NullSchemaProvider;
use AppDevPanel\Api\Inspector\Database\SchemaProviderInterface;
use PHPUnit\Framework\TestCase;

final class NullSchemaProviderTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(SchemaProviderInterface::class, new NullSchemaProvider());
    }

    public function testGetTablesReturnsEmpty(): void
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

    public function testGetTableRespectsArguments(): void
    {
        $provider = new NullSchemaProvider();
        $result = $provider->getTable('orders', 500, 10);

        $this->assertSame('orders', $result['table']);
        $this->assertSame(500, $result['limit']);
        $this->assertSame(10, $result['offset']);
    }

    public function testExplainQueryReturnsEmpty(): void
    {
        $provider = new NullSchemaProvider();

        $this->assertSame([], $provider->explainQuery('SELECT * FROM users'));
    }

    public function testExplainQueryWithParamsAndAnalyze(): void
    {
        $provider = new NullSchemaProvider();

        $this->assertSame([], $provider->explainQuery('SELECT * FROM users WHERE id = ?', [1], true));
    }

    public function testExecuteQueryReturnsEmpty(): void
    {
        $provider = new NullSchemaProvider();

        $this->assertSame([], $provider->executeQuery('SELECT 1'));
    }

    public function testExecuteQueryWithParams(): void
    {
        $provider = new NullSchemaProvider();

        $this->assertSame([], $provider->executeQuery('SELECT * FROM users WHERE id = ?', [42]));
    }
}
