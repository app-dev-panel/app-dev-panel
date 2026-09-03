<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Controller;

use AppDevPanel\Api\ApiSecurityConfig;
use AppDevPanel\Api\Inspector\CommandResponse;
use AppDevPanel\Api\Inspector\Controller\ComposerController;
use AppDevPanel\Api\PathResolverInterface;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

final class ComposerControllerTest extends ControllerTestCase
{
    private string $fixtureDir;

    /** @var list<list<string>> argv of every command handed to the fake runner */
    private array $commands = [];

    protected function setUp(): void
    {
        $this->fixtureDir = sys_get_temp_dir() . '/adp-composer-test-' . uniqid();
        mkdir($this->fixtureDir, 0o755, true);
        $this->commands = [];
    }

    protected function tearDown(): void
    {
        if (is_dir($this->fixtureDir)) {
            $this->removeDirectory($this->fixtureDir);
        }
    }

    private function removeDirectory(string $dir): void
    {
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    /**
     * Fake process runner: records the argv and answers with the canned response.
     * No composer binary, no subprocess, no network.
     */
    private function createController(
        bool $allowDestructive = true,
        ?CommandResponse $response = null,
    ): ComposerController {
        $pathResolver = $this->createMock(PathResolverInterface::class);
        $pathResolver->method('getRootPath')->willReturn($this->fixtureDir);
        $pathResolver->method('getRuntimePath')->willReturn($this->fixtureDir . '/runtime');

        $response ??= new CommandResponse(CommandResponse::STATUS_OK, '');

        return new ComposerController(
            $this->createResponseFactory(),
            $pathResolver,
            new ApiSecurityConfig(allowDestructiveOperations: $allowDestructive),
            function (array $command) use ($response): CommandResponse {
                $this->commands[] = $command;

                return $response;
            },
        );
    }

    public function testIndexWithJsonAndLock(): void
    {
        file_put_contents($this->fixtureDir . '/composer.json', json_encode([
            'name' => 'test/app',
            'require' => ['php' => '>=8.4'],
        ]));
        file_put_contents($this->fixtureDir . '/composer.lock', json_encode([
            'packages' => [],
        ]));

        $controller = $this->createController();
        $response = $controller->index($this->get());

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame('test/app', $data['json']['name']);
        $this->assertArrayHasKey('lock', $data);
        $this->assertIsArray($data['lock']);
        $this->assertSame([], $this->commands);
    }

    public function testIndexWithJsonOnly(): void
    {
        file_put_contents($this->fixtureDir . '/composer.json', json_encode([
            'name' => 'test/no-lock',
        ]));

        $controller = $this->createController();
        $response = $controller->index($this->get());

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertNull($data['lock']);
    }

    public function testIndexNoComposerJson(): void
    {
        $controller = $this->createController();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('composer.json');
        $controller->index($this->get());
    }

    public function testIndexWithInvalidJson(): void
    {
        file_put_contents($this->fixtureDir . '/composer.json', '{not valid json}');

        $controller = $this->createController();

        $this->expectException(\JsonException::class);
        $controller->index($this->get());
    }

    public function testIndexWithInvalidLockJson(): void
    {
        file_put_contents($this->fixtureDir . '/composer.json', json_encode(['name' => 'test/app']));
        file_put_contents($this->fixtureDir . '/composer.lock', 'broken json');

        $controller = $this->createController();

        $this->expectException(\JsonException::class);
        $controller->index($this->get());
    }

    public function testIndexWithComplexComposerJson(): void
    {
        $composerJson = [
            'name' => 'test/complex',
            'require' => ['php' => '^8.4', 'vendor/a' => '^1.0'],
            'require-dev' => ['phpunit/phpunit' => '^11.0'],
            'autoload' => ['psr-4' => ['App\\' => 'src/']],
            'scripts' => ['test' => 'phpunit'],
        ];
        file_put_contents($this->fixtureDir . '/composer.json', json_encode($composerJson));

        $controller = $this->createController();
        $response = $controller->index($this->get());

        $data = $this->responseData($response);
        $this->assertSame('test/complex', $data['json']['name']);
        $this->assertArrayHasKey('require', $data['json']);
        $this->assertArrayHasKey('require-dev', $data['json']);
        $this->assertArrayHasKey('scripts', $data['json']);
        $this->assertNull($data['lock']);
    }

    public function testIndexWithLockContainingPackages(): void
    {
        file_put_contents($this->fixtureDir . '/composer.json', json_encode(['name' => 'test/app']));
        file_put_contents($this->fixtureDir . '/composer.lock', json_encode([
            'packages' => [
                ['name' => 'vendor/lib', 'version' => '1.0.0'],
                ['name' => 'vendor/other', 'version' => '2.3.4'],
            ],
            'packages-dev' => [
                ['name' => 'dev/tool', 'version' => '0.9.0'],
            ],
        ]));

        $controller = $this->createController();
        $response = $controller->index($this->get());

        $data = $this->responseData($response);
        $this->assertCount(2, $data['lock']['packages']);
        $this->assertSame('vendor/lib', $data['lock']['packages'][0]['name']);
        $this->assertCount(1, $data['lock']['packages-dev']);
    }

    public function testInspectMissingPackage(): void
    {
        $controller = $this->createController();

        try {
            $controller->inspect($this->get());
            $this->fail('Expected InvalidArgumentException.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('package', $e->getMessage());
        }

        $this->assertSame([], $this->commands, 'No command must run without a package name.');
    }

    public function testInspectRunsComposerShowAndDecodesJson(): void
    {
        $controller = $this->createController(response: new CommandResponse(
            CommandResponse::STATUS_OK,
            '{"name":"phpunit/phpunit","versions":["11.5.0"],"licenses":[{"osi":"BSD-3-Clause"}]}',
        ));

        $response = $controller->inspect($this->get(['package' => 'phpunit/phpunit']));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([['composer', 'show', 'phpunit/phpunit', '--all', '--format=json']], $this->commands);
        $data = $this->responseData($response);
        $this->assertSame('ok', $data['status']);
        $this->assertSame('phpunit/phpunit', $data['result']['name']);
        $this->assertSame(['11.5.0'], $data['result']['versions']);
        $this->assertSame([], $data['errors']);
    }

    public function testInspectStripsComposerNoticesAroundJson(): void
    {
        $controller = $this->createController(response: new CommandResponse(
            CommandResponse::STATUS_OK,
            "Do not run Composer as root/super user! See https://getcomposer.org/root for details\n"
            . "{\"name\":\"vendor/pkg\",\"description\":\"a {braced} description\"}\n"
            . "Continue as root/super user [yes]? \n",
        ));

        $data = $this->responseData($controller->inspect($this->get(['package' => 'vendor/pkg'])));

        $this->assertSame('ok', $data['status']);
        $this->assertSame(['name' => 'vendor/pkg', 'description' => 'a {braced} description'], $data['result']);
    }

    public function testInspectFailurePassesErrorsThrough(): void
    {
        $controller = $this->createController(
            response: new CommandResponse(CommandResponse::STATUS_FAIL, null, ['Package "nonexistent/pkg" not found']),
        );

        $data = $this->responseData($controller->inspect($this->get(['package' => 'nonexistent/pkg'])));

        $this->assertSame([['composer', 'show', 'nonexistent/pkg', '--all', '--format=json']], $this->commands);
        $this->assertSame('fail', $data['status']);
        $this->assertNull($data['result']);
        $this->assertSame(['Package "nonexistent/pkg" not found'], $data['errors']);
    }

    public function testInspectErrorStatusReturnsNullResult(): void
    {
        $controller = $this->createController(response: new CommandResponse(
            CommandResponse::STATUS_ERROR,
            '[InvalidArgumentException] Package nonexistent/pkg not found',
        ));

        $data = $this->responseData($controller->inspect($this->get(['package' => 'nonexistent/pkg'])));

        $this->assertSame('error', $data['status']);
        $this->assertNull($data['result']);
    }

    public function testInspectWithNonJsonOutputThrows(): void
    {
        $controller = $this->createController(response: new CommandResponse(
            CommandResponse::STATUS_OK,
            'composer: command not found',
        ));

        $this->expectException(\JsonException::class);
        $controller->inspect($this->get(['package' => 'vendor/pkg']));
    }

    public function testRequireMissingPackage(): void
    {
        $controller = $this->createController();

        try {
            $controller->require($this->post([]));
            $this->fail('Expected InvalidArgumentException.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('package', $e->getMessage());
        }

        $this->assertSame([], $this->commands);
    }

    public function testRequireWithInvalidBodyJson(): void
    {
        $request = new \Nyholm\Psr7\ServerRequest('POST', '/test');
        $request = $request
            ->withHeader('Content-Type', 'application/json')
            ->withBody(\Nyholm\Psr7\Stream::create('{not valid json}'));

        $controller = $this->createController();

        $this->expectException(\JsonException::class);
        $controller->require($request);
    }

    public function testRequireWithNullPackageInBody(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('package');
        $controller->require($this->post(['package' => null]));
    }

    /**
     * @return iterable<string, array{array<string, mixed>, list<string>}>
     */
    public static function requireCommandLines(): iterable
    {
        yield 'version' => [
            ['package' => 'vendor/pkg', 'version' => '1.0.0', 'isDev' => false],
            ['composer', 'require', 'vendor/pkg:1.0.0', '-n'],
        ];
        yield 'no version' => [
            ['package' => 'vendor/pkg'],
            ['composer', 'require', 'vendor/pkg:*', '-n'],
        ];
        yield 'null version' => [
            ['package' => 'vendor/pkg', 'version' => null],
            ['composer', 'require', 'vendor/pkg:*', '-n'],
        ];
        yield 'dev' => [
            ['package' => 'vendor/tool', 'version' => '^2.0', 'isDev' => true],
            ['composer', 'require', 'vendor/tool:^2.0', '-n', '--dev'],
        ];
    }

    #[DataProvider('requireCommandLines')]
    public function testRequireBuildsExactCommandLine(array $body, array $expectedCommand): void
    {
        $controller = $this->createController(response: new CommandResponse(
            CommandResponse::STATUS_OK,
            "./composer.json has been updated\nNothing to install, update or remove\n",
        ));

        $response = $controller->require($this->post($body));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([$expectedCommand], $this->commands);
        $data = $this->responseData($response);
        $this->assertSame('ok', $data['status']);
        $this->assertSame("./composer.json has been updated\nNothing to install, update or remove\n", $data['result']);
        $this->assertSame([], $data['errors']);
    }

    public function testRequireDecodesJsonOutput(): void
    {
        $controller = $this->createController(response: new CommandResponse(
            CommandResponse::STATUS_OK,
            '{"installed":["vendor/pkg"]}',
        ));

        $data = $this->responseData($controller->require($this->post(['package' => 'vendor/pkg'])));

        $this->assertSame('ok', $data['status']);
        $this->assertSame(['installed' => ['vendor/pkg']], $data['result']);
    }

    public function testRequireErrorReturnsRawOutput(): void
    {
        $controller = $this->createController(response: new CommandResponse(
            CommandResponse::STATUS_ERROR,
            'Could not find a matching version of package nonexistent/pkg',
        ));

        $data = $this->responseData($controller->require($this->post([
            'package' => 'nonexistent/pkg',
            'version' => '1.0.0',
        ])));

        $this->assertSame([['composer', 'require', 'nonexistent/pkg:1.0.0', '-n']], $this->commands);
        $this->assertSame('error', $data['status']);
        $this->assertSame('Could not find a matching version of package nonexistent/pkg', $data['result']);
    }

    public function testRequireFailurePassesErrorsThrough(): void
    {
        $controller = $this->createController(
            response: new CommandResponse(CommandResponse::STATUS_FAIL, null, ['Command timed out after 120 seconds.']),
        );

        $data = $this->responseData($controller->require($this->post(['package' => 'vendor/pkg'])));

        $this->assertSame('fail', $data['status']);
        $this->assertNull($data['result']);
        $this->assertSame(['Command timed out after 120 seconds.'], $data['errors']);
    }

    public function testRequireReturns403WhenDestructiveOperationsDisabled(): void
    {
        $controller = $this->createController(allowDestructive: false);

        $response = $controller->require($this->post([
            'package' => 'vendor/something',
            'version' => '1.0.0',
        ]));

        $this->assertSame(403, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame('error', $data['status']);
        $this->assertNotEmpty($data['errors']);
        $this->assertStringContainsString('allowDestructiveOperations', $data['errors'][0]);
        $this->assertSame([], $this->commands, 'The guard must short-circuit before any command runs.');
    }

    public function testInspectIsNotGuardedByDestructiveFlag(): void
    {
        $controller = $this->createController(
            allowDestructive: false,
            response: new CommandResponse(CommandResponse::STATUS_OK, '{"name":"vendor/pkg"}'),
        );

        $data = $this->responseData($controller->inspect($this->get(['package' => 'vendor/pkg'])));

        $this->assertSame('ok', $data['status']);
        $this->assertCount(1, $this->commands);
    }
}
