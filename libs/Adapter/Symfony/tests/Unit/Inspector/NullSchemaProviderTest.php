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

        $this->assertSame('users', $result['name']);
        $this->assertSame([], $result['columns']);
        $this->assertSame([], $result['records']);
        $this->assertSame(0, $result['total']);
    }

    public function testGetTableRespectsArguments(): void
    {
        $provider = new NullSchemaProvider();
        $result = $provider->getTable('orders', 500, 10);

        $this->assertSame('orders', $result['name']);
    }
}
