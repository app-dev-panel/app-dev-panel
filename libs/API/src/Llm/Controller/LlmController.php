<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Llm\Controller;

use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Api\Llm\Acp\AcpCommandVerifierInterface;
use AppDevPanel\Api\Llm\Acp\AcpDaemonManagerInterface;
use AppDevPanel\Api\Llm\LlmHistoryStorageInterface;
use AppDevPanel\Api\Llm\LlmProviderService;
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

    public function __construct(
        private readonly JsonResponseFactoryInterface $responseFactory,
        private readonly LlmSettingsInterface $settings,
        private readonly LlmProviderService $providerService,
        private readonly LlmHistoryStorageInterface $historyStorage,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly ClientInterface $httpClient,
        private readonly ?AcpCommandVerifierInterface $commandVerifier = null,
        private readonly ?AcpDaemonManagerInterface $acpDaemonManager = null,
    ) {}

    /**
     * GET /debug/api/llm/status — Connection status.
     */
    public function status(ServerRequestInterface $request): ResponseInterface
    {
        return $this->responseFactory->createJsonResponse($this->settings->toArray());
    }

    /**
     * POST /debug/api/llm/connect — Connect with direct API key or ACP agent.
     */
    public function connect(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array<string, mixed> $body */
        $body = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $provider = $body['provider'] ?? null;

        if (!is_string($provider) || $provider === '') {
            throw new InvalidArgumentException('Field "provider" is required and must be a non-empty string.');
        }

        if ($provider === 'acp') {
            return $this->connectAcp($body);
        }

        $apiKey = $body['apiKey'] ?? null;
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
     * Connect to an ACP agent (Claude Code, Gemini CLI, etc.).
     *
     * Verifies the agent command is available on the system.
     */
    private function connectAcp(array $body): ResponseInterface
    {
        $command =
            isset($body['acpCommand']) && is_string($body['acpCommand']) && $body['acpCommand'] !== ''
                ? $body['acpCommand']
                : 'npx';

        // Default args: use the official ACP adapter for Claude Code when using default command.
        $defaultArgs =
            $command === 'npx' && (!isset($body['acpArgs']) || $body['acpArgs'] === [])
                ? ['@agentclientprotocol/claude-agent-acp']
                : [];
        $acpArgs = isset($body['acpArgs']) && is_array($body['acpArgs']) ? $body['acpArgs'] : $defaultArgs;
        $acpEnv = isset($body['acpEnv']) && is_array($body['acpEnv']) ? $body['acpEnv'] : [];

        if ($this->commandVerifier !== null && !$this->commandVerifier->isAvailable($command)) {
            return $this->responseFactory->createJsonResponse([
                'connected' => false,
                'error' => sprintf('ACP agent command "%s" not found on system PATH.', $command),
            ], 400);
        }

        $this->settings->setProvider('acp');
        $this->settings->setAcpCommand($command);
        $this->settings->setAcpArgs($acpArgs);
        $this->settings->setAcpEnv($acpEnv);

        // Start persistent ACP daemon
        if ($this->acpDaemonManager !== null) {
            try {
                $this->acpDaemonManager->start($command, $acpArgs, $acpEnv);
            } catch (\RuntimeException $e) {
                $this->settings->clear();

                return $this->responseFactory->createJsonResponse([
                    'connected' => false,
                    'error' => 'Failed to start ACP daemon: ' . $e->getMessage(),
                ], 500);
            }
        }

        return $this->responseFactory->createJsonResponse([
            'connected' => true,
            'provider' => 'acp',
            'acpCommand' => $command,
        ]);
    }

    /**
     * POST /debug/api/llm/oauth/initiate — Start OAuth PKCE flow (OpenRouter only).
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
            'code_challenge_method' => 'S256',
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
        // Stop ACP daemon if running
        if ($this->acpDaemonManager !== null) {
            $this->acpDaemonManager->stop();
        }

        $this->settings->clear();

        return $this->responseFactory->createJsonResponse([
            'connected' => false,
        ]);
    }

    /**
     * POST /debug/api/llm/model — Set preferred model.
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
     * POST /debug/api/llm/timeout — Set request timeout.
     */
    public function setTimeout(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array<string, mixed> $body */
        $body = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $timeout = $body['timeout'] ?? null;
        if (!is_int($timeout)) {
            throw new InvalidArgumentException('Field "timeout" is required and must be an integer.');
        }

        $this->settings->setTimeout($timeout);

        return $this->responseFactory->createJsonResponse($this->settings->toArray());
    }

    /**
     * POST /debug/api/llm/custom-prompt — Set custom prompt.
     */
    public function setCustomPrompt(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array<string, mixed> $body */
        $body = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $prompt = $body['customPrompt'] ?? null;
        if (!is_string($prompt)) {
            throw new InvalidArgumentException('Field "customPrompt" is required and must be a string.');
        }

        $this->settings->setCustomPrompt($prompt);

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

        $models = $this->providerService->listModels($this->settings->getProvider());

        return $this->responseFactory->createJsonResponse(['models' => $models]);
    }

    /**
     * POST /debug/api/llm/chat — Proxy chat completions.
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

        $provider = $this->settings->getProvider();
        $model = $body['model'] ?? $this->settings->getModel() ?? $this->providerService->getDefaultModel($provider);
        $temperature = isset($body['temperature']) ? (float) $body['temperature'] : 0.7;

        $messages = $this->prependCustomPrompt($messages, $provider);

        $data = $this->providerService->sendChat($provider, $messages, $model, $temperature);

        if (isset($data['error'])) {
            return $this->responseFactory->createJsonResponse(['error' => $data['error']], 502);
        }

        return $this->responseFactory->createJsonResponse($data);
    }

    /**
     * POST /debug/api/llm/analyze — Analyze debug entry data with LLM.
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

        $instructions = <<<'PROMPT'
            You are an expert application debugger integrated into the Application Development Panel (ADP).
            You analyze debug data from PHP web applications and provide actionable insights.

            When analyzing debug data, focus on:
            1. Errors and exceptions — root cause analysis and fix suggestions
            2. Performance issues — slow queries, N+1 problems, excessive memory usage
            3. Security concerns — exposed sensitive data, insecure headers
            4. Best practices — PSR compliance, proper logging levels, caching opportunities

            Keep responses concise and actionable. Use markdown formatting.
            PROMPT;

        $customPrompt = $this->settings->getCustomPrompt();
        if ($customPrompt !== '') {
            $instructions .= "\n\nAdditional user instructions:\n" . $customPrompt;
        }

        $contextJson = json_encode($context, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Truncate context to avoid exceeding model context windows.
        // JSON structure is ASCII; values may contain UTF-8 but truncation is for size limits, not display.
        $maxContextLength = 12000;
        if (strlen($contextJson) > $maxContextLength) {
            $contextJson = substr($contextJson, 0, $maxContextLength) . "\n... [truncated]";
        }

        $messages = [
            [
                'role' => 'user',
                'content' => "{$instructions}\n\nHere is the debug data:\n\n```json\n{$contextJson}\n```\n\n{$userPrompt}",
            ],
        ];

        $provider = $this->settings->getProvider();
        $model = $this->settings->getModel() ?? $this->providerService->getDefaultModel($provider);

        $data = $this->providerService->sendChat($provider, $messages, $model, 0.3);

        if (isset($data['error'])) {
            return $this->responseFactory->createJsonResponse([
                'error' => $data['error'],
            ], 502);
        }

        $content = $data['choices'][0]['message']['content'] ?? '';

        return $this->responseFactory->createJsonResponse([
            'analysis' => $content,
            'model' => $model,
        ]);
    }

    /**
     * GET /debug/api/llm/history — Get chat history.
     */
    public function history(ServerRequestInterface $request): ResponseInterface
    {
        return $this->responseFactory->createJsonResponse($this->historyStorage->getAll());
    }

    /**
     * POST /debug/api/llm/history — Add a history entry.
     */
    public function addHistory(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array{query?: string, response?: string, timestamp?: int, error?: string} $body */
        $body = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $query = $body['query'] ?? '';
        $response = $body['response'] ?? '';
        $timestamp = $body['timestamp'] ?? time();

        if ($query === '') {
            throw new InvalidArgumentException('Field "query" is required.');
        }

        $entry = [
            'query' => $query,
            'response' => $response,
            'timestamp' => $timestamp,
        ];
        if (isset($body['error']) && $body['error'] !== '') {
            $entry['error'] = $body['error'];
        }

        $this->historyStorage->add($entry);

        return $this->responseFactory->createJsonResponse($this->historyStorage->getAll());
    }

    /**
     * DELETE /debug/api/llm/history/{index} — Delete a single history entry.
     */
    public function deleteHistory(ServerRequestInterface $request): ResponseInterface
    {
        $index = (int) $request->getAttribute('index');
        $this->historyStorage->delete($index);

        return $this->responseFactory->createJsonResponse($this->historyStorage->getAll());
    }

    /**
     * DELETE /debug/api/llm/history — Clear all history.
     */
    public function clearHistory(ServerRequestInterface $request): ResponseInterface
    {
        $this->historyStorage->clear();

        return $this->responseFactory->createJsonResponse([]);
    }

    /**
     * Prepend custom prompt to messages based on provider capabilities.
     */
    private function prependCustomPrompt(array $messages, string $provider): array
    {
        $customPrompt = $this->settings->getCustomPrompt();
        if ($customPrompt === '') {
            return $messages;
        }

        if ($provider === 'anthropic' || $provider === 'openai') {
            array_unshift($messages, ['role' => 'system', 'content' => $customPrompt]);
        } else {
            // Merge into first user message for maximum model compatibility.
            for ($i = 0, $len = count($messages); $i < $len; $i++) {
                if ($messages[$i]['role'] === 'user') {
                    $messages[$i]['content'] = "[Instructions: {$customPrompt}]\n\n" . $messages[$i]['content'];
                    break;
                }
            }
        }

        return $messages;
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
