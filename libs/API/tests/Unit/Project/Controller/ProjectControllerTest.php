<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Project\Controller;

use AppDevPanel\Api\Http\JsonResponseFactory;
use AppDevPanel\Api\Project\Controller\ProjectController;
use AppDevPanel\Kernel\Project\FileProjectConfigStorage;
use AppDevPanel\Kernel\Project\ProjectConfig;
use GuzzleHttp\Psr7\HttpFactory;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Stream;
use PHPUnit\Framework\TestCase;

final class ProjectControllerTest extends TestCase
{
    private string $configDir;

    protected function setUp(): void
    {
        $this->configDir = sys_get_temp_dir() . '/adp-project-ctrl-' . uniqid('', true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->configDir);
    }

    private function createController(): ProjectController
    {
        $httpFactory = new HttpFactory();

        return new ProjectController(
            new JsonResponseFactory($httpFactory, $httpFactory),
            new FileProjectConfigStorage($this->configDir),
        );
    }

    public function testIndexReturnsEmptyConfigByDefault(): void
    {
        $response = $this->createController()->index(new ServerRequest('GET', '/'));

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(ProjectConfig::CURRENT_VERSION, $data['config']['version']);
        $this->assertSame([], $data['config']['frames']);
        $this->assertSame([], $data['config']['openapi']);
        $this->assertSame($this->configDir, $data['configDir']);
    }

    public function testUpdateAcceptsBareConfigShape(): void
    {
        $controller = $this->createController();
        $body = json_encode([
            'frames' => ['Logs' => 'https://logs.example/'],
            'openapi' => ['Main' => '/openapi.json'],
        ]);

        $request = new ServerRequest('PUT', '/')->withBody(Stream::create($body));
        $response = $controller->update($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(['Logs' => 'https://logs.example/'], $data['config']['frames']);
        $this->assertSame(['Main' => '/openapi.json'], $data['config']['openapi']);

        // Round-trip via a fresh controller / storage to confirm persistence.
        $reread = $this->createController()->index(new ServerRequest('GET', '/'));
        $rereadData = json_decode((string) $reread->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(['Logs' => 'https://logs.example/'], $rereadData['config']['frames']);
    }

    public function testUpdateAcceptsWrappedConfigShape(): void
    {
        $controller = $this->createController();
        $body = json_encode([
            'config' => [
                'frames' => ['A' => 'https://a/'],
                'openapi' => ['B' => 'https://b/'],
            ],
        ]);

        $request = new ServerRequest('PUT', '/')->withBody(Stream::create($body));
        $response = $controller->update($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(['A' => 'https://a/'], $data['config']['frames']);
        $this->assertSame(['B' => 'https://b/'], $data['config']['openapi']);
    }

    public function testUpdateRejectsInvalidJson(): void
    {
        $controller = $this->createController();
        $request = new ServerRequest('PUT', '/')->withBody(Stream::create('not json'));

        $response = $controller->update($request);

        $this->assertSame(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertStringContainsString('Invalid JSON', $data['error']);
    }

    public function testUpdateRejectsNonObjectBody(): void
    {
        $controller = $this->createController();
        $request = new ServerRequest('PUT', '/')->withBody(Stream::create('"a string"'));

        $response = $controller->update($request);

        $this->assertSame(400, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertStringContainsString('JSON object', $data['error']);
    }

    public function testUpdateNormalisesMalformedEntries(): void
    {
        $controller = $this->createController();
        $body = json_encode([
            'frames' => [
                'Good' => 'https://good/',
                'BadInt' => 42,
                '' => 'https://emptyname/',
            ],
            'openapi' => 'not-an-array',
        ]);

        $request = new ServerRequest('PUT', '/')->withBody(Stream::create($body));
        $response = $controller->update($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(['Good' => 'https://good/'], $data['config']['frames']);
        $this->assertSame([], $data['config']['openapi']);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach ((array) scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..' || !is_string($entry)) {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($path)) {
                $this->removeDir($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
