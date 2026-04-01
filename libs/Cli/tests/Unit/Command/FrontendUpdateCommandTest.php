<?php

declare(strict_types=1);

namespace Unit\Command;

use AppDevPanel\Cli\Command\FrontendUpdateCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class FrontendUpdateCommandTest extends TestCase
{
    public function testCommandCanBeInstantiated(): void
    {
        $command = new FrontendUpdateCommand();

        $this->assertSame('frontend:update', $command->getName());
        $this->assertSame('Check for updates and download the latest frontend build', $command->getDescription());
    }

    public function testCommandHasPathOption(): void
    {
        $command = new FrontendUpdateCommand();
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('path'));
        $this->assertTrue($definition->hasOption('json'));
        $this->assertTrue($definition->hasArgument('action'));
    }

    public function testDownloadRequiresPath(): void
    {
        $tester = new CommandTester(new FrontendUpdateCommand());
        $tester->execute(['action' => 'download']);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('Path is required', $tester->getDisplay());
    }

    public function testUnknownAction(): void
    {
        $tester = new CommandTester(new FrontendUpdateCommand());
        $tester->execute(['action' => 'invalid']);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('Unknown action', $tester->getDisplay());
    }
}
