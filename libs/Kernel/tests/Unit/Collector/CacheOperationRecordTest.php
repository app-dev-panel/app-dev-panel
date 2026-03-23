<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\CacheOperationRecord;
use PHPUnit\Framework\TestCase;

final class CacheOperationRecordTest extends TestCase
{
    public function testConstructorDefaults(): void
    {
        $record = new CacheOperationRecord(pool: 'default', operation: 'get', key: 'user:42');

        $this->assertSame('default', $record->pool);
        $this->assertSame('get', $record->operation);
        $this->assertSame('user:42', $record->key);
        $this->assertFalse($record->hit);
        $this->assertSame(0.0, $record->duration);
        $this->assertNull($record->value);
    }

    public function testConstructorCustomValues(): void
    {
        $record = new CacheOperationRecord(
            pool: 'redis',
            operation: 'set',
            key: 'session:abc',
            hit: true,
            duration: 0.002,
            value: ['data' => 'cached'],
        );

        $this->assertSame('redis', $record->pool);
        $this->assertTrue($record->hit);
        $this->assertSame(0.002, $record->duration);
        $this->assertSame(['data' => 'cached'], $record->value);
    }

    public function testToArray(): void
    {
        $record = new CacheOperationRecord(
            pool: 'file',
            operation: 'delete',
            key: 'temp:key',
            hit: false,
            duration: 0.001,
            value: null,
        );

        $array = $record->toArray();

        $this->assertSame('file', $array['pool']);
        $this->assertSame('delete', $array['operation']);
        $this->assertSame('temp:key', $array['key']);
        $this->assertFalse($array['hit']);
        $this->assertSame(0.001, $array['duration']);
        $this->assertNull($array['value']);
    }
}
