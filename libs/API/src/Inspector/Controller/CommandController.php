<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Controller;

use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Api\Inspector\Command\BashCommand;
use AppDevPanel\Api\Inspector\Command\CodeceptionCommand;
use AppDevPanel\Api\Inspector\Command\MagoCommand;
use AppDevPanel\Api\Inspector\Command\PestCommand;
use AppDevPanel\Api\Inspector\Command\PHPStanCommand;
use AppDevPanel\Api\Inspector\Command\PHPUnitCommand;
use AppDevPanel\Api\Inspector\Command\PsalmCommand;
use AppDevPanel\Api\Inspector\Command\TestoCommand;
use AppDevPanel\Api\Inspector\CommandInterface;
use AppDevPanel\Api\PathResolverInterface;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function array_key_exists;
use function is_string;

class CommandController
{
    /**
     * Built-in commands grouped by category.
     * These are auto-discovered via {@see CommandInterface::isAvailable()}.
     *
     * @var array<string, array<string, class-string<CommandInterface>>>
     */
    private const BUILT_IN_COMMANDS = [
        'analyse' => [
            PsalmCommand::COMMAND_NAME => PsalmCommand::class,
            PHPStanCommand::COMMAND_NAME => PHPStanCommand::class,
            MagoCommand::COMMAND_NAME => MagoCommand::class,
        ],
        'test' => [
            PHPUnitCommand::COMMAND_NAME => PHPUnitCommand::class,
            CodeceptionCommand::COMMAND_NAME => CodeceptionCommand::class,
            PestCommand::COMMAND_NAME => PestCommand::class,
            TestoCommand::COMMAND_NAME => TestoCommand::class,
        ],
    ];

    /**
     * @param array<string, array<string, class-string>> $commandMap Additional commands beyond built-ins
     */
    public function __construct(
        private readonly JsonResponseFactoryInterface $responseFactory,
        private readonly PathResolverInterface $pathResolver,
        private readonly ContainerInterface $container,
        private readonly array $commandMap = [],
    ) {}

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $result = $this->collectRegisteredCommands();

        foreach ($this->getComposerScripts() as $scriptName => $commands) {
            $result[] = [
                'name' => "composer/{$scriptName}",
                'title' => $scriptName,
                'group' => 'composer',
                'description' => implode("\n", $commands),
            ];
        }

        return $this->responseFactory->createJsonResponse($result);
    }

    public function run(ServerRequestInterface $request): ResponseInterface
    {
        $commandList = $this->buildCommandList();

        $queryParams = $request->getQueryParams();
        $commandName = $queryParams['command'] ?? null;

        $this->validateCommandName($commandName, $commandList);

        $command = $this->resolveCommand($commandList[$commandName]);
        $result = $command->run();

        return $this->responseFactory->createJsonResponse([
            'status' => $result->getStatus(),
            'result' => $result->getResult(),
            'errors' => $result->getErrors(),
        ]);
    }

    /**
     * @return array<string, array{group: string, class: class-string}>
     */
    private function getValidCommands(): array
    {
        $result = [];
        $merged = array_merge_recursive(self::BUILT_IN_COMMANDS, $this->commandMap);
        foreach ($merged as $groupName => $commands) {
            $valid = array_filter(
                $commands,
                static fn(string $class) => is_subclass_of($class, CommandInterface::class) && $class::isAvailable(),
            );
            foreach ($valid as $name => $command) {
                $result[$name] = ['group' => $groupName, 'class' => $command];
            }
        }
        return $result;
    }

    private function collectRegisteredCommands(): array
    {
        $validCommands = $this->getValidCommands();
        return array_values(array_map(
            static fn(string $name, array $info) => [
                'name' => $name,
                'title' => $info['class']::getTitle(),
                'group' => $info['group'],
                'description' => $info['class']::getDescription(),
            ],
            array_keys($validCommands),
            $validCommands,
        ));
    }

    private function buildCommandList(): array
    {
        $commandList = array_map(static fn(array $info) => $info['class'], $this->getValidCommands());

        foreach ($this->getComposerScripts() as $scriptName => $commands) {
            $commandList["composer/{$scriptName}"] = ['composer', $scriptName];
        }
        return $commandList;
    }

    private function validateCommandName(?string $commandName, array $commandList): void
    {
        if ($commandName === null) {
            throw new InvalidArgumentException(sprintf('Command must not be null. Available commands: "%s".', implode(
                '", "',
                array_keys($commandList),
            )));
        }
        if (!array_key_exists($commandName, $commandList)) {
            throw new InvalidArgumentException(sprintf(
                'Unknown command "%s". Available commands: "%s".',
                $commandName,
                implode('", "', array_keys($commandList)),
            ));
        }
    }

    private function resolveCommand(string|array $commandClass): CommandInterface
    {
        if (is_string($commandClass) && $this->container->has($commandClass)) {
            return $this->container->get($commandClass);
        }
        return new BashCommand($this->pathResolver, (array) $commandClass);
    }

    private function getComposerScripts(): array
    {
        $composerJsonPath = $this->pathResolver->getRootPath() . '/composer.json';
        if (!file_exists($composerJsonPath)) {
            return [];
        }

        $composerJson = json_decode(file_get_contents($composerJsonPath), true, 512, JSON_THROW_ON_ERROR);
        $scripts = $composerJson['scripts'] ?? [];

        return is_array($scripts) ? array_map(static fn(mixed $script) => (array) $script, $scripts) : [];
    }
}
