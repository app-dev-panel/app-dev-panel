<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Command;

use AppDevPanel\Api\Inspector\Command\TestoCommand;
use AppDevPanel\Api\Inspector\CommandInterface;
use PHPUnit\Framework\TestCase;

final class TestoCommandTest extends TestCase
{
    public function testImplementsCommandInterface(): void
    {
        $this->assertTrue(is_subclass_of(TestoCommand::class, CommandInterface::class));
    }

    public function testGetTitle(): void
    {
        $this->assertSame('Testo', TestoCommand::getTitle());
    }

    public function testGetDescription(): void
    {
        $this->assertSame('', TestoCommand::getDescription());
    }

    public function testCommandName(): void
    {
        $this->assertSame('test/testo', TestoCommand::COMMAND_NAME);
    }

    public function testIsAvailableReturnsBool(): void
    {
        $this->assertIsBool(TestoCommand::isAvailable());
    }
}
