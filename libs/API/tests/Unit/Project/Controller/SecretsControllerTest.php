<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Project\Controller;

use AppDevPanel\Api\Http\JsonResponseFactory;
use AppDevPanel\Api\Project\Controller\SecretsController;
use AppDevPanel\Kernel\Project\FileSecretsStorage;
use AppDevPanel\Kernel\Project\SecretsConfig;
use GuzzleHttp\Psr7\HttpFactory;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Stream;
use PHPUnit\Framework\TestCase;

final class SecretsControllerTest extends TestCase
{
    private string $configDir;

    protected function setUp(): void
    {
        $this->configDir = sys_get_temp_dir() . '/adp-secrets-ctrl-' . uniqid('', true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->configDir);
    }

    private function createController(): SecretsController
    {
        $httpFactory = new HttpFactory();

        return new SecretsController(
            new JsonResponseFactory($httpFactory, $httpFactory),
            new FileSecretsStorage($this->configDir),
        );
    }

    private function decodeBody(\Psr\Http\Message\ResponseInterface $response): array
    {
        return json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    public function testIndexReturnsEmptyByDefault(): void
    {
        $response = $this->createController()->index(new ServerRequest('GET', '/'));

        self::assertSame(200, $response->getStatusCode());
        $body = $this->decodeBody($response);
        // Even on a fresh install, presence flags are surfaced so the UI can
        // toggle "configured / empty" without an extra round-trip.
        self::assertNull($body['secrets']['apiKey']);
        self::assertFalse($body['secrets']['hasApiKey']);
        self::assertFalse($body['secrets']['hasAcpArgs']);
        self::assertSame([], $body['secrets']['acpEnv']);
        self::assertSame([], $body['secrets']['acpArgs']);
        self::assertSame($this->configDir, $body['configDir']);
    }

    public function testIndexMasksApiKey(): void
    {
        // Seed the file directly so we know exactly what's stored.
        new FileSecretsStorage($this->configDir)->save(SecretsConfig::fromArray(['llm' => [
            'apiKey' => 'sk-secret-abcdwxyz',
        ]]));

        $body = $this->decodeBody($this->createController()->index(new ServerRequest('GET', '/')));

        self::assertSame('...wxyz', $body['secrets']['apiKey']);
        self::assertTrue($body['secrets']['hasApiKey']);
    }

    public function testIndexExposesNonSecretFieldsUnchanged(): void
    {
        new FileSecretsStorage($this->configDir)->save(SecretsConfig::fromArray([
            'llm' => [
                'apiKey' => 'sk-aaaa-bbbb',
                'provider' => 'anthropic',
                'model' => 'claude-3-opus',
                'timeout' => 45,
                'customPrompt' => 'Be concise',
                'acpCommand' => 'claude',
            ],
        ]));

        $body = $this->decodeBody($this->createController()->index(new ServerRequest('GET', '/')));

        self::assertSame('anthropic', $body['secrets']['provider']);
        self::assertSame('claude-3-opus', $body['secrets']['model']);
        self::assertSame(45, $body['secrets']['timeout']);
        self::assertSame('Be concise', $body['secrets']['customPrompt']);
        self::assertSame('claude', $body['secrets']['acpCommand']);
    }

    public function testIndexMasksAcpEnvAndArgs(): void
    {
        new FileSecretsStorage($this->configDir)->save(SecretsConfig::fromArray([
            'llm' => [
                'acpEnv' => ['ANTHROPIC_API_KEY' => 'sk-very-secret', 'EMPTY' => ''],
                'acpArgs' => ['--api-key=sk-cli-secret', '--model=opus'],
            ],
        ]));

        $body = $this->decodeBody($this->createController()->index(new ServerRequest('GET', '/')));

        self::assertSame('...cret', $body['secrets']['acpEnv']['ANTHROPIC_API_KEY']);
        self::assertSame('', $body['secrets']['acpEnv']['EMPTY']);
        self::assertSame('...cret', $body['secrets']['acpArgs'][0]);
        self::assertSame('...opus', $body['secrets']['acpArgs'][1]);
        self::assertTrue($body['secrets']['hasAcpArgs']);
    }

    public function testIndexFullyObfuscatesShortSecrets(): void
    {
        new FileSecretsStorage($this->configDir)->save(SecretsConfig::fromArray(['llm' => ['apiKey' => 'abcd']]));

        $body = $this->decodeBody($this->createController()->index(new ServerRequest('GET', '/')));

        // Strings <= 4 chars are masked entirely so we don't reveal the whole secret.
        self::assertSame('...', $body['secrets']['apiKey']);
    }

    public function testPatchMergesNewFields(): void
    {
        $controller = $this->createController();
        $request = new ServerRequest('PATCH', '/')->withBody(Stream::create(json_encode(['llm' => [
            'apiKey' => 'sk-new-aaaaaaaa',
        ]])));

        $response = $controller->patch($request);
        self::assertSame(200, $response->getStatusCode());

        $loaded = new FileSecretsStorage($this->configDir)->load();
        self::assertSame('sk-new-aaaaaaaa', $loaded->llm['apiKey']);
    }

    public function testPatchPreservesUnmentionedFields(): void
    {
        new FileSecretsStorage($this->configDir)->save(SecretsConfig::fromArray(['llm' => [
            'apiKey' => 'sk-keep',
            'model' => 'gpt-4',
        ]]));

        $controller = $this->createController();
        $request = new ServerRequest('PATCH', '/')->withBody(Stream::create(json_encode(['llm' => ['timeout' => 60]])));
        $controller->patch($request);

        $loaded = new FileSecretsStorage($this->configDir)->load();
        self::assertSame('sk-keep', $loaded->llm['apiKey']);
        self::assertSame('gpt-4', $loaded->llm['model']);
        self::assertSame(60, $loaded->llm['timeout']);
    }

    public function testPatchNullValueDeletesKey(): void
    {
        new FileSecretsStorage($this->configDir)->save(SecretsConfig::fromArray(['llm' => [
            'apiKey' => 'sk-old-aaaa',
            'model' => 'gpt-4',
        ]]));

        $controller = $this->createController();
        $request = new ServerRequest('PATCH', '/')->withBody(Stream::create(json_encode(['llm' => [
            'apiKey' => null,
        ]])));
        $controller->patch($request);

        $loaded = new FileSecretsStorage($this->configDir)->load();
        self::assertArrayNotHasKey('apiKey', $loaded->llm);
        self::assertSame('gpt-4', $loaded->llm['model']);
    }

    public function testPatchRejectsInvalidJson(): void
    {
        $controller = $this->createController();
        $request = new ServerRequest('PATCH', '/')->withBody(Stream::create('not json'));

        $response = $controller->patch($request);
        self::assertSame(400, $response->getStatusCode());
        self::assertStringContainsString('Invalid JSON', $this->decodeBody($response)['error']);
    }

    public function testPatchRejectsNonObjectBody(): void
    {
        $controller = $this->createController();
        $request = new ServerRequest('PATCH', '/')->withBody(Stream::create('"a string"'));

        $response = $controller->patch($request);
        self::assertSame(400, $response->getStatusCode());
        self::assertStringContainsString('JSON object', $this->decodeBody($response)['error']);
    }

    public function testPatchResponseIsMasked(): void
    {
        $controller = $this->createController();
        $request = new ServerRequest('PATCH', '/')->withBody(Stream::create(json_encode(['llm' => [
            'apiKey' => 'sk-fresh-12345678',
        ]])));

        $body = $this->decodeBody($controller->patch($request));

        // The PATCH response — like GET — must NOT echo the apiKey back unmasked.
        self::assertSame('...5678', $body['secrets']['apiKey']);
        self::assertTrue($body['secrets']['hasApiKey']);
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
