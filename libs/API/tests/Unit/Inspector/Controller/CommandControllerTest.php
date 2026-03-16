<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Controller;

use AppDevPanel\Api\Inspector\Command\BashCommand;
use AppDevPanel\Api\Inspector\CommandInterface;
use AppDevPanel\Api\Inspector\CommandResponse;
use AppDevPanel\Api\Inspector\Controller\CommandController;
use InvalidArgumentException;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Config\ConfigInterface;

final class CommandControllerTest extends ControllerTestCase
{
    private function createController(): CommandController
    {
        return new CommandController($this->createResponseFactory());
    }

    private function aliases(): Aliases
    {
        return new Aliases(['@root' => dirname(__DIR__, 6)]);
    }

    private function configWithCommands(array $commandMap = []): ConfigInterface
    {
        $config = $this->createMock(ConfigInterface::class);
        $config
            ->method('get')
            ->with('params')
            ->willReturn([
                'app-dev-panel/yii-debug-api' => [
                    'inspector' => [
                        'commandMap' => $commandMap,
                    ],
                ],
            ]);
        return $config;
    }

    public function testIndexWithCommands(): void
    {
        $config = $this->configWithCommands([
            'testing' => [
                'phpunit' => BashCommand::class,
            ],
        ]);

        $controller = $this->createController();
        $response = $controller->index($config, $this->aliases());

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertIsArray($data);
    }

    public function testIndexWithNonCommandClass(): void
    {
        $config = $this->configWithCommands([
            'testing' => [
                'invalid' => \stdClass::class,
            ],
        ]);

        $controller = $this->createController();
        $response = $controller->index($config, $this->aliases());

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);

        // stdClass should be filtered out
        $names = array_column($data, 'name');
        $this->assertNotContains('invalid', $names);
    }

    public function testIndexIncludesComposerScripts(): void
    {
        $config = $this->configWithCommands();

        $controller = $this->createController();
        $response = $controller->index($config, $this->aliases());

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        // The project root has composer.json with scripts
        $groups = array_column($data, 'group');
        $this->assertContains('composer', $groups);
    }

    public function testRunNullCommand(): void
    {
        $config = $this->configWithCommands();
        $container = $this->container();

        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must not be null');
        $controller->run($this->get(), $container, $config, $this->aliases());
    }

    public function testRunUnknownCommand(): void
    {
        $config = $this->configWithCommands();
        $container = $this->container();

        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown command');
        $controller->run($this->get(['command' => 'nonexistent']), $container, $config, $this->aliases());
    }

    public function testRunRegisteredCommand(): void
    {
        $commandResult = new CommandResponse(CommandResponse::STATUS_OK, 'output', []);

        $command = $this->createMock(CommandInterface::class);
        $command->method('run')->willReturn($commandResult);

        $config = $this->configWithCommands([
            'testing' => [
                'my-cmd' => BashCommand::class,
            ],
        ]);

        $container = $this->container([BashCommand::class => $command]);

        $controller = $this->createController();
        $response = $controller->run($this->get(['command' => 'my-cmd']), $container, $config, $this->aliases());

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame(CommandResponse::STATUS_OK, $data['status']);
        $this->assertSame('output', $data['result']);
    }
}
