<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Command;

use AppDevPanel\Api\Inspector\Command\PestCommand;
use AppDevPanel\Api\Inspector\CommandInterface;
use PHPUnit\Framework\TestCase;

final class PestCommandTest extends TestCase
{
    public function testImplementsCommandInterface(): void
    {
        $this->assertTrue(is_subclass_of(PestCommand::class, CommandInterface::class));
    }

    public function testGetTitle(): void
    {
        $this->assertSame('Pest', PestCommand::getTitle());
    }

    public function testGetDescription(): void
    {
        $this->assertSame('', PestCommand::getDescription());
    }

    public function testCommandName(): void
    {
        $this->assertSame('test/pest', PestCommand::COMMAND_NAME);
    }

    public function testIsAvailableReturnsFalseWhenPestNotInstalled(): void
    {
        // pestphp/pest is not installed in this project
        $this->assertFalse(PestCommand::isAvailable());
    }

    public function testIsAvailableReturnsBool(): void
    {
        $this->assertIsBool(PestCommand::isAvailable());
    }
}
