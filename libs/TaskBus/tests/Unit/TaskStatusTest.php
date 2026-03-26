<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus\Tests\Unit;

use AppDevPanel\TaskBus\TaskStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(TaskStatus::class)]
final class TaskStatusTest extends TestCase
{
    #[DataProvider('terminalProvider')]
    public function testIsTerminal(TaskStatus $status, bool $expected): void
    {
        $this->assertSame($expected, $status->isTerminal());
    }

    public static function terminalProvider(): iterable
    {
        yield 'pending' => [TaskStatus::Pending, false];
        yield 'scheduled' => [TaskStatus::Scheduled, false];
        yield 'running' => [TaskStatus::Running, false];
        yield 'completed' => [TaskStatus::Completed, true];
        yield 'failed' => [TaskStatus::Failed, true];
        yield 'cancelled' => [TaskStatus::Cancelled, true];
    }
}
