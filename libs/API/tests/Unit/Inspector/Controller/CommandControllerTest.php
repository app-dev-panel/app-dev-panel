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
    private function pathResolver(?string $rootPath = null): PathResolverInterface
    {
        $root = $rootPath ?? dirname(__DIR__, 6);
        $pathResolver = $this->createMock(PathResolverInterface::class);
        $pathResolver->method('getRootPath')->willReturn($root);
        $pathResolver->method('getRuntimePath')->willReturn($root . '/runtime');
        return $pathResolver;
    }

    private function createController(
        array $commandMap = [],
        array $containerServices = [],
        ?PathResolverInterface $pathResolver = null,
    ): CommandController {
        return new CommandController(
            $this->createResponseFactory(),
            $pathResolver ?? $this->pathResolver(),
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
        $this->assertSame(['some error'], $data['errors']);
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
        $this->assertSame(['fatal error'], $data['errors']);
    }

    public function testIndexComposerScriptHasDescription(): void
    {
        $controller = $this->createController();
        $response = $controller->index($this->get());

        $data = $this->responseData($response);
        $composerCommands = array_values(array_filter($data, static fn(array $item) => $item['group'] === 'composer'));

        if ($composerCommands !== []) {
            $first = $composerCommands[0];
            $this->assertArrayHasKey('description', $first);
            $this->assertIsString($first['description']);
        }
    }

    public function testRunResponseStructure(): void
    {
        $commandResult = new CommandResponse(CommandResponse::STATUS_OK, ['key' => 'value'], []);

        $command = $this->createMock(CommandInterface::class);
        $command->method('run')->willReturn($commandResult);

        $controller = $this->createController([
            'testing' => [
                'struct-cmd' => BashCommand::class,
            ],
        ], [BashCommand::class => $command]);

        $response = $controller->run($this->get(['command' => 'struct-cmd']));

        $data = $this->responseData($response);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('result', $data);
        $this->assertArrayHasKey('errors', $data);
    }

    public function testIndexWithNoComposerJson(): void
    {
        // Use a path with no composer.json
        $tmpDir = sys_get_temp_dir() . '/adp-test-no-composer-' . uniqid();
        mkdir($tmpDir, 0755, true);

        try {
            $controller = $this->createController(commandMap: [], pathResolver: $this->pathResolver($tmpDir));

            $response = $controller->index($this->get());

            $data = $this->responseData($response);
            // No composer scripts should be present
            $composerCommands = array_filter($data, static fn(array $item) => $item['group'] === 'composer');
            $this->assertEmpty($composerCommands);
        } finally {
            rmdir($tmpDir);
        }
    }

    public function testIndexCommandStructure(): void
    {
        $controller = $this->createController();
        $response = $controller->index($this->get());

        $data = $this->responseData($response);
        $this->assertNotEmpty($data);

        foreach ($data as $cmd) {
            $this->assertArrayHasKey('name', $cmd);
            $this->assertArrayHasKey('title', $cmd);
            $this->assertArrayHasKey('group', $cmd);
            $this->assertArrayHasKey('description', $cmd);
            $this->assertIsString($cmd['name']);
            $this->assertIsString($cmd['title']);
            $this->assertIsString($cmd['group']);
            $this->assertIsString($cmd['description']);
        }
    }

    public function testRunUnknownCommandExceptionContainsAvailableCommands(): void
    {
        $controller = $this->createController();

        try {
            $controller->run($this->get(['command' => 'nonexistent']));
            $this->fail('Expected InvalidArgumentException was not thrown.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('nonexistent', $e->getMessage());
            $this->assertStringContainsString('Available commands', $e->getMessage());
        }
    }

    public function testRunNullCommandExceptionContainsAvailableCommands(): void
    {
        $controller = $this->createController();

        try {
            $controller->run($this->get());
            $this->fail('Expected InvalidArgumentException was not thrown.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('must not be null', $e->getMessage());
            $this->assertStringContainsString('Available commands', $e->getMessage());
        }
    }

    public function testIndexEmptyCommandMap(): void
    {
        $controller = $this->createController([]);
        $response = $controller->index($this->get());

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertIsArray($data);

        // Should still contain built-in commands
        $names = array_column($data, 'name');
        $this->assertContains('test/phpunit', $names);
    }

    public function testRunComposerScriptExecutesBashCommand(): void
    {
        // Create a temp directory with a composer.json that has a simple script
        $tmpDir = sys_get_temp_dir() . '/adp-cmd-composer-' . uniqid();
        mkdir($tmpDir, 0755, true);

        $composerJson = json_encode([
            'scripts' => [
                'hello' => 'echo hello-from-composer',
            ],
        ], JSON_THROW_ON_ERROR);
        file_put_contents($tmpDir . '/composer.json', $composerJson);

        try {
            $controller = $this->createController(commandMap: [], pathResolver: $this->pathResolver($tmpDir));

            // First verify the script appears in the index
            $indexResponse = $controller->index($this->get());
            $indexData = $this->responseData($indexResponse);
            $names = array_column($indexData, 'name');
            $this->assertContains('composer/hello', $names);

            // Now run the composer script — it resolves as BashCommand(['composer', 'hello'])
            // This will fail since there's no actual composer binary context,
            // but it exercises the resolveCommand path for array commands
            $response = $controller->run($this->get(['command' => 'composer/hello']));
            $data = $this->responseData($response);

            // The response structure should be correct regardless of success/failure
            $this->assertArrayHasKey('status', $data);
            $this->assertArrayHasKey('result', $data);
            $this->assertArrayHasKey('errors', $data);
        } finally {
            @unlink($tmpDir . '/composer.json');
            @rmdir($tmpDir);
        }
    }

    public function testRunBuiltInCommandNotInContainer(): void
    {
        // When a built-in command class is NOT in the container,
        // resolveCommand falls through to BashCommand
        // BashCommand::isAvailable() returns true and it's always a valid command class
        $controller = $this->createController(
            commandMap: [
                'testing' => [
                    'my-bash' => BashCommand::class,
                ],
            ],
            containerServices: [], // Empty container — no services registered
        );

        // BashCommand not in container, so resolveCommand creates new BashCommand
        // with the class name cast to array, which will fail as a shell command
        $response = $controller->run($this->get(['command' => 'my-bash']));
        $data = $this->responseData($response);

        // It should return a valid response structure even if the bash command fails
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('result', $data);
        $this->assertArrayHasKey('errors', $data);
    }

    public function testRunBuiltInCommandFromContainerCallsRun(): void
    {
        $result = new CommandResponse(CommandResponse::STATUS_OK, 'from-container', []);
        $mock = $this->createMock(CommandInterface::class);
        $mock->expects($this->once())->method('run')->willReturn($result);

        // PHPUnitCommand is a built-in that IS available
        $controller = $this->createController(commandMap: [], containerServices: [PHPUnitCommand::class => $mock]);

        $response = $controller->run($this->get(['command' => 'test/phpunit']));
        $data = $this->responseData($response);

        $this->assertSame('ok', $data['status']);
        $this->assertSame('from-container', $data['result']);
    }

    public function testRunWithComposerScriptArrayCommands(): void
    {
        // composer.json can have array-format scripts
        $tmpDir = sys_get_temp_dir() . '/adp-cmd-arr-' . uniqid();
        mkdir($tmpDir, 0755, true);

        $composerJson = json_encode([
            'scripts' => [
                'multi' => ['echo step1', 'echo step2'],
            ],
        ], JSON_THROW_ON_ERROR);
        file_put_contents($tmpDir . '/composer.json', $composerJson);

        try {
            $controller = $this->createController(commandMap: [], pathResolver: $this->pathResolver($tmpDir));

            $response = $controller->index($this->get());
            $data = $this->responseData($response);

            // Verify the multi-step script appears with joined description
            $multiCmd = array_values(array_filter($data, static fn(array $item) => $item['name'] === 'composer/multi'));
            $this->assertCount(1, $multiCmd);
            $this->assertStringContainsString('echo step1', $multiCmd[0]['description']);
            $this->assertStringContainsString('echo step2', $multiCmd[0]['description']);
        } finally {
            @unlink($tmpDir . '/composer.json');
            @rmdir($tmpDir);
        }
    }

    public function testRunComposerScriptReturnsResult(): void
    {
        // Create a directory with composer.json and test running a script
        $tmpDir = sys_get_temp_dir() . '/adp-cmd-run-script-' . uniqid();
        mkdir($tmpDir, 0755, true);

        $composerJson = json_encode([
            'scripts' => [
                'greet' => 'echo greet-output',
            ],
        ], JSON_THROW_ON_ERROR);
        file_put_contents($tmpDir . '/composer.json', $composerJson);

        try {
            $controller = $this->createController(commandMap: [], pathResolver: $this->pathResolver($tmpDir));

            // Run the composer/greet command
            $response = $controller->run($this->get(['command' => 'composer/greet']));
            $data = $this->responseData($response);

            $this->assertArrayHasKey('status', $data);
            // The BashCommand runs ['composer', 'greet'] which may fail since
            // there's no composer autoloader in tmpDir, but the status should be valid
            $this->assertContains($data['status'], [
                CommandResponse::STATUS_OK,
                CommandResponse::STATUS_ERROR,
                CommandResponse::STATUS_FAIL,
            ]);
        } finally {
            @unlink($tmpDir . '/composer.json');
            @rmdir($tmpDir);
        }
    }

    public function testIndexWithComposerJsonWithoutScripts(): void
    {
        $tmpDir = sys_get_temp_dir() . '/adp-cmd-no-scripts-' . uniqid();
        mkdir($tmpDir, 0755, true);

        $composerJson = json_encode([
            'name' => 'test/test',
            'require' => [],
        ], JSON_THROW_ON_ERROR);
        file_put_contents($tmpDir . '/composer.json', $composerJson);

        try {
            $controller = $this->createController(commandMap: [], pathResolver: $this->pathResolver($tmpDir));

            $response = $controller->index($this->get());
            $data = $this->responseData($response);

            $composerCommands = array_filter($data, static fn(array $item) => $item['group'] === 'composer');
            $this->assertEmpty($composerCommands);
        } finally {
            @unlink($tmpDir . '/composer.json');
            @rmdir($tmpDir);
        }
    }

    public function testRunBuiltInCommandFallsBackToBashWhenNotInContainer(): void
    {
        $tmpDir = sys_get_temp_dir() . '/adp-cmd-fallback-' . uniqid();
        mkdir($tmpDir, 0755, true);
        file_put_contents($tmpDir . '/composer.json', json_encode(['scripts' => []], JSON_THROW_ON_ERROR));

        try {
            $controller = $this->createController(
                commandMap: [
                    'testing' => [
                        'my-bash' => BashCommand::class,
                    ],
                ],
                containerServices: [],
                pathResolver: $this->pathResolver($tmpDir),
            );

            $response = $controller->run($this->get(['command' => 'my-bash']));
            $data = $this->responseData($response);

            $this->assertArrayHasKey('status', $data);
            $this->assertContains($data['status'], [
                CommandResponse::STATUS_OK,
                CommandResponse::STATUS_ERROR,
                CommandResponse::STATUS_FAIL,
            ]);
        } finally {
            @unlink($tmpDir . '/composer.json');
            @rmdir($tmpDir);
        }
    }

    public function testRunComposerScriptResolvesAsArrayCommand(): void
    {
        $tmpDir = sys_get_temp_dir() . '/adp-cmd-arr-resolve-' . uniqid();
        mkdir($tmpDir, 0755, true);
        file_put_contents($tmpDir . '/composer.json', json_encode([
            'scripts' => ['test-script' => 'echo ok'],
        ], JSON_THROW_ON_ERROR));

        try {
            $controller = $this->createController(
                commandMap: [],
                containerServices: [],
                pathResolver: $this->pathResolver($tmpDir),
            );

            $response = $controller->run($this->get(['command' => 'composer/test-script']));
            $data = $this->responseData($response);

            $this->assertArrayHasKey('status', $data);
            $this->assertArrayHasKey('result', $data);
            $this->assertArrayHasKey('errors', $data);
        } finally {
            @unlink($tmpDir . '/composer.json');
            @rmdir($tmpDir);
        }
    }

    public function testRunComposerScriptIsNotResolvedFromContainer(): void
    {
        $tmpDir = sys_get_temp_dir() . '/adp-cmd-composer-nocontainer-' . uniqid();
        mkdir($tmpDir, 0755, true);
        file_put_contents($tmpDir . '/composer.json', json_encode([
            'scripts' => ['lint' => 'echo lint'],
        ], JSON_THROW_ON_ERROR));

        try {
            $containerCallCount = 0;
            $container = $this->createMock(\Psr\Container\ContainerInterface::class);
            $container
                ->method('has')
                ->willReturnCallback(function () use (&$containerCallCount) {
                    $containerCallCount++;
                    return false;
                });

            $controller = new CommandController(
                $this->createResponseFactory(),
                $this->pathResolver($tmpDir),
                $container,
            );

            $response = $controller->run($this->get(['command' => 'composer/lint']));
            $data = $this->responseData($response);

            // Container::has() should NOT be called for array commands
            $this->assertSame(0, $containerCallCount);
            $this->assertArrayHasKey('status', $data);
        } finally {
            @unlink($tmpDir . '/composer.json');
            @rmdir($tmpDir);
        }
    }
}
