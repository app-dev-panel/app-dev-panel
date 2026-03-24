<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit;

use AppDevPanel\Kernel\DebuggerIgnoreConfig;
use PHPUnit\Framework\TestCase;

final class DebuggerIgnoreConfigTest extends TestCase
{
    public function testDefaults(): void
    {
        $config = new DebuggerIgnoreConfig();

        $this->assertSame([], $config->requests);
        $this->assertSame([], $config->commands);
    }

    public function testCustomValues(): void
    {
        $config = new DebuggerIgnoreConfig(requests: ['/debug/*', '/health'], commands: ['debug:*']);

        $this->assertSame(['/debug/*', '/health'], $config->requests);
        $this->assertSame(['debug:*'], $config->commands);
    }
}
