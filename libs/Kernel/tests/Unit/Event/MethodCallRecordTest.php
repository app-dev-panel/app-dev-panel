<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Event;

use AppDevPanel\Kernel\Event\MethodCallRecord;
use PHPUnit\Framework\TestCase;

final class MethodCallRecordTest extends TestCase
{
    public function testConstructorAssignsAllProperties(): void
    {
        $error = new \RuntimeException('failed');
        $record = new MethodCallRecord(
            service: 'logger',
            class: 'Psr\Log\LoggerInterface',
            methodName: 'info',
            arguments: ['message', ['context']],
            result: null,
            status: 'error',
            error: $error,
            timeStart: 1000.0,
            timeEnd: 1000.05,
        );

        $this->assertSame('logger', $record->service);
        $this->assertSame('Psr\Log\LoggerInterface', $record->class);
        $this->assertSame('info', $record->methodName);
        $this->assertSame(['message', ['context']], $record->arguments);
        $this->assertNull($record->result);
        $this->assertSame('error', $record->status);
        $this->assertSame($error, $record->error);
        $this->assertSame(1000.0, $record->timeStart);
        $this->assertSame(1000.05, $record->timeEnd);
    }

    public function testNullArguments(): void
    {
        $record = new MethodCallRecord(
            service: 'cache',
            class: 'CacheInterface',
            methodName: 'clear',
            arguments: null,
            result: true,
            status: 'success',
            error: null,
            timeStart: 0.0,
            timeEnd: 0.01,
        );

        $this->assertNull($record->arguments);
        $this->assertNull($record->error);
        $this->assertTrue($record->result);
    }
}
