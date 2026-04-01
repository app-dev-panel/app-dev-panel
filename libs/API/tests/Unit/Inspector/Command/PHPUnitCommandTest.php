<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Command;

use AppDevPanel\Api\Inspector\Command\PHPUnitCommand;
use AppDevPanel\Api\Inspector\CommandInterface;
use PHPUnit\Framework\TestCase;

final class PHPUnitCommandTest extends TestCase
{
    public function testImplementsCommandInterface(): void
    {
        $this->assertTrue(is_subclass_of(PHPUnitCommand::class, CommandInterface::class));
    }

    public function testGetTitle(): void
    {
        $this->assertSame('PHPUnit', PHPUnitCommand::getTitle());
    }

    public function testGetDescription(): void
    {
        $this->assertSame('', PHPUnitCommand::getDescription());
    }

    public function testCommandName(): void
    {
        $this->assertSame('test/phpunit', PHPUnitCommand::COMMAND_NAME);
    }

    public function testIsAvailableReturnsTrueWhenPHPUnitInstalled(): void
    {
        // phpunit/phpunit IS installed in this project
        $this->assertTrue(PHPUnitCommand::isAvailable());
    }
}
