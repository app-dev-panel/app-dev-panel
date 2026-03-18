<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Unit\Inspector;

use AppDevPanel\Adapter\Yii2\Inspector\NullSchemaProvider;
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

        $result = $provider->getTable('users', 100, 0);

        $this->assertSame('users', $result['name']);
        $this->assertSame([], $result['columns']);
        $this->assertSame([], $result['records']);
        $this->assertSame(0, $result['total']);
    }

    public function testGetTableRespectsArguments(): void
    {
        $provider = new NullSchemaProvider();

        $result = $provider->getTable('orders', 50, 10);

        $this->assertSame('orders', $result['name']);
    }
}
