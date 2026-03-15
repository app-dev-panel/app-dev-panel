<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Command;

use AppDevPanel\Api\Inspector\Command\CodeceptionCommand;
use AppDevPanel\Api\Inspector\CommandInterface;
use PHPUnit\Framework\TestCase;

final class CodeceptionCommandTest extends TestCase
{
    public function testImplementsCommandInterface(): void
    {
        $this->assertTrue(is_subclass_of(CodeceptionCommand::class, CommandInterface::class));
    }

    public function testGetTitle(): void
    {
        $this->assertSame('Codeception', CodeceptionCommand::getTitle());
    }

    public function testGetDescription(): void
    {
        $this->assertSame('', CodeceptionCommand::getDescription());
    }

    public function testCommandName(): void
    {
        $this->assertSame('test/codeception', CodeceptionCommand::COMMAND_NAME);
    }
}
