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
}
