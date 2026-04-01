<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Command;

use AppDevPanel\Api\Inspector\Command\PHPStanCommand;
use AppDevPanel\Api\Inspector\CommandInterface;
use PHPUnit\Framework\TestCase;

final class PHPStanCommandTest extends TestCase
{
    public function testImplementsCommandInterface(): void
    {
        $this->assertTrue(is_subclass_of(PHPStanCommand::class, CommandInterface::class));
    }

    public function testGetTitle(): void
    {
        $this->assertSame('PHPStan', PHPStanCommand::getTitle());
    }

    public function testGetDescription(): void
    {
        $this->assertSame('', PHPStanCommand::getDescription());
    }

    public function testCommandName(): void
    {
        $this->assertSame('analyse/phpstan', PHPStanCommand::COMMAND_NAME);
    }

    public function testIsAvailableReturnsFalseWhenPHPStanNotInstalled(): void
    {
        // phpstan/phpstan is NOT installed in this project
        $this->assertFalse(PHPStanCommand::isAvailable());
    }

    public function testIsAvailableReturnsBool(): void
    {
        $this->assertIsBool(PHPStanCommand::isAvailable());
    }
}
