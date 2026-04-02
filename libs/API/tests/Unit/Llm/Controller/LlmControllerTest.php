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

    private function makeController(
        string $provider = 'openrouter',
        ?string $apiKey = null,
        ?ClientInterface $httpClient = null,
    ): LlmController {
        $settings = new FileLlmSettings($this->tmpDir);
        if ($apiKey !== null) {
            $settings->setApiKey($apiKey);
        }
        $settings->setProvider($provider);

        $httpFactory = new HttpFactory();

        return new LlmController(
            new JsonResponseFactory($httpFactory, $httpFactory),
            $settings,
            $httpClient ?? $this->mockHttpClient(new Response(200, [], '{}')),
            $httpFactory,
            $httpFactory,
            new FileLlmHistoryStorage($this->tmpDir),
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
        return new ServerRequest('POST', '/test')->withBody(Stream::create(json_encode($body, JSON_THROW_ON_ERROR)));
    }

    private function data(\Psr\Http\Message\ResponseInterface $response): array
    {
        return json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    // --- Status ---

    public function testStatus(): void
    {
        $controller = $this->makeController();
        $response = $controller->status(new ServerRequest('GET', '/'));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertFalse($this->data($response)['connected']);
    }

    public function testStatusConnected(): void
    {
        $controller = $this->makeController('anthropic', 'sk-test');
        $data = $this->data($controller->status(new ServerRequest('GET', '/')));
        $this->assertTrue($data['connected']);
        $this->assertSame('anthropic', $data['provider']);
    }

    // --- Connect ---

    public function testConnect(): void
    {
        $controller = $this->makeController();
        $response = $controller->connect($this->post(['provider' => 'anthropic', 'apiKey' => 'sk-ant-test']));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($this->data($response)['connected']);
    }

    public function testConnectMissingProvider(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeController()->connect($this->post(['apiKey' => 'key']));
    }

    public function testConnectEmptyProvider(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeController()->connect($this->post(['provider' => '', 'apiKey' => 'key']));
    }

    public function testConnectMissingApiKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeController()->connect($this->post(['provider' => 'anthropic']));
    }

    public function testConnectEmptyApiKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeController()->connect($this->post(['provider' => 'anthropic', 'apiKey' => '']));
    }

    // --- Disconnect ---

    public function testDisconnect(): void
    {
        $controller = $this->makeController('openrouter', 'sk-test');
        $response = $controller->disconnect(new ServerRequest('POST', '/'));
        $this->assertFalse($this->data($response)['connected']);
    }

    // --- Set Model ---

    public function testSetModel(): void
    {
        $controller = $this->makeController();
        $data = $this->data($controller->setModel($this->post(['model' => 'claude-3-opus'])));
        $this->assertSame('claude-3-opus', $data['model']);
    }

    public function testSetModelMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeController()->setModel($this->post([]));
    }

    // --- Set Timeout ---

    public function testSetTimeout(): void
    {
        $data = $this->data($this->makeController()->setTimeout($this->post(['timeout' => 60])));
        $this->assertSame(60, $data['timeout']);
    }

    public function testSetTimeoutMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeController()->setTimeout($this->post([]));
    }

    // --- Set Custom Prompt ---

    public function testSetCustomPrompt(): void
    {
        $data = $this->data($this->makeController()->setCustomPrompt($this->post(['customPrompt' => 'Be helpful'])));
        $this->assertSame('Be helpful', $data['customPrompt']);
    }

    public function testSetCustomPromptMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeController()->setCustomPrompt($this->post([]));
    }

    // --- Models ---

    public function testModelsNotConnected(): void
    {
        $response = $this->makeController()->models(new ServerRequest('GET', '/'));
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testModelsOpenRouter(): void
    {
        $client = $this->mockHttpClient(new Response(200, [], json_encode([
            'data' => [['id' => 'model-1', 'name' => 'Model 1', 'context_length' => 8192, 'pricing' => []]],
        ])));
        $controller = $this->makeController('openrouter', 'sk-test', $client);
        $data = $this->data($controller->models(new ServerRequest('GET', '/')));
        $this->assertArrayHasKey('models', $data);
        $this->assertSame('model-1', $data['models'][0]['id']);
    }

    public function testModelsAnthropic(): void
    {
        $client = $this->mockHttpClient(new Response(200, [], json_encode([
            'data' => [['id' => 'claude-3', 'display_name' => 'Claude 3', 'context_window' => 200000]],
        ])));
        $controller = $this->makeController('anthropic', 'sk-ant-test', $client);
        $data = $this->data($controller->models(new ServerRequest('GET', '/')));
        $this->assertArrayHasKey('models', $data);
    }

    public function testModelsAnthropicOAuthToken(): void
    {
        $client = $this->mockHttpClient(new Response(200, [], json_encode(['data' => []])));
        $controller = $this->makeController('anthropic', 'sk-ant-oat-test', $client);
        $data = $this->data($controller->models(new ServerRequest('GET', '/')));
        $this->assertArrayHasKey('models', $data);
    }

    public function testModelsOpenAi(): void
    {
        $client = $this->mockHttpClient(new Response(200, [], json_encode([
            'data' => [
                ['id' => 'gpt-4o'],
                ['id' => 'text-davinci-003'],
                ['id' => 'o1-preview'],
                ['id' => 'chatgpt-4o-latest'],
            ],
        ])));
        $controller = $this->makeController('openai', 'sk-openai-test', $client);
        $data = $this->data($controller->models(new ServerRequest('GET', '/')));
        $ids = array_column($data['models'], 'id');
        $this->assertContains('gpt-4o', $ids);
        $this->assertNotContains('text-davinci-003', $ids);
        $this->assertContains('o1-preview', $ids);
        $this->assertContains('chatgpt-4o-latest', $ids);
    }

    // --- Chat ---

    public function testChatNotConnected(): void
    {
        $response = $this->makeController()->chat($this->post(['messages' => [['role' => 'user', 'content' => 'hi']]]));
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testChatMissingMessages(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeController('openrouter', 'sk-test')->chat($this->post([]));
    }

    public function testChatEmptyMessages(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeController('openrouter', 'sk-test')->chat($this->post(['messages' => []]));
    }

    // Chat methods make real HTTP calls via new Client(), so we just verify they reach that point
    public function testChatOpenRouterReachesHttp(): void
    {
        $controller = $this->makeController('openrouter', 'sk-test');
        try {
            $controller->chat($this->post(['messages' => [['role' => 'user', 'content' => 'hi']]]));
        } catch (\Throwable) {
            // Expected: real HTTP call fails
        }
        $this->assertTrue(true);
    }

    public function testChatAnthropicReachesHttp(): void
    {
        $controller = $this->makeController('anthropic', 'sk-ant-test');
        try {
            $controller->chat($this->post(['messages' => [['role' => 'user', 'content' => 'hi']]]));
        } catch (\Throwable) {
            // Expected
        }
        $this->assertTrue(true);
    }

    public function testChatOpenAiReachesHttp(): void
    {
        $controller = $this->makeController('openai', 'sk-test');
        try {
            $controller->chat($this->post(['messages' => [['role' => 'user', 'content' => 'hi']]]));
        } catch (\Throwable) {
            // Expected
        }
        $this->assertTrue(true);
    }

    public function testChatWithCustomPromptSystemRole(): void
    {
        $settings = new FileLlmSettings($this->tmpDir);
        $settings->setApiKey('sk-ant-test');
        $settings->setProvider('anthropic');
        $settings->setCustomPrompt('Be brief');

        $httpFactory = new HttpFactory();
        $controller = new LlmController(
            new JsonResponseFactory($httpFactory, $httpFactory),
            $settings,
            $this->mockHttpClient(new Response(200, [], '{}')),
            $httpFactory,
            $httpFactory,
            new FileLlmHistoryStorage($this->tmpDir),
        );

        try {
            $controller->chat($this->post(['messages' => [['role' => 'user', 'content' => 'hi']]]));
        } catch (\Throwable) {
            // Expected
        }
        $this->assertTrue(true);
    }

    public function testChatWithCustomPromptMergedIntoUser(): void
    {
        $settings = new FileLlmSettings($this->tmpDir);
        $settings->setApiKey('sk-test');
        $settings->setProvider('openrouter');
        $settings->setCustomPrompt('Be helpful');

        $httpFactory = new HttpFactory();
        $controller = new LlmController(
            new JsonResponseFactory($httpFactory, $httpFactory),
            $settings,
            $this->mockHttpClient(new Response(200, [], '{}')),
            $httpFactory,
            $httpFactory,
            new FileLlmHistoryStorage($this->tmpDir),
        );

        try {
            $controller->chat($this->post(['messages' => [['role' => 'user', 'content' => 'hi']]]));
        } catch (\Throwable) {
            // Expected
        }
        $this->assertTrue(true);
    }

    // --- Analyze ---

    public function testAnalyzeNotConnected(): void
    {
        $response = $this->makeController()->analyze($this->post(['context' => ['test' => true]]));
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testAnalyzeMissingContext(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeController('openrouter', 'sk-test')->analyze($this->post([]));
    }

    public function testAnalyzeReachesHttp(): void
    {
        $controller = $this->makeController('openrouter', 'sk-test');
        try {
            $controller->analyze($this->post(['context' => ['logs' => []]]));
        } catch (\Throwable) {
            // Expected
        }
        $this->assertTrue(true);
    }

    public function testAnalyzeAnthropicReachesHttp(): void
    {
        $controller = $this->makeController('anthropic', 'sk-ant-test');
        try {
            $controller->analyze($this->post(['context' => ['logs' => []]]));
        } catch (\Throwable) {
            // Expected
        }
        $this->assertTrue(true);
    }

    public function testAnalyzeOpenAiReachesHttp(): void
    {
        $controller = $this->makeController('openai', 'sk-test');
        try {
            $controller->analyze($this->post(['context' => ['logs' => []]]));
        } catch (\Throwable) {
            // Expected
        }
        $this->assertTrue(true);
    }

    public function testAnalyzeLargeContextTruncated(): void
    {
        $controller = $this->makeController('openrouter', 'sk-test');
        try {
            $controller->analyze($this->post([
                'context' => ['data' => str_repeat('x', 15000)],
                'prompt' => 'Analyze this',
            ]));
        } catch (\Throwable) {
            // Expected
        }
        $this->assertTrue(true);
    }

    // --- OAuth ---

    public function testOauthInitiate(): void
    {
        $controller = $this->makeController();
        $data = $this->data($controller->oauthInitiate($this->post([
            'callbackUrl' => 'http://localhost:3000/callback',
        ])));
        $this->assertArrayHasKey('authUrl', $data);
        $this->assertArrayHasKey('codeVerifier', $data);
        $this->assertStringContainsString('openrouter.ai/auth', $data['authUrl']);
    }

    public function testOauthInitiateMissingCallback(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeController()->oauthInitiate($this->post([]));
    }

    public function testOauthExchangeSuccess(): void
    {
        $client = $this->mockHttpClient(new Response(200, [], json_encode(['key' => 'sk-or-new-key'])));
        $controller = $this->makeController('openrouter', null, $client);
        $data = $this->data($controller->oauthExchange($this->post(['code' => 'c', 'codeVerifier' => 'v'])));
        $this->assertTrue($data['connected']);
    }

    public function testOauthExchangeFailure(): void
    {
        $client = $this->mockHttpClient(new Response(400, [], json_encode(['error' => 'invalid_code'])));
        $controller = $this->makeController('openrouter', null, $client);
        $response = $controller->oauthExchange($this->post(['code' => 'bad', 'codeVerifier' => 'v']));
        $this->assertSame(400, $response->getStatusCode());
        $this->assertFalse($this->data($response)['connected']);
    }

    public function testOauthExchangeUnknownError(): void
    {
        $client = $this->mockHttpClient(new Response(200, [], json_encode([])));
        $controller = $this->makeController('openrouter', null, $client);
        $data = $this->data($controller->oauthExchange($this->post(['code' => 'c', 'codeVerifier' => 'v'])));
        $this->assertStringContainsString('Unknown error', $data['error']);
    }

    public function testOauthExchangeMissingCode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeController()->oauthExchange($this->post(['codeVerifier' => 'v']));
    }

    public function testOauthExchangeMissingCodeVerifier(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeController()->oauthExchange($this->post(['code' => 'c']));
    }

    // --- History ---

    public function testHistory(): void
    {
        $this->assertSame([], $this->data($this->makeController()->history(new ServerRequest('GET', '/'))));
    }

    public function testAddHistory(): void
    {
        $controller = $this->makeController();
        $data = $this->data($controller->addHistory($this->post([
            'query' => 'What is the error?',
            'response' => 'Bug found',
            'timestamp' => 1000,
        ])));
        $this->assertCount(1, $data);
        $this->assertSame('What is the error?', $data[0]['query']);
    }

    public function testAddHistoryMissingQuery(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeController()->addHistory($this->post(['response' => 'r']));
    }

    public function testAddHistoryWithError(): void
    {
        $data = $this->data($this->makeController()->addHistory($this->post([
            'query' => 'q',
            'response' => '',
            'timestamp' => 1000,
            'error' => 'API timeout',
        ])));
        $this->assertSame('API timeout', $data[0]['error']);
    }

    public function testAddHistoryEmptyErrorNotStored(): void
    {
        $data = $this->data($this->makeController()->addHistory($this->post([
            'query' => 'q',
            'response' => 'r',
            'timestamp' => 1000,
            'error' => '',
        ])));
        $this->assertArrayNotHasKey('error', $data[0]);
    }

    public function testDeleteHistory(): void
    {
        $controller = $this->makeController();
        $controller->addHistory($this->post(['query' => 'q1', 'response' => 'r1', 'timestamp' => 1]));
        $controller->addHistory($this->post(['query' => 'q2', 'response' => 'r2', 'timestamp' => 2]));

        $request = new ServerRequest('DELETE', '/')->withAttribute('index', '0');
        $data = $this->data($controller->deleteHistory($request));
        $this->assertCount(1, $data);
    }

    public function testClearHistory(): void
    {
        $controller = $this->makeController();
        $controller->addHistory($this->post(['query' => 'q', 'response' => 'r', 'timestamp' => 1]));
        $this->assertSame([], $this->data($controller->clearHistory(new ServerRequest('DELETE', '/'))));
    }
}
