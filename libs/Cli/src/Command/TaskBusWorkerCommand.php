<?php

declare(strict_types=1);

declare(ticks=1);

namespace AppDevPanel\Cli\Command;

use AppDevPanel\TaskBus\Message\RunAnalyzer;
use AppDevPanel\TaskBus\Message\RunCommand;
use AppDevPanel\TaskBus\Message\RunTests;
use AppDevPanel\TaskBus\Scheduler\ScheduleRegistry;
use AppDevPanel\TaskBus\Storage\PdoFactory;
use AppDevPanel\TaskBus\Storage\SqliteTaskRepository;
use AppDevPanel\TaskBus\TaskBusConfig;
use AppDevPanel\TaskBus\TaskBusFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'taskbus:worker', description: 'Start TaskBus background worker')]
final class TaskBusWorkerCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('db', null, InputOption::VALUE_OPTIONAL, 'SQLite database path', 'task-bus.sqlite')
            ->addOption('poll-interval', null, InputOption::VALUE_OPTIONAL, 'Poll interval in milliseconds', '200')
            ->addOption('max-tasks', null, InputOption::VALUE_OPTIONAL, 'Max concurrent tasks', '4');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $dbPath = $input->getOption('db');
        $pollInterval = (int) $input->getOption('poll-interval') * 1000; // ms → μs
        $maxTasks = (int) $input->getOption('max-tasks');

        $config = new TaskBusConfig(
            databasePath: $dbPath,
            maxConcurrentTasks: $maxTasks,
            workerSleepInterval: $pollInterval,
        );

        $pdo = PdoFactory::create($dbPath);
        $repository = new SqliteTaskRepository($pdo);
        $bus = TaskBusFactory::create($config, $repository);
        $scheduleRegistry = new ScheduleRegistry($pdo);

        $running = true;
        pcntl_signal(SIGINT, static function () use (&$running, $io): void {
            $io->text('Shutting down worker...');
            $running = false;
        });
        pcntl_signal(SIGTERM, static function () use (&$running, $io): void {
            $io->text('Shutting down worker...');
            $running = false;
        });

        $io->success("TaskBus worker started (db: {$dbPath}, poll: {$pollInterval}μs, max: {$maxTasks})");

        $lastScheduleCheck = 0;

        while ($running) {
            // Check cron schedules every 60 seconds
            $now = time();
            if (($now - $lastScheduleCheck) >= 60) {
                $lastScheduleCheck = $now;
                foreach ($scheduleRegistry->findDue() as $schedule) {
                    $io->text("[scheduler] Dispatching: {$schedule['name']} ({$schedule['message_type']})");
                    $params = $schedule['message_payload'];
                    match ($schedule['message_type']) {
                        'run_command' => $bus->runCommand($params['command'] ?? 'echo "no command"'),
                        'run_tests' => $bus->runTests($params['runner'] ?? 'vendor/bin/phpunit', $params['args'] ?? []),
                        'run_analyzer' => $bus->runAnalyzer(
                            $params['analyzer'] ?? 'vendor/bin/mago',
                            $params['args'] ?? [],
                        ),
                        default => null,
                    };
                }
            }

            // Process scheduled tasks that became ready
            foreach ($repository->findScheduledReady() as $task) {
                $io->text("[worker] Processing: {$task->id} ({$task->type})");
                $payload = $task->payload;
                match ($task->type) {
                    'run_command' => $bus->dispatch(new RunCommand(
                        taskId: $task->id,
                        command: $payload['command'] ?? 'echo "no command"',
                        workingDirectory: $payload['working_directory'] ?? null,
                    )),
                    'run_tests' => $bus->dispatch(new RunTests(
                        taskId: $task->id,
                        runner: $payload['runner'] ?? 'vendor/bin/phpunit',
                        args: $payload['args'] ?? [],
                        workingDirectory: $payload['working_directory'] ?? null,
                    )),
                    'run_analyzer' => $bus->dispatch(new RunAnalyzer(
                        taskId: $task->id,
                        analyzer: $payload['analyzer'] ?? 'vendor/bin/mago',
                        args: $payload['args'] ?? [],
                        workingDirectory: $payload['working_directory'] ?? null,
                    )),
                    default => null,
                };
            }

            usleep($pollInterval);
        }

        $io->text('Worker stopped.');

        return Command::SUCCESS;
    }
}
