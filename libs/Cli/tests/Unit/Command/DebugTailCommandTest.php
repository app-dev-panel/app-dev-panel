<?php

declare(strict_types=1);

namespace Unit\Command;

use AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface;
use AppDevPanel\Cli\Command\DebugTailCommand;
use PHPUnit\Framework\TestCase;

final class DebugTailCommandTest extends TestCase
{
    public function testCommandCanBeInstantiated(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $command = new DebugTailCommand($repository);

        $this->assertSame('debug:tail', $command->getName());
        $this->assertSame('Watch debug entries in real-time', $command->getDescription());
    }

    public function testCommandHasIntervalOption(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $command = new DebugTailCommand($repository);
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('interval'));
        $this->assertTrue($definition->hasOption('json'));
    }
}
