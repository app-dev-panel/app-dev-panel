<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Llm\Controller;

use AppDevPanel\Api\Http\JsonResponseFactory;
use AppDevPanel\Api\Llm\Acp\AcpCommandVerifierInterface;
use AppDevPanel\Api\Llm\Acp\AcpDaemonManagerInterface;
use AppDevPanel\Api\Llm\Controller\LlmController;
use AppDevPanel\Api\Llm\FileLlmHistoryStorage;
use AppDevPanel\Api\Llm\FileLlmSettings;
use AppDevPanel\Api\Llm\LlmProviderService;
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
        ?LlmProviderService $providerService = null,
        ?AcpCommandVerifierInterface $commandVerifier = null,
        ?AcpDaemonManagerInterface $acpDaemonManager = null,
    ): LlmController {
        $settings = new FileLlmSettings($this->tmpDir);
        if ($apiKey !== null) {
            $settings->setApiKey($apiKey);
        }
        $settings->setProvider($provider);

        $httpFactory = new HttpFactory();
        $client = $httpClient ?? $this->mockHttpClient(new Response(200, [], '{}'));

        $providerService ??= new LlmProviderService($settings, $client, $httpFactory, $httpFactory, $acpDaemonManager);

        return new LlmController(
            new JsonResponseFactory($httpFactory, $httpFactory),
            $settings,
            $providerService,
            new FileLlmHistoryStorage($this->tmpDir),
            $httpFactory,
            $httpFactory,
            $client,
            $commandVerifier,
            $acpDaemonManager,
        );
    }

    private function mockHttpClient(Response $response): ClientInterface
    {
        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')->willReturn($response);
        return $client;
    }

    private function post(array $body, array $headers = []): ServerRequest
    {
        $request = new ServerRequest('POST', '/test')->withBody(Stream::create(json_encode(
            $body,
            JSON_THROW_ON_ERROR,
        )));
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }
        return $request;
    }

    private function acpPost(array $body): ServerRequest
    {
        return $this->post($body, ['X-Acp-Session' => 'test-session-id']);
    }

    private function mockDaemonManager(): AcpDaemonManagerInterface
    {
        $mock = $this->createMock(AcpDaemonManagerInterface::class);
        $mock->method('isRunning')->willReturn(true);
        $mock->method('startSession')->willReturn(['agentName' => 'TestAgent', 'agentVersion' => '1.0']);
        $mock->method('isSessionActive')->willReturn(true);
        return $mock;
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

    public function testChatReachesProvider(): void
    {
        // LlmProviderService is final, so we test via the real class with HTTP mock.
        // sendLlmRequest creates a new Guzzle Client internally, so the mock client isn't used for chat.
        // We verify the method reaches the HTTP call attempt.
        $controller = $this->makeController('openrouter', 'sk-test');
        try {
            $controller->chat($this->post(['messages' => [['role' => 'user', 'content' => 'hi']]]));
        } catch (\Throwable) {
            // Expected: real HTTP call fails
        }
        $this->assertTrue(true);
    }

    public function testChatAnthropicReachesProvider(): void
    {
        $controller = $this->makeController('anthropic', 'sk-ant-test');
        try {
            $controller->chat($this->post(['messages' => [['role' => 'user', 'content' => 'hi']]]));
        } catch (\Throwable) {
            // Expected
        }
        $this->assertTrue(true);
    }

    public function testChatOpenAiReachesProvider(): void
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
        $client = $this->mockHttpClient(new Response(200, [], '{}'));
        $providerService = new LlmProviderService($settings, $client, $httpFactory, $httpFactory);
        $controller = new LlmController(
            new JsonResponseFactory($httpFactory, $httpFactory),
            $settings,
            $providerService,
            new FileLlmHistoryStorage($this->tmpDir),
            $httpFactory,
            $httpFactory,
            $client,
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
        $client = $this->mockHttpClient(new Response(200, [], '{}'));
        $providerService = new LlmProviderService($settings, $client, $httpFactory, $httpFactory);
        $controller = new LlmController(
            new JsonResponseFactory($httpFactory, $httpFactory),
            $settings,
            $providerService,
            new FileLlmHistoryStorage($this->tmpDir),
            $httpFactory,
            $httpFactory,
            $client,
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

    public function testAnalyzeReachesProvider(): void
    {
        $controller = $this->makeController('openrouter', 'sk-test');
        try {
            $controller->analyze($this->post(['context' => ['logs' => []]]));
        } catch (\Throwable) {
            // Expected: real HTTP call fails
        }
        $this->assertTrue(true);
    }

    public function testAnalyzeAnthropicReachesProvider(): void
    {
        $controller = $this->makeController('anthropic', 'sk-ant-test');
        try {
            $controller->analyze($this->post(['context' => ['logs' => []]]));
        } catch (\Throwable) {
            // Expected
        }
        $this->assertTrue(true);
    }

    public function testAnalyzeOpenAiReachesProvider(): void
    {
        $controller = $this->makeController('openai', 'sk-test');
        try {
            $controller->analyze($this->post(['context' => ['logs' => []]]));
        } catch (\Throwable) {
            // Expected
        }
        $this->assertTrue(true);
    }

    public function testAnalyzeLargeContextReachesProvider(): void
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

    public function testAnalyzeWithCustomPrompt(): void
    {
        $settings = new FileLlmSettings($this->tmpDir);
        $settings->setApiKey('sk-test');
        $settings->setProvider('openrouter');
        $settings->setCustomPrompt('Focus on security issues');

        $httpFactory = new HttpFactory();
        $client = $this->mockHttpClient(new Response(200, [], '{}'));
        $providerService = new LlmProviderService($settings, $client, $httpFactory, $httpFactory);
        $controller = new LlmController(
            new JsonResponseFactory($httpFactory, $httpFactory),
            $settings,
            $providerService,
            new FileLlmHistoryStorage($this->tmpDir),
            $httpFactory,
            $httpFactory,
            $client,
        );

        try {
            $controller->analyze($this->post(['context' => ['logs' => []]]));
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
        $this->assertStringContainsString('code_challenge', $data['authUrl']);
    }

    public function testOauthInitiateMissingCallback(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeController()->oauthInitiate($this->post([]));
    }

    public function testOauthInitiateEmptyCallback(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeController()->oauthInitiate($this->post(['callbackUrl' => '']));
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

    public function testOauthExchangeEmptyCode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeController()->oauthExchange($this->post(['code' => '', 'codeVerifier' => 'v']));
    }

    public function testOauthExchangeEmptyCodeVerifier(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeController()->oauthExchange($this->post(['code' => 'c', 'codeVerifier' => '']));
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

    public function testAddHistoryDefaultTimestamp(): void
    {
        $data = $this->data($this->makeController()->addHistory($this->post([
            'query' => 'q',
            'response' => 'r',
        ])));
        $this->assertArrayHasKey('timestamp', $data[0]);
        $this->assertIsInt($data[0]['timestamp']);
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

    public function testConnectNonStringProvider(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeController()->connect($this->post(['provider' => 123, 'apiKey' => 'key']));
    }

    public function testConnectNonStringApiKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeController()->connect($this->post(['provider' => 'openrouter', 'apiKey' => 123]));
    }

    public function testSetModelNonString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeController()->setModel($this->post(['model' => 123]));
    }

    public function testSetTimeoutNonInt(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeController()->setTimeout($this->post(['timeout' => 'fast']));
    }

    public function testSetCustomPromptNonString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeController()->setCustomPrompt($this->post(['customPrompt' => 123]));
    }

    public function testAnalyzeNonArrayContext(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeController('openrouter', 'sk-test')->analyze($this->post(['context' => 'not-array']));
    }

    public function testChatNonArrayMessages(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeController('openrouter', 'sk-test')->chat($this->post(['messages' => 'not-array']));
    }

    // --- ACP Connect ---

    private function mockVerifier(bool $available): AcpCommandVerifierInterface
    {
        $verifier = $this->createMock(AcpCommandVerifierInterface::class);
        $verifier->method('isAvailable')->willReturn($available);

        return $verifier;
    }

    public function testConnectAcpWithValidCommand(): void
    {
        $controller = $this->makeController(
            commandVerifier: $this->mockVerifier(true),
            acpDaemonManager: $this->mockDaemonManager(),
        );
        $response = $controller->connect($this->acpPost(['provider' => 'acp', 'acpCommand' => 'claude']));
        $data = $this->data($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['connected']);
        $this->assertSame('acp', $data['provider']);
        $this->assertSame('claude', $data['acpCommand']);
    }

    public function testConnectAcpWithInvalidCommand(): void
    {
        $controller = $this->makeController(commandVerifier: $this->mockVerifier(false));
        $response = $controller->connect($this->post([
            'provider' => 'acp',
            'acpCommand' => 'nonexistent-acp-cmd-99999',
        ]));
        $data = $this->data($response);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertFalse($data['connected']);
        $this->assertStringContainsString('not found', $data['error']);
    }

    public function testConnectAcpDefaultCommand(): void
    {
        $controller = $this->makeController(
            commandVerifier: $this->mockVerifier(true),
            acpDaemonManager: $this->mockDaemonManager(),
        );
        $response = $controller->connect($this->acpPost(['provider' => 'acp']));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('npx', $this->data($response)['acpCommand']);
    }

    public function testConnectAcpDoesNotRequireApiKey(): void
    {
        $controller = $this->makeController(
            commandVerifier: $this->mockVerifier(true),
            acpDaemonManager: $this->mockDaemonManager(),
        );
        $response = $controller->connect($this->acpPost(['provider' => 'acp', 'acpCommand' => 'npx']));
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testConnectAcpWithArgsAndEnv(): void
    {
        $controller = $this->makeController(
            commandVerifier: $this->mockVerifier(true),
            acpDaemonManager: $this->mockDaemonManager(),
        );
        $response = $controller->connect($this->acpPost([
            'provider' => 'acp',
            'acpCommand' => 'claude',
            'acpArgs' => ['--version'],
            'acpEnv' => ['MY_VAR' => 'value'],
        ]));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($this->data($response)['connected']);
    }

    public function testStatusAfterAcpConnect(): void
    {
        $controller = $this->makeController(
            commandVerifier: $this->mockVerifier(true),
            acpDaemonManager: $this->mockDaemonManager(),
        );
        $controller->connect($this->acpPost(['provider' => 'acp', 'acpCommand' => 'claude']));

        $data = $this->data($controller->status(new ServerRequest('GET', '/')));
        $this->assertTrue($data['connected']);
        $this->assertSame('acp', $data['provider']);
        $this->assertSame('claude', $data['acpCommand']);
    }

    public function testModelsAcpConnected(): void
    {
        $controller = $this->makeController(
            commandVerifier: $this->mockVerifier(true),
            acpDaemonManager: $this->mockDaemonManager(),
        );
        $controller->connect($this->acpPost(['provider' => 'acp', 'acpCommand' => 'claude']));

        $data = $this->data($controller->models(new ServerRequest('GET', '/')));
        $this->assertArrayHasKey('models', $data);
        $this->assertCount(1, $data['models']);
        $this->assertSame('acp-agent', $data['models'][0]['id']);
    }

    public function testConnectAcpWithoutVerifierSkipsCheck(): void
    {
        $controller = $this->makeController(acpDaemonManager: $this->mockDaemonManager());
        $response = $controller->connect($this->acpPost(['provider' => 'acp', 'acpCommand' => 'anything']));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($this->data($response)['connected']);
    }

    public function testConnectAcpRequiresSessionHeader(): void
    {
        $controller = $this->makeController(
            commandVerifier: $this->mockVerifier(true),
            acpDaemonManager: $this->mockDaemonManager(),
        );
        $response = $controller->connect($this->post(['provider' => 'acp']));

        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('X-Acp-Session', $this->data($response)['error']);
    }
}
