<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Database;

use AppDevPanel\Api\Inspector\Database\NullSchemaProvider;
use AppDevPanel\Api\Inspector\Database\SchemaProviderInterface;
use PHPUnit\Framework\TestCase;

final class NullSchemaProviderTest extends TestCase
{
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

    public function testGetTablePassesLimitAndOffset(): void
    {
        $provider = new NullSchemaProvider();
        $result = $provider->getTable('orders', 25, 100);

        $this->assertSame(25, $result['limit']);
        $this->assertSame(100, $result['offset']);
    }

    public function testExplainQueryReturnsEmptyArray(): void
    {
        $provider = new NullSchemaProvider();
        $this->assertSame([], $provider->explainQuery('SELECT 1'));
    }

    public function testExecuteQueryReturnsEmptyArray(): void
    {
        $provider = new NullSchemaProvider();
        $this->assertSame([], $provider->executeQuery('SELECT 1'));
    }
}
