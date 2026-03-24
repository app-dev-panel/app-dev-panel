<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Llm\Controller;

use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Api\Llm\LlmSettingsInterface;
use InvalidArgumentException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class LlmController
{
    private const string OPENROUTER_AUTH_URL = 'https://openrouter.ai/auth';
    private const string OPENROUTER_KEY_EXCHANGE_URL = 'https://openrouter.ai/api/v1/auth/keys';
    private const string OPENROUTER_MODELS_URL = 'https://openrouter.ai/api/v1/models';
    private const string OPENROUTER_COMPLETIONS_URL = 'https://openrouter.ai/api/v1/chat/completions';

    private const string ANTHROPIC_MESSAGES_URL = 'https://api.anthropic.com/v1/messages';
    private const string ANTHROPIC_MODELS_URL = 'https://api.anthropic.com/v1/models';
    private const string ANTHROPIC_API_VERSION = '2023-06-01';

    public function __construct(
        private readonly JsonResponseFactoryInterface $responseFactory,
        private readonly LlmSettingsInterface $settings,
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * GET /debug/api/llm/status — Connection status.
     */
    public function status(ServerRequestInterface $request): ResponseInterface
    {
        return $this->responseFactory->createJsonResponse($this->settings->toArray());
    }

    /**
     * POST /debug/api/llm/connect — Connect with direct API key (for Anthropic and other key-based providers).
     *
     * Body: { "provider": "anthropic", "apiKey": "sk-ant-..." }
     */
    public function connect(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array<string, mixed> $body */
        $body = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $provider = $body['provider'] ?? null;
        $apiKey = $body['apiKey'] ?? null;

        if (!is_string($provider) || $provider === '') {
            throw new InvalidArgumentException('Field "provider" is required and must be a non-empty string.');
        }
        if (!is_string($apiKey) || $apiKey === '') {
            throw new InvalidArgumentException('Field "apiKey" is required and must be a non-empty string.');
        }

        $this->settings->setProvider($provider);
        $this->settings->setApiKey($apiKey);

        return $this->responseFactory->createJsonResponse([
            'connected' => true,
            'provider' => $provider,
        ]);
    }

    /**
     * POST /debug/api/llm/oauth/initiate — Start OAuth PKCE flow (OpenRouter only).
     *
     * Body: { "callbackUrl": "..." }
     * Returns: { "authUrl": "...", "codeVerifier": "..." }
     */
    public function oauthInitiate(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array<string, mixed> $body */
        $body = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $callbackUrl = $body['callbackUrl'] ?? null;
        if (!is_string($callbackUrl) || $callbackUrl === '') {
            throw new InvalidArgumentException('Field "callbackUrl" is required and must be a non-empty string.');
        }

        $codeVerifier = $this->generateCodeVerifier();
        $codeChallenge = $this->generateCodeChallenge($codeVerifier);

        $authUrl =
            self::OPENROUTER_AUTH_URL
            . '?'
            . http_build_query([
                'callback_url' => $callbackUrl,
                'code_challenge' => $codeChallenge,
                'code_challenge_method' => 'S256',
            ]);

        return $this->responseFactory->createJsonResponse([
            'authUrl' => $authUrl,
            'codeVerifier' => $codeVerifier,
        ]);
    }

    /**
     * POST /debug/api/llm/oauth/exchange — Exchange authorization code for API key (OpenRouter only).
     *
     * Body: { "code": "...", "codeVerifier": "..." }
     */
    public function oauthExchange(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array<string, mixed> $body */
        $body = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $code = $body['code'] ?? null;
        $codeVerifier = $body['codeVerifier'] ?? null;

        if (!is_string($code) || $code === '') {
            throw new InvalidArgumentException('Field "code" is required.');
        }
        if (!is_string($codeVerifier) || $codeVerifier === '') {
            throw new InvalidArgumentException('Field "codeVerifier" is required.');
        }

        $exchangeBody = json_encode([
            'code' => $code,
            'code_verifier' => $codeVerifier,
        ], JSON_THROW_ON_ERROR);

        $exchangeRequest = $this->requestFactory
            ->createRequest('POST', self::OPENROUTER_KEY_EXCHANGE_URL)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streamFactory->createStream($exchangeBody));

        $exchangeResponse = $this->httpClient->sendRequest($exchangeRequest);
        $responseBody = $exchangeResponse->getBody()->getContents();

        /** @var array{key?: string, error?: string} $data */
        $data = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);

        if (!isset($data['key']) || !is_string($data['key'])) {
            $error = $data['error'] ?? 'Unknown error during key exchange.';

            return $this->responseFactory->createJsonResponse([
                'connected' => false,
                'error' => $error,
            ], 400);
        }

        $this->settings->setProvider('openrouter');
        $this->settings->setApiKey($data['key']);

        return $this->responseFactory->createJsonResponse([
            'connected' => true,
        ]);
    }

    /**
     * POST /debug/api/llm/disconnect — Remove stored API key.
     */
    public function disconnect(ServerRequestInterface $request): ResponseInterface
    {
        $this->settings->clear();

        return $this->responseFactory->createJsonResponse([
            'connected' => false,
        ]);
    }

    /**
     * POST /debug/api/llm/model — Set preferred model.
     *
     * Body: { "model": "anthropic/claude-sonnet-4" }
     */
    public function setModel(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array<string, mixed> $body */
        $body = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $model = $body['model'] ?? null;
        if (!is_string($model)) {
            throw new InvalidArgumentException('Field "model" is required and must be a string.');
        }

        $this->settings->setModel($model);

        return $this->responseFactory->createJsonResponse($this->settings->toArray());
    }

    /**
     * GET /debug/api/llm/models — List available models.
     */
    public function models(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->settings->isConnected()) {
            return $this->responseFactory->createJsonResponse([
                'error' => 'Not connected. Complete OAuth flow first.',
            ], 401);
        }

        $provider = $this->settings->getProvider();

        if ($provider === 'anthropic') {
            return $this->anthropicModels();
        }

        return $this->openRouterModels();
    }

    /**
     * POST /debug/api/llm/chat — Proxy chat completions.
     *
     * Body: { "messages": [...], "model"?: "...", "temperature"?: 0.7 }
     */
    public function chat(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->settings->isConnected()) {
            return $this->responseFactory->createJsonResponse([
                'error' => 'Not connected. Complete OAuth flow first.',
            ], 401);
        }

        /** @var array<string, mixed> $body */
        $body = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $messages = $body['messages'] ?? null;
        if (!is_array($messages) || $messages === []) {
            throw new InvalidArgumentException('Field "messages" is required and must be a non-empty array.');
        }

        $model = $body['model'] ?? $this->settings->getModel();
        $temperature = isset($body['temperature']) ? (float) $body['temperature'] : 0.7;

        $provider = $this->settings->getProvider();

        if ($provider === 'anthropic') {
            $model ??= 'claude-sonnet-4-20250514';

            return $this->anthropicChat($messages, $model, $temperature);
        }

        $model ??= 'anthropic/claude-sonnet-4';

        return $this->openRouterChat($messages, $model, $temperature);
    }

    /**
     * POST /debug/api/llm/analyze — Analyze debug entry data with LLM.
     *
     * Body: { "debugEntryId": "...", "context": {...}, "prompt"?: "..." }
     */
    public function analyze(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->settings->isConnected()) {
            return $this->responseFactory->createJsonResponse([
                'error' => 'Not connected. Complete OAuth flow first.',
            ], 401);
        }

        /** @var array<string, mixed> $body */
        $body = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $context = $body['context'] ?? null;
        if (!is_array($context)) {
            throw new InvalidArgumentException('Field "context" is required and must be an object.');
        }

        $userPrompt =
            $body['prompt']
            ?? 'Analyze this debug data and provide insights, potential issues, and suggestions for improvement.';

        $systemPrompt = <<<'PROMPT'
            You are an expert application debugger integrated into the Application Development Panel (ADP).
            You analyze debug data from PHP web applications and provide actionable insights.

            When analyzing debug data, focus on:
            1. Errors and exceptions — root cause analysis and fix suggestions
            2. Performance issues — slow queries, N+1 problems, excessive memory usage
            3. Security concerns — exposed sensitive data, insecure headers
            4. Best practices — PSR compliance, proper logging levels, caching opportunities

            Keep responses concise and actionable. Use markdown formatting.
            PROMPT;

        $contextJson = json_encode($context, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => "Here is the debug data:\n\n```json\n{$contextJson}\n```\n\n{$userPrompt}"],
        ];

        $provider = $this->settings->getProvider();

        if ($provider === 'anthropic') {
            $model = $this->settings->getModel() ?? 'claude-sonnet-4-20250514';
            $response = $this->anthropicChat($messages, $model, 0.3);
        } else {
            $model = $this->settings->getModel() ?? 'anthropic/claude-sonnet-4';
            $response = $this->openRouterChat($messages, $model, 0.3);
        }

        $responseBody = $response->getBody()->getContents();

        /** @var array<string, mixed> $data */
        $data = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);

        if (isset($data['error'])) {
            return $this->responseFactory->createJsonResponse([
                'error' => $data['error'],
            ], 502);
        }

        $content = $provider === 'anthropic'
            ? $data['content'][0]['text'] ?? ''
            : $data['choices'][0]['message']['content'] ?? '';

        return $this->responseFactory->createJsonResponse([
            'analysis' => $content,
            'model' => $model,
        ]);
    }

    private function openRouterModels(): ResponseInterface
    {
        $modelsRequest = $this->requestFactory->createRequest('GET', self::OPENROUTER_MODELS_URL)->withHeader(
            'Authorization',
            'Bearer ' . $this->settings->getApiKey(),
        );

        $modelsResponse = $this->httpClient->sendRequest($modelsRequest);
        $responseBody = $modelsResponse->getBody()->getContents();

        /** @var array{data?: list<array<string, mixed>>} $data */
        $data = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);

        $models = [];
        foreach ($data['data'] ?? [] as $model) {
            $models[] = [
                'id' => $model['id'] ?? '',
                'name' => $model['name'] ?? '',
                'context_length' => $model['context_length'] ?? 0,
                'pricing' => $model['pricing'] ?? [],
            ];
        }

        return $this->responseFactory->createJsonResponse(['models' => $models]);
    }

    private function anthropicModels(): ResponseInterface
    {
        $modelsRequest = $this->requestFactory
            ->createRequest('GET', self::ANTHROPIC_MODELS_URL)
            ->withHeader('x-api-key', (string) $this->settings->getApiKey())
            ->withHeader('anthropic-version', self::ANTHROPIC_API_VERSION);

        $modelsResponse = $this->httpClient->sendRequest($modelsRequest);
        $responseBody = $modelsResponse->getBody()->getContents();

        /** @var array{data?: list<array<string, mixed>>} $data */
        $data = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);

        $models = [];
        foreach ($data['data'] ?? [] as $model) {
            $models[] = [
                'id' => $model['id'] ?? '',
                'name' => $model['display_name'] ?? $model['id'] ?? '',
                'context_length' => $model['context_window'] ?? 0,
                'pricing' => [],
            ];
        }

        return $this->responseFactory->createJsonResponse(['models' => $models]);
    }

    private function openRouterChat(array $messages, string $model, float $temperature): ResponseInterface
    {
        $chatBody = json_encode([
            'model' => $model,
            'messages' => $messages,
            'temperature' => $temperature,
        ], JSON_THROW_ON_ERROR);

        $chatRequest = $this->requestFactory
            ->createRequest('POST', self::OPENROUTER_COMPLETIONS_URL)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Authorization', 'Bearer ' . $this->settings->getApiKey())
            ->withHeader('HTTP-Referer', 'https://app-dev-panel.dev')
            ->withHeader('X-Title', 'Application Development Panel')
            ->withBody($this->streamFactory->createStream($chatBody));

        $chatResponse = $this->httpClient->sendRequest($chatRequest);
        $responseBody = $chatResponse->getBody()->getContents();

        /** @var array<string, mixed> $data */
        $data = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);

        if (isset($data['error'])) {
            $errorMessage = is_array($data['error'])
                ? $data['error']['message'] ?? 'Unknown OpenRouter API error.'
                : (string) $data['error'];

            return $this->responseFactory->createJsonResponse([
                'error' => $errorMessage,
            ], 502);
        }

        return $this->responseFactory->createJsonResponse($data);
    }

    /**
     * @param list<array{role: string, content: string}> $messages
     */
    private function anthropicChat(array $messages, string $model, float $temperature): ResponseInterface
    {
        $systemPrompt = null;
        $chatMessages = [];
        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                $systemPrompt = $message['content'];
            } else {
                $chatMessages[] = $message;
            }
        }

        $payload = [
            'model' => $model,
            'max_tokens' => 4096,
            'messages' => $chatMessages,
            'temperature' => $temperature,
        ];
        if ($systemPrompt !== null) {
            $payload['system'] = $systemPrompt;
        }

        $chatBody = json_encode($payload, JSON_THROW_ON_ERROR);

        $chatRequest = $this->requestFactory
            ->createRequest('POST', self::ANTHROPIC_MESSAGES_URL)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('x-api-key', (string) $this->settings->getApiKey())
            ->withHeader('anthropic-version', self::ANTHROPIC_API_VERSION)
            ->withBody($this->streamFactory->createStream($chatBody));

        $chatResponse = $this->httpClient->sendRequest($chatRequest);
        $responseBody = $chatResponse->getBody()->getContents();

        /** @var array<string, mixed> $anthropicData */
        $anthropicData = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);

        if (isset($anthropicData['error'])) {
            $errorMessage = is_array($anthropicData['error'])
                ? $anthropicData['error']['message'] ?? 'Unknown Anthropic API error.'
                : (string) $anthropicData['error'];

            return $this->responseFactory->createJsonResponse([
                'error' => $errorMessage,
            ], 502);
        }

        // Normalize Anthropic response to OpenAI-compatible format
        $content = $anthropicData['content'][0]['text'] ?? '';

        return $this->responseFactory->createJsonResponse([
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => $content,
                    ],
                ],
            ],
            'model' => $anthropicData['model'] ?? $model,
            'usage' => $anthropicData['usage'] ?? [],
        ]);
    }

    private function generateCodeVerifier(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    private function generateCodeChallenge(string $codeVerifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
    }
}
