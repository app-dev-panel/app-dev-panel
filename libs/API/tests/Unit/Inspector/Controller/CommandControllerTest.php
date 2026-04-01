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
}
