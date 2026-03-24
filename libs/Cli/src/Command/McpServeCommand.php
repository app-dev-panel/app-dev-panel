<?php

declare(strict_types=1);

namespace AppDevPanel\Cli\Command;

use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\FileStorage;
use AppDevPanel\McpServer\McpServer;
use AppDevPanel\McpServer\McpToolRegistryFactory;
use AppDevPanel\McpServer\Transport\StdioTransport;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'mcp:serve', description: 'Start MCP (Model Context Protocol) server for AI assistant integration')]
final class McpServeCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption(
            'storage-path',
            's',
            InputOption::VALUE_REQUIRED,
            'Path to debug data storage directory',
            sys_get_temp_dir() . '/adp',
        )->setHelp(<<<'HELP'
            Start an MCP server over stdio that exposes ADP debug data to AI assistants.

            The server speaks the Model Context Protocol (JSON-RPC 2.0 over stdio)
            and provides tools for querying debug entries, searching logs, analyzing
            exceptions, and viewing database queries.

            Configure in your AI client (e.g., Claude Code):
              <info>{
                "mcpServers": {
                  "adp": {
                    "command": "php",
                    "args": ["vendor/bin/adp-mcp", "--storage=/path/to/debug-data"]
                  }
                }
              }</info>

            Or use this command directly:
              <info>mcp:serve --storage-path=/path/to/debug-data</info>
            HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $storagePath = (string) $input->getOption('storage-path');

        if (!is_dir($storagePath)) {
            $output->writeln(sprintf('<error>Storage path does not exist: %s</error>', $storagePath));
            return Command::FAILURE;
        }

        $storage = new FileStorage($storagePath, new DebuggerIdGenerator());
        $toolRegistry = McpToolRegistryFactory::create($storage);

        $transport = new StdioTransport();
        $server = new McpServer($toolRegistry, $transport);

        // Write status to stderr (not stdout, which is for MCP protocol)
        fwrite(STDERR, sprintf("ADP MCP server started (storage: %s)\n", $storagePath));

        $server->run();

        return Command::SUCCESS;
    }
}
