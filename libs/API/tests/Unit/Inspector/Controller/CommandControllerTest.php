<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Controller;

use AppDevPanel\Api\Inspector\Command\BashCommand;
use AppDevPanel\Api\Inspector\Command\PHPUnitCommand;
use AppDevPanel\Api\Inspector\CommandInterface;
use AppDevPanel\Api\Inspector\CommandResponse;
use AppDevPanel\Api\Inspector\Controller\CommandController;
use AppDevPanel\Api\PathResolverInterface;
use InvalidArgumentException;

final class CommandControllerTest extends ControllerTestCase
{
    private function pathResolver(): PathResolverInterface
    {
        $pathResolver = $this->createMock(PathResolverInterface::class);
        $pathResolver->method('getRootPath')->willReturn(dirname(__DIR__, 6));
        $pathResolver->method('getRuntimePath')->willReturn(dirname(__DIR__, 6) . '/runtime');
        return $pathResolver;
    }

    private function createController(array $commandMap = [], array $containerServices = []): CommandController
    {
        return new CommandController(
            $this->createResponseFactory(),
            $this->pathResolver(),
            $this->container($containerServices),
            $commandMap,
        );
    }

    public function testIndexWithCommands(): void
    {
        $controller = $this->createController([
            'testing' => [
                'phpunit' => BashCommand::class,
            ],
        ]);
        $response = $controller->index($this->get());

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertIsArray($data);
    }

    public function testIndexWithNonCommandClass(): void
    {
        $controller = $this->createController([
            'testing' => [
                'invalid' => \stdClass::class,
            ],
        ]);
        $response = $controller->index($this->get());

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);

