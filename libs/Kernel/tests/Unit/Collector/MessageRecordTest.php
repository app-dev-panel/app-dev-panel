<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\MessageRecord;
use PHPUnit\Framework\TestCase;

final class MessageRecordTest extends TestCase
{
    public function testConstructorDefaults(): void
    {
        $record = new MessageRecord(messageClass: 'App\Message\SendEmail');

        $this->assertSame('App\Message\SendEmail', $record->messageClass);
        $this->assertSame('default', $record->bus);
        $this->assertNull($record->transport);
        $this->assertTrue($record->dispatched);
        $this->assertFalse($record->handled);
        $this->assertFalse($record->failed);
        $this->assertSame(0.0, $record->duration);
        $this->assertNull($record->message);
    }

    public function testConstructorCustomValues(): void
    {
        $record = new MessageRecord(
            messageClass: 'App\Message\ProcessOrder',
            bus: 'async',
            transport: 'rabbitmq',
            dispatched: true,
            handled: true,
            failed: false,
            duration: 0.123,
            message: ['orderId' => 42],
        );

        $this->assertSame('async', $record->bus);
        $this->assertSame('rabbitmq', $record->transport);
        $this->assertTrue($record->handled);
        $this->assertSame(0.123, $record->duration);
        $this->assertSame(['orderId' => 42], $record->message);
    }

    public function testToArray(): void
    {
        $record = new MessageRecord(
            messageClass: 'App\Message\Notify',
            bus: 'event',
            transport: 'sync',
            dispatched: true,
            handled: true,
            failed: false,
            duration: 0.5,
            message: 'payload',
        );

        $array = $record->toArray();

        $this->assertSame('App\Message\Notify', $array['messageClass']);
        $this->assertSame('event', $array['bus']);
        $this->assertSame('sync', $array['transport']);
        $this->assertTrue($array['dispatched']);
        $this->assertTrue($array['handled']);
        $this->assertFalse($array['failed']);
        $this->assertSame(0.5, $array['duration']);
        $this->assertSame('payload', $array['message']);
    }
}
