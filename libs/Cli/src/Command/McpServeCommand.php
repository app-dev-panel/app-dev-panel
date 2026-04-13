<?php

declare(strict_types=1);

namespace AppDevPanel\Cli\Command;

use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\StorageFactory;
use AppDevPanel\McpServer\Inspector\InspectorClient;
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
        $this
            ->addOption(
                'storage-path',
                's',
                InputOption::VALUE_REQUIRED,
                'Path to debug data storage directory',
                sys_get_temp_dir() . '/adp',
            )
            ->addOption(
                'storage-driver',
                'd',
                InputOption::VALUE_REQUIRED,
                'Storage driver: "sqlite", "file", or FQCN',
                'file',
            )
            ->addOption(
                'inspector-url',
                'i',
                InputOption::VALUE_REQUIRED,
                'URL of the running application for live inspection (e.g., http://localhost:8080)',
            )
            ->setHelp(<<<'HELP'
                Start an MCP server over stdio that exposes ADP debug data to AI assistants.

                The server speaks the Model Context Protocol (JSON-RPC 2.0 over stdio)
                and provides tools for querying debug entries, searching logs, analyzing
                exceptions, viewing database queries, and inspecting live application state.

                Configure in your AI client (e.g., Claude Code):
                  <info>{
                    "mcpServers": {
                      "adp": {
                        "command": "php",
                        "args": ["vendor/bin/adp-mcp", "--storage=/path/to/debug-data", "--inspector-url=http://localhost:8080"]
                      }
                    }
                  }</info>

                Or use this command directly:
                  <info>mcp:serve --storage-path=/path/to/debug-data --inspector-url=http://localhost:8080</info>

                Without --inspector-url, only debug tools (stored data) are available.
                With --inspector-url, inspector tools (routes, config, DB schema) are also enabled.
                HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $storagePath = (string) $input->getOption('storage-path');

        if (!is_dir($storagePath)) {
            $output->writeln(sprintf('<error>Storage path does not exist: %s</error>', $storagePath));
            return Command::FAILURE;
        }

        $inspectorUrl = $input->getOption('inspector-url');
        $inspectorClient = InspectorClient::fromOptionalUrl(is_string($inspectorUrl) ? $inspectorUrl : null);

        $driver = (string) $input->getOption('storage-driver');
        $storage = StorageFactory::create($driver, $storagePath, new DebuggerIdGenerator());
        $toolRegistry = McpToolRegistryFactory::create($storage, $inspectorClient);

        $transport = new StdioTransport();
        $server = new McpServer($toolRegistry, $transport);

        // Write status to stderr (not stdout, which is for MCP protocol)
        $status = sprintf('ADP MCP server started (storage: %s', $storagePath);
        if ($inspectorClient !== null) {
            $status .= sprintf(', inspector: %s', $inspectorUrl);
        }
        fwrite(STDERR, $status . ")\n");

        $server->run();

        return Command::SUCCESS;
    }
}
