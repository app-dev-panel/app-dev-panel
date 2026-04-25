<?php

declare(strict_types=1);

namespace Unit\Command;

use AppDevPanel\Cli\Command\McpServeCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class McpServeCommandTest extends TestCase
{
    public function testMissingStoragePathReturnsFailure(): void
    {
        $nonexistent = sys_get_temp_dir() . '/adp-mcp-missing-' . uniqid();
        $this->assertDirectoryDoesNotExist($nonexistent);

        $tester = new CommandTester(new McpServeCommand());
        $exit = $tester->execute(['--storage-path' => $nonexistent]);

        $this->assertSame(Command::FAILURE, $exit);
        $this->assertStringContainsString('Storage path does not exist', $tester->getDisplay());
        $this->assertStringContainsString($nonexistent, $tester->getDisplay());
    }

    public function testCommandIsRegisteredUnderMcpServe(): void
    {
        $app = new Application();
        $app->add(new McpServeCommand());

        $this->assertTrue($app->has('mcp:serve'));
    }

    public function testCommandAdvertisesStorageDriverOption(): void
    {
        $command = new McpServeCommand();

        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasOption('storage-path'));
        $this->assertTrue($definition->hasOption('storage-driver'));
        $this->assertTrue($definition->hasOption('inspector-url'));
    }

    public function testStorageDriverDefaultsToFile(): void
    {
        $command = new McpServeCommand();

        $this->assertSame('file', $command->getDefinition()->getOption('storage-driver')->getDefault());
    }

    public function testStoragePathDefaultsToTemp(): void
    {
        $command = new McpServeCommand();

        $default = (string) $command->getDefinition()->getOption('storage-path')->getDefault();
        $this->assertStringStartsWith(sys_get_temp_dir(), $default);
    }

    public function testInspectorUrlOptionHasNoDefault(): void
    {
        $command = new McpServeCommand();

        $this->assertNull($command->getDefinition()->getOption('inspector-url')->getDefault());
    }

    public function testHelpTextMentionsStdioAndJsonRpc(): void
    {
        $help = new McpServeCommand()->getHelp();

        $this->assertStringContainsString('stdio', $help);
        $this->assertStringContainsString('JSON-RPC', $help);
    }
}
