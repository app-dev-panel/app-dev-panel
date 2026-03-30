<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\RedisCommandRecord;
use PHPUnit\Framework\TestCase;

final class RedisCommandRecordTest extends TestCase
{
    public function testToArray(): void
    {
        $record = new RedisCommandRecord(
            connection: 'default',
            command: 'SET',
            arguments: ['key', 'value'],
            result: true,
            duration: 0.005,
            error: null,
            line: '/app/src/Service.php:10',
        );

        $array = $record->toArray();

        $this->assertSame('default', $array['connection']);
        $this->assertSame('SET', $array['command']);
        $this->assertSame(['key', 'value'], $array['arguments']);
        $this->assertTrue($array['result']);
        $this->assertSame(0.005, $array['duration']);
        $this->assertNull($array['error']);
        $this->assertSame('/app/src/Service.php:10', $array['line']);
    }

    public function testDefaultValues(): void
    {
        $record = new RedisCommandRecord(
            connection: 'default',
            command: 'PING',
            arguments: [],
            result: 'PONG',
            duration: 0.001,
        );

        $this->assertNull($record->error);
        $this->assertSame('', $record->line);
    }

    public function testWithError(): void
    {
        $record = new RedisCommandRecord(
            connection: 'default',
            command: 'GET',
            arguments: ['key'],
            result: null,
            duration: 0.01,
            error: 'WRONGTYPE Operation against a key holding the wrong kind of value',
        );

        $this->assertSame('WRONGTYPE Operation against a key holding the wrong kind of value', $record->error);
        $array = $record->toArray();
        $this->assertSame('WRONGTYPE Operation against a key holding the wrong kind of value', $array['error']);
    }
}
