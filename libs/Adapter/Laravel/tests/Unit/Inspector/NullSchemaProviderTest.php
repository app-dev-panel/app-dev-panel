<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Tests\Unit\Inspector;

use AppDevPanel\Adapter\Laravel\Inspector\NullSchemaProvider;
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

        $this->assertSame('users', $result['name']);
        $this->assertSame([], $result['columns']);
        $this->assertSame([], $result['records']);
        $this->assertSame(0, $result['total']);
    }

    public function testExplainQueryReturnsEmptyArray(): void
    {
        $provider = new NullSchemaProvider();
        $this->assertSame([], $provider->explainQuery('SELECT 1'));
    }
}
