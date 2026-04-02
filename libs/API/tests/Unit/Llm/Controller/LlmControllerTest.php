<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Llm\Controller;

use AppDevPanel\Api\Http\JsonResponseFactory;
use AppDevPanel\Api\Llm\Controller\LlmController;
use AppDevPanel\Api\Llm\FileLlmHistoryStorage;
use AppDevPanel\Api\Llm\FileLlmSettings;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Stream;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

final class LlmControllerTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/adp-llm-ctrl-' . uniqid();
        mkdir($this->tmpDir, 0o755, true);
    }

    protected function tearDown(): void
    {
        @unlink($this->tmpDir . '/.llm-settings.json');
        @unlink($this->tmpDir . '/.llm-history.json');
        @rmdir($this->tmpDir);
    }

    private function createController(?ClientInterface $httpClient = null): LlmController
    {
        $httpFactory = new HttpFactory();
        $responseFactory = new JsonResponseFactory($httpFactory, $httpFactory);
        $settings = new FileLlmSettings($this->tmpDir);
        $history = new FileLlmHistoryStorage($this->tmpDir);

        return new LlmController(
            $responseFactory,
            $settings,
            $httpClient ?? $this->mockHttpClient(new Response(200, [], '{}')),
            $httpFactory,
            $httpFactory,
            $history,
        );
    }

    private function connectedController(?ClientInterface $httpClient = null): LlmController
    {
        $settings = new FileLlmSettings($this->tmpDir);
        $settings->setApiKey('sk-test-key');
        $settings->setProvider('openrouter');

        $httpFactory = new HttpFactory();
        $responseFactory = new JsonResponseFactory($httpFactory, $httpFactory);
        $history = new FileLlmHistoryStorage($this->tmpDir);

        return new LlmController(
            $responseFactory,
            $settings,
            $httpClient ?? $this->mockHttpClient(new Response(200, [], '{}')),
            $httpFactory,
            $httpFactory,
            $history,
        );
    }

    private function mockHttpClient(Response $response): ClientInterface
    {
        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')->willReturn($response);

        return $client;
    }

    private function post(array $body): ServerRequest
    {
        $request = new ServerRequest('POST', '/test');
        return $request->withBody(Stream::create(json_encode($body, JSON_THROW_ON_ERROR)));
    }

    private function responseData(\Psr\Http\Message\ResponseInterface $response): array
    {
        return json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    public function testStatus(): void
    {
        $controller = $this->createController();
        $response = $controller->status(new ServerRequest('GET', '/'));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertFalse($data['connected']);
    }

    public function testConnect(): void
    {
        $controller = $this->createController();
        $response = $controller->connect($this->post([
            'provider' => 'anthropic',
            'apiKey' => 'sk-ant-test',
        ]));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertTrue($data['connected']);
        $this->assertSame('anthropic', $data['provider']);
    }

    public function testConnectMissingProvider(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('provider');
        $controller->connect($this->post(['apiKey' => 'key']));
    }

    public function testConnectMissingApiKey(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('apiKey');
        $controller->connect($this->post(['provider' => 'anthropic']));
    }

    public function testDisconnect(): void
    {
        $controller = $this->connectedController();
        $response = $controller->disconnect(new ServerRequest('POST', '/'));

        $data = $this->responseData($response);
        $this->assertFalse($data['connected']);
    }

    public function testSetModel(): void
    {
        $controller = $this->createController();
        $response = $controller->setModel($this->post(['model' => 'claude-3-opus']));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame('claude-3-opus', $data['model']);
    }

    public function testSetModelMissing(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $controller->setModel($this->post([]));
    }

    public function testSetTimeout(): void
    {
        $controller = $this->createController();
        $response = $controller->setTimeout($this->post(['timeout' => 60]));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame(60, $data['timeout']);
    }

    public function testSetTimeoutMissing(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $controller->setTimeout($this->post([]));
    }

    public function testSetCustomPrompt(): void
    {
        $controller = $this->createController();
        $response = $controller->setCustomPrompt($this->post(['customPrompt' => 'Be helpful']));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame('Be helpful', $data['customPrompt']);
    }

    public function testSetCustomPromptMissing(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $controller->setCustomPrompt($this->post([]));
    }

    public function testModelsNotConnected(): void
    {
        $controller = $this->createController();
        $response = $controller->models(new ServerRequest('GET', '/'));

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testChatNotConnected(): void
    {
        $controller = $this->createController();
        $response = $controller->chat($this->post(['messages' => [['role' => 'user', 'content' => 'hi']]]));

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testChatMissingMessages(): void
    {
        $controller = $this->connectedController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('messages');
        $controller->chat($this->post([]));
    }

    public function testAnalyzeNotConnected(): void
    {
        $controller = $this->createController();
        $response = $controller->analyze($this->post(['context' => ['data' => 'test']]));

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testAnalyzeMissingContext(): void
    {
        $controller = $this->connectedController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('context');
        $controller->analyze($this->post([]));
    }

    public function testHistory(): void
    {
        $controller = $this->createController();
        $response = $controller->history(new ServerRequest('GET', '/'));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame([], $data);
    }

    public function testAddHistory(): void
    {
        $controller = $this->createController();
        $response = $controller->addHistory($this->post([
            'query' => 'What is the error?',
            'response' => 'NullPointerException',
            'timestamp' => 1000,
        ]));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertCount(1, $data);
        $this->assertSame('What is the error?', $data[0]['query']);
    }

    public function testAddHistoryMissingQuery(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('query');
        $controller->addHistory($this->post(['response' => 'r']));
    }

    public function testAddHistoryWithError(): void
    {
        $controller = $this->createController();
        $response = $controller->addHistory($this->post([
            'query' => 'q',
            'response' => '',
            'timestamp' => 1000,
            'error' => 'API timeout',
        ]));

        $data = $this->responseData($response);
        $this->assertSame('API timeout', $data[0]['error']);
    }

    public function testDeleteHistory(): void
    {
        $controller = $this->createController();
        $controller->addHistory($this->post(['query' => 'q1', 'response' => 'r1', 'timestamp' => 1]));
        $controller->addHistory($this->post(['query' => 'q2', 'response' => 'r2', 'timestamp' => 2]));

        $request = new ServerRequest('DELETE', '/');
        $request = $request->withAttribute('index', '0');
        $response = $controller->deleteHistory($request);

        $data = $this->responseData($response);
        $this->assertCount(1, $data);
    }

    public function testClearHistory(): void
    {
        $controller = $this->createController();
        $controller->addHistory($this->post(['query' => 'q', 'response' => 'r', 'timestamp' => 1]));

        $response = $controller->clearHistory(new ServerRequest('DELETE', '/'));

        $data = $this->responseData($response);
        $this->assertSame([], $data);
    }

    public function testOauthInitiate(): void
    {
        $controller = $this->createController();
        $response = $controller->oauthInitiate($this->post([
            'callbackUrl' => 'http://localhost:3000/callback',
        ]));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertArrayHasKey('authUrl', $data);
        $this->assertArrayHasKey('codeVerifier', $data);
        $this->assertStringContainsString('openrouter.ai/auth', $data['authUrl']);
        $this->assertStringContainsString('callback_url', $data['authUrl']);
    }

    public function testOauthInitiateMissingCallback(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('callbackUrl');
        $controller->oauthInitiate($this->post([]));
    }

    public function testOauthExchangeSuccess(): void
    {
        $httpClient = $this->mockHttpClient(new Response(200, [], json_encode(['key' => 'sk-or-new-key'])));
        $controller = $this->createController($httpClient);

        $response = $controller->oauthExchange($this->post([
            'code' => 'auth-code-123',
            'codeVerifier' => 'verifier-abc',
        ]));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertTrue($data['connected']);
    }

    public function testOauthExchangeFailure(): void
    {
        $httpClient = $this->mockHttpClient(new Response(400, [], json_encode(['error' => 'invalid_code'])));
        $controller = $this->createController($httpClient);

        $response = $controller->oauthExchange($this->post([
            'code' => 'bad-code',
            'codeVerifier' => 'verifier',
        ]));

        $this->assertSame(400, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertFalse($data['connected']);
    }

    public function testOauthExchangeMissingCode(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('code');
        $controller->oauthExchange($this->post(['codeVerifier' => 'v']));
    }

    public function testOauthExchangeMissingCodeVerifier(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('codeVerifier');
        $controller->oauthExchange($this->post(['code' => 'c']));
    }

    public function testModelsOpenRouterConnected(): void
    {
        $httpClient = $this->mockHttpClient(new Response(200, [], json_encode([
            'data' => [
                ['id' => 'anthropic/claude-3', 'name' => 'Claude 3', 'context_length' => 200000, 'pricing' => []],
            ],
        ])));

        $controller = $this->connectedController($httpClient);
        $response = $controller->models(new ServerRequest('GET', '/'));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertArrayHasKey('models', $data);
        $this->assertCount(1, $data['models']);
        $this->assertSame('anthropic/claude-3', $data['models'][0]['id']);
    }

    public function testModelsAnthropicProvider(): void
    {
        $settings = new FileLlmSettings($this->tmpDir);
        $settings->setApiKey('sk-ant-test');
        $settings->setProvider('anthropic');

        $httpClient = $this->mockHttpClient(new Response(200, [], json_encode([
            'data' => [
                ['id' => 'claude-sonnet-4', 'display_name' => 'Claude Sonnet 4', 'context_window' => 200000],
            ],
        ])));

        $httpFactory = new HttpFactory();
        $controller = new LlmController(
            new JsonResponseFactory($httpFactory, $httpFactory),
            $settings,
            $httpClient,
            $httpFactory,
            $httpFactory,
            new FileLlmHistoryStorage($this->tmpDir),
        );

        $response = $controller->models(new ServerRequest('GET', '/'));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertArrayHasKey('models', $data);
    }

    public function testConnectWithEmptyProvider(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $controller->connect($this->post(['provider' => '', 'apiKey' => 'key']));
    }

    public function testConnectWithEmptyApiKey(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $controller->connect($this->post(['provider' => 'anthropic', 'apiKey' => '']));
    }

    public function testChatEmptyMessages(): void
    {
        $controller = $this->connectedController();

        $this->expectException(InvalidArgumentException::class);
        $controller->chat($this->post(['messages' => []]));
    }

    public function testAddHistoryWithEmptyError(): void
    {
        $controller = $this->createController();
        $response = $controller->addHistory($this->post([
            'query' => 'q',
            'response' => 'r',
            'timestamp' => 1000,
            'error' => '', // empty error should not be stored
        ]));

        $data = $this->responseData($response);
        $this->assertArrayNotHasKey('error', $data[0]);
    }

    public function testOauthExchangeUnknownError(): void
    {
        $httpClient = $this->mockHttpClient(new Response(200, [], json_encode(['something' => 'unexpected'])));
        $controller = $this->createController($httpClient);

        $response = $controller->oauthExchange($this->post([
            'code' => 'code',
            'codeVerifier' => 'verifier',
        ]));

        $this->assertSame(400, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertStringContainsString('Unknown error', $data['error']);
    }

    public function testModelsOpenAiProvider(): void
    {
        $settings = new FileLlmSettings($this->tmpDir);
        $settings->setApiKey('sk-openai-test');
        $settings->setProvider('openai');

        $httpClient = $this->mockHttpClient(new Response(200, [], json_encode([
            'data' => [
                ['id' => 'gpt-4o', 'name' => 'GPT-4o'],
                ['id' => 'text-davinci-003', 'name' => 'Davinci'],
                ['id' => 'o1-preview', 'name' => 'o1 Preview'],
            ],
        ])));

        $httpFactory = new HttpFactory();
        $controller = new LlmController(
            new JsonResponseFactory($httpFactory, $httpFactory),
            $settings,
            $httpClient,
            $httpFactory,
            $httpFactory,
            new FileLlmHistoryStorage($this->tmpDir),
        );

        $response = $controller->models(new ServerRequest('GET', '/'));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertArrayHasKey('models', $data);
        // Should only include gpt-*, o*, chatgpt-* models
        $ids = array_column($data['models'], 'id');
        $this->assertContains('gpt-4o', $ids);
        $this->assertContains('o1-preview', $ids);
        $this->assertNotContains('text-davinci-003', $ids);
    }
}
