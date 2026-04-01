<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Command;

use AppDevPanel\Api\Inspector\Command\MagoCommand;
use AppDevPanel\Api\Inspector\CommandInterface;
use PHPUnit\Framework\TestCase;

final class MagoCommandTest extends TestCase
{
    public function testImplementsCommandInterface(): void
    {
        $this->assertTrue(is_subclass_of(MagoCommand::class, CommandInterface::class));
    }

    public function testGetTitle(): void
    {
        $this->assertSame('Mago', MagoCommand::getTitle());
    }

    public function testGetDescription(): void
    {
        $this->assertSame('', MagoCommand::getDescription());
    }

    public function testCommandName(): void
    {
        $this->assertSame('analyse/mago', MagoCommand::COMMAND_NAME);
    }

    public function testIsAvailableReturnsTrueWhenMagoInstalled(): void
    {
        // carthage-software/mago IS installed in this project
        $this->assertTrue(MagoCommand::isAvailable());
    }
}
