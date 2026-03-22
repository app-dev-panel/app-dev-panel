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
        $result = [];
        foreach ($this->commandMap as $groupName => $commands) {
            foreach ($commands as $name => $command) {
                if (!is_subclass_of($command, CommandInterface::class)) {
                    continue;
                }
                $result[] = [
                    'name' => $name,
                    'title' => $command::getTitle(),
                    'group' => $groupName,
                    'description' => $command::getDescription(),
                ];
            }
        }

        $composerScripts = $this->getComposerScripts();
        foreach ($composerScripts as $scriptName => $commands) {
            $commandName = "composer/{$scriptName}";
            $result[] = [
                'name' => $commandName,
                'title' => $scriptName,
                'group' => 'composer',
                'description' => implode("\n", $commands),
            ];
        }

        return $this->responseFactory->createJsonResponse($result);
    }

    public function run(ServerRequestInterface $request): ResponseInterface
    {
        $commandList = [];
        foreach ($this->commandMap as $commands) {
            foreach ($commands as $name => $command) {
                if (!is_subclass_of($command, CommandInterface::class)) {
                    continue;
                }
                $commandList[$name] = $command;
            }
        }
        $composerScripts = $this->getComposerScripts();
        foreach ($composerScripts as $scriptName => $commands) {
            $commandName = "composer/{$scriptName}";
            $commandList[$commandName] = ['composer', $scriptName];
        }

        $queryParams = $request->getQueryParams();
        $commandName = $queryParams['command'] ?? null;

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

        $commandClass = $commandList[$commandName];
        if (is_string($commandClass) && $this->container->has($commandClass)) {
            $command = $this->container->get($commandClass);
        } else {
            $command = new BashCommand($this->pathResolver, (array) $commandClass);
        }
        $result = $command->run();

        return $this->responseFactory->createJsonResponse([
            'status' => $result->getStatus(),
            'result' => $result->getResult(),
            'error' => $result->getErrors(),
        ]);
    }

    private function getComposerScripts(): array
    {
        $result = [];
        $composerJsonPath = $this->pathResolver->getRootPath() . '/composer.json';
        if (file_exists($composerJsonPath)) {
            $composerJsonCommands = json_decode(file_get_contents($composerJsonPath), true, 512, JSON_THROW_ON_ERROR);
            if (is_array($composerJsonCommands) && array_key_exists('scripts', $composerJsonCommands)) {
                $scripts = $composerJsonCommands['scripts'];
                foreach ($scripts as $name => $script) {
                    $result[$name] = (array) $script;
                }
            }
        }
        return $result;
    }
}
