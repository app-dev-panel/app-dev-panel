<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\QueryRecord;
use PHPUnit\Framework\TestCase;

final class QueryRecordTest extends TestCase
{
    public function testConstructorAssignsProperties(): void
    {
        $record = new QueryRecord(
            sql: 'SELECT * FROM users WHERE id = ?',
            rawSql: 'SELECT * FROM users WHERE id = 42',
            params: [42],
            line: 'UserRepository.php:55',
            startTime: 1000.0,
            endTime: 1000.05,
            rowsNumber: 3,
        );

        $this->assertSame('SELECT * FROM users WHERE id = ?', $record->sql);
        $this->assertSame('SELECT * FROM users WHERE id = 42', $record->rawSql);
        $this->assertSame([42], $record->params);
        $this->assertSame('UserRepository.php:55', $record->line);
        $this->assertSame(1000.0, $record->startTime);
        $this->assertSame(1000.05, $record->endTime);
        $this->assertSame(3, $record->rowsNumber);
    }

    public function testDefaultRowsNumber(): void
    {
        $record = new QueryRecord('SELECT 1', 'SELECT 1', [], '', 0.0, 0.0);

        $this->assertSame(0, $record->rowsNumber);
    }

    public function testWithParams(): void
    {
        $record = new QueryRecord(
            sql: 'INSERT INTO users (name, email) VALUES (?, ?)',
            rawSql: "INSERT INTO users (name, email) VALUES ('Alice', 'alice@test.com')",
            params: ['Alice', 'alice@test.com'],
            line: 'Service.php:100',
            startTime: 500.0,
            endTime: 500.123,
            rowsNumber: 1,
        );

        $this->assertSame(['Alice', 'alice@test.com'], $record->params);
        $this->assertSame(1, $record->rowsNumber);
        $this->assertSame('Service.php:100', $record->line);
    }

    public function testTimingValues(): void
    {
        $record = new QueryRecord(
            sql: 'SELECT 1',
            rawSql: 'SELECT 1',
            params: [],
            line: '',
            startTime: 1000.5,
            endTime: 1001.75,
        );

        $this->assertSame(1000.5, $record->startTime);
        $this->assertSame(1001.75, $record->endTime);
        $this->assertEqualsWithDelta(1.25, $record->endTime - $record->startTime, 0.001);
    }
}
