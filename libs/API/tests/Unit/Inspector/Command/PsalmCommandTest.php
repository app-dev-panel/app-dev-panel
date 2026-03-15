<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Command;

use AppDevPanel\Api\Inspector\Command\PsalmCommand;
use AppDevPanel\Api\Inspector\CommandInterface;
use PHPUnit\Framework\TestCase;

final class PsalmCommandTest extends TestCase
{
    public function testImplementsCommandInterface(): void
    {
        $this->assertTrue(is_subclass_of(PsalmCommand::class, CommandInterface::class));
    }

    public function testGetTitle(): void
    {
        $this->assertSame('Psalm', PsalmCommand::getTitle());
    }

    public function testGetDescription(): void
    {
        $this->assertSame('', PsalmCommand::getDescription());
    }

    public function testCommandName(): void
    {
        $this->assertSame('analyse/psalm', PsalmCommand::COMMAND_NAME);
    }
}
