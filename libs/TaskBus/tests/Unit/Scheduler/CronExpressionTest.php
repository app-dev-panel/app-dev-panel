<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus\Tests\Unit\Scheduler;

use AppDevPanel\TaskBus\Scheduler\CronExpression;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(CronExpression::class)]
final class CronExpressionTest extends TestCase
{
    public function testEveryMinute(): void
    {
        $cron = new CronExpression('* * * * *');
        $this->assertTrue($cron->isDue(new DateTimeImmutable('2026-01-15 10:30:00')));
        $this->assertTrue($cron->isDue(new DateTimeImmutable('2026-06-01 00:00:00')));
    }

    public function testExactTime(): void
    {
        $cron = new CronExpression('30 10 * * *');
        $this->assertTrue($cron->isDue(new DateTimeImmutable('2026-01-15 10:30:00')));
        $this->assertFalse($cron->isDue(new DateTimeImmutable('2026-01-15 10:31:00')));
        $this->assertFalse($cron->isDue(new DateTimeImmutable('2026-01-15 11:30:00')));
    }

    public function testStepExpression(): void
    {
        $cron = new CronExpression('*/5 * * * *');
        $this->assertTrue($cron->isDue(new DateTimeImmutable('2026-01-15 10:00:00')));
        $this->assertTrue($cron->isDue(new DateTimeImmutable('2026-01-15 10:05:00')));
        $this->assertTrue($cron->isDue(new DateTimeImmutable('2026-01-15 10:10:00')));
        $this->assertFalse($cron->isDue(new DateTimeImmutable('2026-01-15 10:03:00')));
    }

    public function testRangeExpression(): void
    {
        $cron = new CronExpression('0 9-17 * * *');
        $this->assertTrue($cron->isDue(new DateTimeImmutable('2026-01-15 09:00:00')));
        $this->assertTrue($cron->isDue(new DateTimeImmutable('2026-01-15 12:00:00')));
        $this->assertTrue($cron->isDue(new DateTimeImmutable('2026-01-15 17:00:00')));
        $this->assertFalse($cron->isDue(new DateTimeImmutable('2026-01-15 08:00:00')));
        $this->assertFalse($cron->isDue(new DateTimeImmutable('2026-01-15 18:00:00')));
    }

    public function testListExpression(): void
    {
        $cron = new CronExpression('0 0 1,15 * *');
        $this->assertTrue($cron->isDue(new DateTimeImmutable('2026-01-01 00:00:00')));
        $this->assertTrue($cron->isDue(new DateTimeImmutable('2026-01-15 00:00:00')));
        $this->assertFalse($cron->isDue(new DateTimeImmutable('2026-01-02 00:00:00')));
    }

    public function testWeekday(): void
    {
        // Monday = 1
        $cron = new CronExpression('0 9 * * 1');
        $this->assertTrue($cron->isDue(new DateTimeImmutable('2026-03-23 09:00:00'))); // Monday
        $this->assertFalse($cron->isDue(new DateTimeImmutable('2026-03-24 09:00:00'))); // Tuesday
    }

    public function testInvalidExpression(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CronExpression('* * *');
    }

    public function testNextRunAfter(): void
    {
        $cron = new CronExpression('30 10 * * *');
        $next = $cron->nextRunAfter(new DateTimeImmutable('2026-01-15 10:30:00'));

        $this->assertSame('2026-01-16 10:30:00', $next->format('Y-m-d H:i:s'));
    }

    public function testNextRunAfterSameDay(): void
    {
        $cron = new CronExpression('30 14 * * *');
        $next = $cron->nextRunAfter(new DateTimeImmutable('2026-01-15 10:00:00'));

        $this->assertSame('2026-01-15 14:30:00', $next->format('Y-m-d H:i:s'));
    }

    #[DataProvider('everyFiveMinutesProvider')]
    public function testNextRunEveryFiveMinutes(string $from, string $expected): void
    {
        $cron = new CronExpression('*/5 * * * *');
        $next = $cron->nextRunAfter(new DateTimeImmutable($from));

        $this->assertSame($expected, $next->format('Y-m-d H:i:s'));
    }

    public static function everyFiveMinutesProvider(): iterable
    {
        yield ['2026-01-15 10:00:00', '2026-01-15 10:05:00'];
        yield ['2026-01-15 10:03:00', '2026-01-15 10:05:00'];
        yield ['2026-01-15 10:55:00', '2026-01-15 11:00:00'];
    }
}
