<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Controller;

use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Api\Inspector\Command\BashCommand;
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
     * @param array<string, array<string, class-string>> $commandMap
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
            'error' => $result->getErrors(),
        ]);
    }

    /**
     * @return iterable<string, array{group: string, class: class-string}>
     */
    private function iterateValidCommands(): iterable
    {
        foreach ($this->commandMap as $groupName => $commands) {
            foreach ($commands as $name => $command) {
                if (!is_subclass_of($command, CommandInterface::class)) {
                    continue;
                }
                yield $name => ['group' => $groupName, 'class' => $command];
            }
        }
    }

    private function collectRegisteredCommands(): array
    {
        $result = [];
        foreach ($this->iterateValidCommands() as $name => $info) {
            $result[] = [
                'name' => $name,
                'title' => $info['class']::getTitle(),
                'group' => $info['group'],
                'description' => $info['class']::getDescription(),
            ];
        }
        return $result;
    }

    private function buildCommandList(): array
    {
        $commandList = [];
        foreach ($this->iterateValidCommands() as $name => $info) {
            $commandList[$name] = $info['class'];
        }
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
        if (!is_array($composerJson) || !array_key_exists('scripts', $composerJson)) {
            return [];
        }

        $result = [];
        foreach ($composerJson['scripts'] as $name => $script) {
            $result[$name] = (array) $script;
        }
        return $result;
    }
}
