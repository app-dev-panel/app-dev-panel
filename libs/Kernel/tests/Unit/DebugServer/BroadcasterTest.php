<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\DebugServer;

use AppDevPanel\Kernel\DebugServer\Broadcaster;
use AppDevPanel\Kernel\DebugServer\Connection;
use PHPUnit\Framework\TestCase;

final class BroadcasterTest extends TestCase
{
    public function testBroadcastWithNoListenersReturnsEmptyErrors(): void
    {
        $broadcaster = new Broadcaster();

        $errors = $broadcaster->broadcast(Connection::MESSAGE_TYPE_LOGGER, 'test message');

        $this->assertSame([], $errors);
    }

    public function testBroadcastWithVarDumperTypeReturnsEmptyErrors(): void
    {
        $broadcaster = new Broadcaster();

        $errors = $broadcaster->broadcast(Connection::MESSAGE_TYPE_VAR_DUMPER, '{"key":"value"}');

        $this->assertSame([], $errors);
    }

    public function testBroadcastEncodesTypeAndDataAsJsonPayload(): void
    {
        $broadcaster = new Broadcaster();

        // No socket files → no errors, but verifies json_encode doesn't throw
        $errors = $broadcaster->broadcast(Connection::MESSAGE_TYPE_LOGGER, '"quoted string"');

        $this->assertSame([], $errors);
    }
}