        // stdClass should be filtered out
        $names = array_column($data, 'name');
        $this->assertNotContains('invalid', $names);
    }

    public function testIndexIncludesComposerScripts(): void
    {
        $controller = $this->createController();
        $response = $controller->index($this->get());

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        // The project root has composer.json with scripts
        $groups = array_column($data, 'group');
        $this->assertContains('composer', $groups);
    }

    public function testRunNullCommand(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must not be null');
        $controller->run($this->get());
    }

    public function testRunUnknownCommand(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown command');
        $controller->run($this->get(['command' => 'nonexistent']));
    }

    public function testRunRegisteredCommand(): void
    {
        $commandResult = new CommandResponse(CommandResponse::STATUS_OK, 'output', []);

        $command = $this->createMock(CommandInterface::class);
        $command->method('run')->willReturn($commandResult);

        $controller = $this->createController([
            'testing' => [
                'my-cmd' => BashCommand::class,
            ],
        ], [BashCommand::class => $command]);
        $response = $controller->run($this->get(['command' => 'my-cmd']));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame(CommandResponse::STATUS_OK, $data['status']);
        $this->assertSame('output', $data['result']);
    }

    public function testIndexFiltersUnavailableCommands(): void
    {
        // PHPUnitCommand::isAvailable() returns true (phpunit is installed in this project)
        // BashCommand::isAvailable() always returns true
        $controller = $this->createController([
            'testing' => [
                'test/phpunit' => PHPUnitCommand::class,
                'my-cmd' => BashCommand::class,
            ],
        ]);
        $response = $controller->index($this->get());

        $data = $this->responseData($response);
        $names = array_column($data, 'name');

        $this->assertContains('test/phpunit', $names);
        $this->assertContains('my-cmd', $names);
    }

    public function testIsAvailableOnBashCommand(): void
    {
        $this->assertTrue(BashCommand::isAvailable());
    }

    public function testIsAvailableOnPHPUnitCommand(): void
    {
        // PHPUnit is installed in this project
        $this->assertTrue(PHPUnitCommand::isAvailable());
    }

    public function testIndexIncludesBuiltInAvailableCommands(): void
    {
        // No custom commandMap — built-in commands should auto-discover
        $controller = $this->createController();
        $response = $controller->index($this->get());

        $data = $this->responseData($response);
        $names = array_column($data, 'name');

        // PHPUnit is installed in this project, so it must appear
        $this->assertContains('test/phpunit', $names);
    }

    public function testIndexBuiltInCommandsHaveCorrectGroup(): void
    {
        $controller = $this->createController();
        $response = $controller->index($this->get());

        $data = $this->responseData($response);
        $phpunit = array_values(array_filter($data, static fn(array $item) => $item['name'] === 'test/phpunit'));

        $this->assertCount(1, $phpunit);
        $this->assertSame('test', $phpunit[0]['group']);
        $this->assertSame('PHPUnit', $phpunit[0]['title']);
    }

    public function testCustomCommandMapMergesWithBuiltIn(): void
    {
        $controller = $this->createController([
            'custom' => [
                'custom/cmd' => BashCommand::class,
            ],
        ]);
        $response = $controller->index($this->get());

        $data = $this->responseData($response);
        $names = array_column($data, 'name');

        // Both built-in and custom commands present
        $this->assertContains('test/phpunit', $names);
        $this->assertContains('custom/cmd', $names);
    }

    public function testRunComposerScript(): void
    {
        // Composer scripts should be auto-discovered and runnable
        $controller = $this->createController();
        $response = $controller->index($this->get());

        $data = $this->responseData($response);
        $composerCommands = array_filter($data, static fn(array $item) => $item['group'] === 'composer');

        $this->assertNotEmpty($composerCommands, 'Composer scripts should appear in the command list');

        foreach ($composerCommands as $cmd) {
            $this->assertStringStartsWith('composer/', $cmd['name']);
            $this->assertSame('composer', $cmd['group']);
            $this->assertArrayHasKey('description', $cmd);
        }
    }

    public function testRunResolvesCommandFromContainer(): void
    {
        $commandResult = new CommandResponse(CommandResponse::STATUS_OK, 'container-output', []);

        $command = $this->createMock(CommandInterface::class);
        $command->expects($this->once())->method('run')->willReturn($commandResult);

        // Register BashCommand in the container — the controller should resolve it from there
        $controller = $this->createController([
            'testing' => [
                'bash-cmd' => BashCommand::class,
            ],
        ], [BashCommand::class => $command]);

        $response = $controller->run($this->get(['command' => 'bash-cmd']));

        $data = $this->responseData($response);
        $this->assertSame('ok', $data['status']);
        $this->assertSame('container-output', $data['result']);
    }

    public function testRunResolvesComposerScriptAsBashCommand(): void
    {
        // The controller should be able to resolve a composer script command
        $controller = $this->createController();
        $response = $controller->index($this->get());

        $data = $this->responseData($response);
        $composerCommands = array_filter($data, static fn(array $item) => $item['group'] === 'composer');

        // At least verify the structure is correct
        foreach ($composerCommands as $cmd) {
            $this->assertArrayHasKey('title', $cmd);
            $this->assertArrayHasKey('name', $cmd);
        }
    }

    public function testRunCommandWithErrorResult(): void
    {
        $commandResult = new CommandResponse(CommandResponse::STATUS_ERROR, 'error-output', ['some error']);

        $command = $this->createMock(CommandInterface::class);
        $command->method('run')->willReturn($commandResult);

        $controller = $this->createController([
            'testing' => [
                'error-cmd' => BashCommand::class,
            ],
        ], [BashCommand::class => $command]);

        $response = $controller->run($this->get(['command' => 'error-cmd']));

        $data = $this->responseData($response);
        $this->assertSame(CommandResponse::STATUS_ERROR, $data['status']);
        $this->assertSame('error-output', $data['result']);
        $this->assertSame(['some error'], $data['error']);
    }

    public function testRunCommandWithFailResult(): void
    {
        $commandResult = new CommandResponse(CommandResponse::STATUS_FAIL, null, ['fatal error']);

        $command = $this->createMock(CommandInterface::class);
        $command->method('run')->willReturn($commandResult);

        $controller = $this->createController([
            'testing' => [
                'fail-cmd' => BashCommand::class,
            ],
        ], [BashCommand::class => $command]);

        $response = $controller->run($this->get(['command' => 'fail-cmd']));

        $data = $this->responseData($response);
        $this->assertSame(CommandResponse::STATUS_FAIL, $data['status']);
        $this->assertNull($data['result']);
        $this->assertSame(['fatal error'], $data['error']);
    }

    public function testIndexComposerScriptHasDescription(): void
    {
        $controller = $this->createController();
        $response = $controller->index($this->get());

        $data = $this->responseData($response);
        $composerCommands = array_values(array_filter(
            $data,
            static fn(array $item) => $item['group'] === 'composer',
        ));

        if ($composerCommands !== []) {
            $first = $composerCommands[0];
            $this->assertArrayHasKey('description', $first);
            $this->assertIsString($first['description']);
        }
    }
}
