<?php

declare(strict_types=1);

declare(ticks=1);

namespace AppDevPanel\Cli\Command;

use AppDevPanel\TaskBus\Scheduler\ScheduleRegistry;
use AppDevPanel\TaskBus\Storage\PdoFactory;
use AppDevPanel\TaskBus\TaskBusConfig;
use AppDevPanel\TaskBus\TaskBusFactory;
use AppDevPanel\TaskBus\Transport\JsonRpcHandler;
use AppDevPanel\TaskBus\Transport\JsonRpcServer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'taskbus:serve', description: 'Start TaskBus JSON-RPC server')]
final class TaskBusServeCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('host', null, InputOption::VALUE_OPTIONAL, 'Host to bind', '127.0.0.1')
            ->addOption('port', 'p', InputOption::VALUE_OPTIONAL, 'Port to bind', '9800')
            ->addOption('socket', 's', InputOption::VALUE_OPTIONAL, 'Unix socket path (overrides host/port)')
            ->addOption('db', null, InputOption::VALUE_OPTIONAL, 'SQLite database path', 'task-bus.sqlite');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $dbPath = $input->getOption('db');
        $socketPath = $input->getOption('socket');

        if ($socketPath !== null) {
            $address = "unix://{$socketPath}";
        } else {
            $host = $input->getOption('host');
            $port = $input->getOption('port');
            $address = "tcp://{$host}:{$port}";
        }

        $config = new TaskBusConfig(databasePath: $dbPath);
        $bus = TaskBusFactory::create($config);

        $pdo = PdoFactory::create($dbPath);
        $scheduleRegistry = new ScheduleRegistry($pdo);

        $rpcHandler = new JsonRpcHandler($bus, $scheduleRegistry);
        $server = new JsonRpcServer($rpcHandler);

        $running = true;
        pcntl_signal(SIGINT, static function () use (&$running, $server): void {
            $running = false;
            $server->stop();
        });
        pcntl_signal(SIGTERM, static function () use (&$running, $server): void {
            $running = false;
            $server->stop();
        });

        $io->success("TaskBus JSON-RPC server starting on {$address}");
        $io->text("Database: {$dbPath}");

        $server->listen($address);

        return Command::SUCCESS;
    }
}
