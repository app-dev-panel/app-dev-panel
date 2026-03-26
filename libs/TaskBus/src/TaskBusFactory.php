<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus;

use AppDevPanel\TaskBus\Handler\AgentTaskHandler;
use AppDevPanel\TaskBus\Handler\RunAnalyzerHandler;
use AppDevPanel\TaskBus\Handler\RunCommandHandler;
use AppDevPanel\TaskBus\Handler\RunTestsHandler;
use AppDevPanel\TaskBus\Message\AgentTask;
use AppDevPanel\TaskBus\Message\RunAnalyzer;
use AppDevPanel\TaskBus\Message\RunCommand;
use AppDevPanel\TaskBus\Message\RunTests;
use AppDevPanel\TaskBus\Middleware\TaskPersistenceMiddleware;
use AppDevPanel\TaskBus\Middleware\TaskRetryMiddleware;
use AppDevPanel\TaskBus\Process\ProcessRunnerInterface;
use AppDevPanel\TaskBus\Process\SymfonyProcessRunner;
use AppDevPanel\TaskBus\Storage\PdoFactory;
use AppDevPanel\TaskBus\Storage\SqliteTaskRepository;
use AppDevPanel\TaskBus\Storage\TaskRepositoryInterface;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;

final class TaskBusFactory
{
    /**
     * Create a fully wired TaskBus with all handlers and SQLite storage.
     *
     * @param array<string, non-empty-string> $agentActions Mapping of agent action names to shell commands
     */
    public static function create(
        TaskBusConfig $config = new TaskBusConfig(),
        ?TaskRepositoryInterface $repository = null,
        ?ProcessRunnerInterface $processRunner = null,
        array $agentActions = [],
    ): TaskBus {
        $pdo = PdoFactory::create($config->databasePath);
        $repository ??= new SqliteTaskRepository($pdo);
        $processRunner ??= new SymfonyProcessRunner();

        $handlers = new HandlersLocator([
            RunCommand::class => [new RunCommandHandler($processRunner, $repository)],
            RunTests::class => [new RunTestsHandler($processRunner, $repository)],
            RunAnalyzer::class => [new RunAnalyzerHandler($processRunner, $repository)],
            AgentTask::class => [new AgentTaskHandler($processRunner, $repository, $agentActions)],
        ]);

        $bus = new MessageBus([
            new TaskPersistenceMiddleware($repository),
            new TaskRetryMiddleware($repository),
            new HandleMessageMiddleware($handlers),
        ]);

        return new TaskBus($bus, $repository, $config);
    }

    /**
     * Create a TaskBus with in-memory SQLite (for testing).
     */
    public static function createInMemory(
        ?ProcessRunnerInterface $processRunner = null,
        array $agentActions = [],
    ): TaskBus {
        $pdo = PdoFactory::createInMemory();
        $repository = new SqliteTaskRepository($pdo);
        $processRunner ??= new SymfonyProcessRunner();

        return self::create(
            config: new TaskBusConfig(databasePath: ':memory:'),
            repository: $repository,
            processRunner: $processRunner,
            agentActions: $agentActions,
        );
    }
}
