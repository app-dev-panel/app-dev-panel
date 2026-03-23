<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Event;

use AppDevPanel\Kernel\Event\MethodCallRecord;
use AppDevPanel\Kernel\Event\ProxyMethodCallEvent;
use PHPUnit\Framework\TestCase;
use stdClass;

class ProxyMethodCallEventTest extends TestCase
{
    public function testEvent(): void
    {
        $time = microtime(true);
        $record = new MethodCallRecord('test', stdClass::class, 'test', [], true, 'success', null, $time, $time + 1);
        $event = new ProxyMethodCallEvent($record);

        $this->assertEquals($time, $event->timeStart);
        $this->assertEquals($time + 1, $event->timeEnd);
        $this->assertEquals(stdClass::class, $event->class);
        $this->assertSame($record, $event->record);
    }
}
