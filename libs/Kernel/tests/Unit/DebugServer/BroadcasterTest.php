<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\DebugServer;

use AppDevPanel\Kernel\DebugServer\Broadcaster;
use AppDevPanel\Kernel\DebugServer\Connection;
use PHPUnit\Framework\TestCase;

final class BroadcasterTest extends TestCase
{
    public function testBroadcastWithNoListeners(): void
    {
        $broadcaster = new Broadcaster();
        $errors = $broadcaster->broadcast(Connection::MESSAGE_TYPE_LOGGER, 'test message');
        $this->assertIsArray($errors);
    }
}
