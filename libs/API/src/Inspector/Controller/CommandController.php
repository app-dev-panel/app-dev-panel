<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Inspector\Controller;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Config\ConfigInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Yii\Debug\Api\Inspector\Command\BashCommand;
use Yiisoft\Yii\Debug\Api\Inspector\CommandInterface;

use function array_key_exists;
use function is_array;
use function is_string;

class CommandController
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
    ) {
    }

    public function index(ConfigInterface $config, Aliases $aliases): ResponseInterface
    {
        $params = $config->get('params');
        $configCommandMap = $params['yiisoft/yii-debug-api']['inspector']['commandMap'] ?? [];

        $result = [];
        foreach ($configCommandMap as $groupName => $commands) {
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

        $composerScripts = $this->getComposerScripts($aliases);
        foreach ($composerScripts as $scriptName => $commands) {
            $commandName = "composer/{$scriptName}";
            $result[] = [
                'name' => $commandName,
                'title' => $scriptName,
                'group' => 'composer',
                'description' => implode("\n", $commands),
            ];
        }

        return $this->responseFactory->createResponse($result);
    }

    public function run(
        ServerRequestInterface $request,
        ContainerInterface $container,
        ConfigInterface $config,
        Aliases $aliases,
    ): ResponseInterface {
        $params = $config->get('params');
        $commandMap = $params['yiisoft/yii-debug-api']['inspector']['commandMap'] ?? [];

        $commandList = [];
        foreach ($commandMap as $commands) {
            foreach ($commands as $name => $command) {
                if (!is_subclass_of($command, CommandInterface::class)) {
                    continue;
                }
                $commandList[$name] = $command;
            }
        }
        $composerScripts = $this->getComposerScripts($aliases);
        foreach ($composerScripts as $scriptName => $commands) {
            $commandName = "composer/{$scriptName}";
            $commandList[$commandName] = ['composer', $scriptName];
        }

        $request = $request->getQueryParams();
        $commandName = $request['command'] ?? null;

        if ($commandName === null) {
            throw new InvalidArgumentException(
                sprintf(
                    'Command must not be null. Available commands: "%s".',
                    implode('", "', array_keys($commandList))
                )
            );
        }

        if (!array_key_exists($commandName, $commandList)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Unknown command "%s". Available commands: "%s".',
                    $commandName,
                    implode('", "', array_keys($commandList))
                )
            );
        }

        $commandClass = $commandList[$commandName];
        if (is_string($commandClass) && $container->has($commandClass)) {
            $command = $container->get($commandClass);
        } else {
            $command = new BashCommand($aliases, (array)$commandClass);
        }
        $result = $command->run();

        return $this->responseFactory->createResponse([
            'status' => $result->getStatus(),
            'result' => $result->getResult(),
            'error' => $result->getErrors(),
        ]);
    }

    private function getComposerScripts(Aliases $aliases): array
    {
        $result = [];
        $composerJsonPath = $aliases->get('@root/composer.json');
        if (file_exists($composerJsonPath)) {
            $composerJsonCommands = json_decode(file_get_contents($composerJsonPath), true, 512, JSON_THROW_ON_ERROR);
            if (is_array($composerJsonCommands) && isset($composerJsonCommands['scripts'])) {
                $scripts = $composerJsonCommands['scripts'];
                foreach ($scripts as $name => $script) {
                    $result[$name] = (array) $script;
                }
            }
        }
        return $result;
    }
}
