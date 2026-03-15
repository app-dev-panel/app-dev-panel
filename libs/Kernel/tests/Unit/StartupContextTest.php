<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit;

use AppDevPanel\Kernel\StartupContext;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

final class StartupContextTest extends TestCase
{
    public function testForRequest(): void
    {
        $request = new ServerRequest('GET', '/test');
        $context = StartupContext::forRequest($request);

        $this->assertSame($request, $context->getRequest());
        $this->assertNull($context->getCommandName());
        $this->assertFalse($context->isCommand());
    }

    public function testForCommand(): void
    {
        $context = StartupContext::forCommand('debug:reset');

        $this->assertNull($context->getRequest());
        $this->assertSame('debug:reset', $context->getCommandName());
        $this->assertTrue($context->isCommand());
    }

    public function testForCommandWithNull(): void
    {
        $context = StartupContext::forCommand(null);

        $this->assertNull($context->getRequest());
        $this->assertNull($context->getCommandName());
        $this->assertTrue($context->isCommand());
    }

    public function testGeneric(): void
    {
        $context = StartupContext::generic();

        $this->assertNull($context->getRequest());
        $this->assertNull($context->getCommandName());
        $this->assertFalse($context->isCommand());
    }
}
