<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus\Tests\Unit\Process;

use AppDevPanel\TaskBus\Process\ProcessResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProcessResult::class)]
final class ProcessResultTest extends TestCase
{
    public function testSuccessful(): void
    {
        $result = new ProcessResult(exitCode: 0, stdout: 'ok', stderr: '', duration: 1.234);
        $this->assertTrue($result->isSuccessful());
    }

    public function testFailed(): void
    {
        $result = new ProcessResult(exitCode: 1, stdout: '', stderr: 'error', duration: 0.5);
        $this->assertFalse($result->isSuccessful());
    }

    public function testToArray(): void
    {
        $result = new ProcessResult(exitCode: 0, stdout: 'output', stderr: 'warn', duration: 1.23456);
        $array = $result->toArray();

        $this->assertSame(0, $array['exit_code']);
        $this->assertSame('output', $array['stdout']);
        $this->assertSame('warn', $array['stderr']);
        $this->assertSame(1.235, $array['duration']);
        $this->assertTrue($array['success']);
    }
}
