<?php

declare(strict_types=1);

namespace Unit\Command;

use AppDevPanel\Cli\Command\ServeCommand;
use PHPUnit\Framework\TestCase;

final class ServeCommandTest extends TestCase
{
    public function testCommandHasCorrectOptions(): void
    {
        $command = new ServeCommand();
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('host'));
        $this->assertTrue($definition->hasOption('port'));
        $this->assertTrue($definition->hasOption('storage-path'));
        $this->assertTrue($definition->hasOption('root-path'));
        $this->assertTrue($definition->hasOption('runtime-path'));
        $this->assertTrue($definition->hasOption('frontend-path'));
    }

    public function testDefaultOptionValues(): void
    {
        $command = new ServeCommand();
        $definition = $command->getDefinition();

        $this->assertSame('127.0.0.1', $definition->getOption('host')->getDefault());
        $this->assertSame('8888', $definition->getOption('port')->getDefault());
    }

    public function testPortShortcut(): void
    {
        $command = new ServeCommand();
        $definition = $command->getDefinition();

        $this->assertSame('p', $definition->getOption('port')->getShortcut());
    }
}
