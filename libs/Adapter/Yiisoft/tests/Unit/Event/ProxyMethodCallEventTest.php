<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Tests\Unit\Event;

use PHPUnit\Framework\TestCase;
use stdClass;
use Yiisoft\Yii\Debug\Event\ProxyMethodCallEvent;

class ProxyMethodCallEventTest extends TestCase
{
    public function testEvent(): void
    {
        $time = microtime(true);
        $event = new ProxyMethodCallEvent(
            'test',
            stdClass::class,
            'test',
            [],
            true,
            'success',
            null,
            $time,
            $time + 1
        );

        $this->assertEquals($time, $event->timeStart);
        $this->assertEquals($time + 1, $event->timeEnd);
        $this->assertEquals(stdClass::class, $event->class);
    }
}
