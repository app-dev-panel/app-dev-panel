<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Llm;

use AppDevPanel\Api\Llm\Acp\AcpDaemonManagerInterface;
use AppDevPanel\Api\Llm\Acp\AcpResponse;
use GuzzleHttp\Client;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Handles communication with LLM providers (OpenRouter, Anthropic, OpenAI, ACP).
 *
 * Encapsulates provider-specific API differences: authentication, request format,
 * response normalization, and model listing.
 */
final class LlmProviderService
{
    private const string OPENROUTER_MODELS_URL = 'https://openrouter.ai/api/v1/models';
    private const string OPENROUTER_COMPLETIONS_URL = 'https://openrouter.ai/api/v1/chat/completions';

    private const string ANTHROPIC_MESSAGES_URL = 'https://api.anthropic.com/v1/messages';
    private const string ANTHROPIC_MODELS_URL = 'https://api.anthropic.com/v1/models';
    private const string ANTHROPIC_API_VERSION = '2023-06-01';

    private const string OPENAI_COMPLETIONS_URL = 'https://api.openai.com/v1/chat/completions';
    private const string OPENAI_MODELS_URL = 'https://api.openai.com/v1/models';

    public function __construct(
        private readonly LlmSettingsInterface $settings,
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly ?AcpDaemonManagerInterface $acpDaemonManager = null,
    ) {}

    /**
     * Send chat messages to the configured provider and return parsed response data.
     *
     * All providers return normalized OpenAI-compatible format:
     * { "choices": [{ "message": { "role": "assistant", "content": "..." } }], "model": "...", "usage": {...} }
     *
     * On error: { "error": "..." }
     *
     * @param list<array{role: string, content: string}> $messages
     * @return array<string, mixed>
     */
    public function sendChat(string $provider, array $messages, string $model, float $temperature): array
    {
        return match ($provider) {
            'anthropic' => $this->sendAnthropicChat($messages, $model, $temperature),
            'openai' => $this->sendOpenAiChat($messages, $model, $temperature),
            'acp' => $this->sendAcpChat($messages),
            default => $this->sendOpenRouterChat($messages, $model, $temperature),
        };
    }

    /**
     * List available models for the given provider.
     *
     * @return list<array{id: string, name: string, context_length: int, pricing: array}>
     */
    public function listModels(string $provider): array
    {
        return match ($provider) {
            'anthropic' => $this->fetchAnthropicModels(),
            'openai' => $this->fetchOpenAiModels(),
            'acp' => $this->fetchAcpModels(),
            default => $this->fetchOpenRouterModels(),
        };
    }

    /**
     * Resolve a default model for the given provider when none is configured.
     */
    public function getDefaultModel(string $provider): string
    {
        return match ($provider) {
            'anthropic' => 'claude-sonnet-4-20250514',
            'openai' => 'gpt-4o',
            'acp' => 'acp-agent',
            default => 'anthropic/claude-sonnet-4',
        };
    }

    /**
     * @return list<array{id: string, name: string, context_length: int, pricing: array}>
     */
    private function fetchOpenRouterModels(): array
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

        return $models;
    }

    /**
     * @return list<array{id: string, name: string, context_length: int, pricing: array}>
     */
    private function fetchAnthropicModels(): array
    {
        $modelsRequest = $this->requestFactory->createRequest('GET', self::ANTHROPIC_MODELS_URL)->withHeader(
            'anthropic-version',
            self::ANTHROPIC_API_VERSION,
        );

        $modelsRequest = $this->applyAnthropicAuth($modelsRequest);

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

        return $models;
    }

    /**
     * @return list<array{id: string, name: string, context_length: int, pricing: array}>
     */
    private function fetchOpenAiModels(): array
    {
        $modelsRequest = $this->requestFactory->createRequest('GET', self::OPENAI_MODELS_URL)->withHeader(
            'Authorization',
            'Bearer ' . $this->settings->getApiKey(),
        );

        $modelsResponse = $this->httpClient->sendRequest($modelsRequest);
        $responseBody = $modelsResponse->getBody()->getContents();

        /** @var array{data?: list<array<string, mixed>>} $data */
        $data = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);

        $models = [];
        foreach ($data['data'] ?? [] as $model) {
            $id = $model['id'] ?? '';
            // Only include chat-capable models
            if (!str_starts_with($id, 'gpt-') && !str_starts_with($id, 'o') && !str_starts_with($id, 'chatgpt-')) {
                continue;
            }
            $models[] = [
                'id' => $id,
                'name' => $id,
                'context_length' => 0,
                'pricing' => [],
            ];
        }

        usort($models, static fn(array $a, array $b): int => strcmp((string) $a['id'], (string) $b['id']));

        return $models;
    }

    /**
     * @param list<array{role: string, content: string}> $messages
     * @return array<string, mixed>
     */
    private function sendOpenRouterChat(array $messages, string $model, float $temperature): array
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

        $chatResponse = $this->sendLlmRequest($chatRequest);
        $responseBody = $chatResponse->getBody()->getContents();

        /** @var array<string, mixed> $data */
        $data = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);

        if (isset($data['error'])) {
            $errorMessage = is_array($data['error'])
                ? $data['error']['message'] ?? 'Unknown OpenRouter API error.'
                : (string) $data['error'];

            // Append provider-specific details when available (e.g. rate limits, context overflow).
            if (is_array($data['error'])) {
                $code = $data['error']['code'] ?? null;
                $metadata = $data['error']['metadata'] ?? null;
                $details = [];
                if ($code !== null) {
                    $details[] = "code: {$code}";
                }
                if (is_array($metadata) && isset($metadata['raw'])) {
                    $details[] = (string) $metadata['raw'];
                }
                if ($details !== []) {
                    $errorMessage .= ' (' . implode('; ', $details) . ')';
                }
            }

            return ['error' => $errorMessage];
        }

        return $data;
    }

    /**
     * @param list<array{role: string, content: string}> $messages
     * @return array<string, mixed>
     */
    private function sendAnthropicChat(array $messages, string $model, float $temperature): array
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
            ->withHeader('anthropic-version', self::ANTHROPIC_API_VERSION)
            ->withBody($this->streamFactory->createStream($chatBody));

        $chatRequest = $this->applyAnthropicAuth($chatRequest);

        $chatResponse = $this->sendLlmRequest($chatRequest);
        $responseBody = $chatResponse->getBody()->getContents();

        /** @var array<string, mixed> $anthropicData */
        $anthropicData = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);

        if (isset($anthropicData['error'])) {
            $errorMessage = is_array($anthropicData['error'])
                ? $anthropicData['error']['message'] ?? 'Unknown Anthropic API error.'
                : (string) $anthropicData['error'];

            return ['error' => $errorMessage];
        }

        // Normalize Anthropic response to OpenAI-compatible format
        $content = $anthropicData['content'][0]['text'] ?? '';

        return [
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
        ];
    }

    /**
     * @param list<array{role: string, content: string}> $messages
     * @return array<string, mixed>
     */
    private function sendOpenAiChat(array $messages, string $model, float $temperature): array
    {
        $chatBody = json_encode([
            'model' => $model,
            'messages' => $messages,
            'temperature' => $temperature,
        ], JSON_THROW_ON_ERROR);

        $chatRequest = $this->requestFactory
            ->createRequest('POST', self::OPENAI_COMPLETIONS_URL)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Authorization', 'Bearer ' . $this->settings->getApiKey())
            ->withBody($this->streamFactory->createStream($chatBody));

        $chatResponse = $this->sendLlmRequest($chatRequest);
        $responseBody = $chatResponse->getBody()->getContents();

        /** @var array<string, mixed> $data */
        $data = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);

        if (isset($data['error'])) {
            $errorMessage = is_array($data['error'])
                ? $data['error']['message'] ?? 'Unknown OpenAI API error.'
                : (string) $data['error'];

            return ['error' => $errorMessage];
        }

        return $data;
    }

    /**
     * Apply the correct authentication header based on the API key type.
     *
     * OAuth tokens (sk-ant-oat*) from Claude subscriptions use Authorization: Bearer.
     * Standard API keys use x-api-key header.
     */
    private function applyAnthropicAuth(RequestInterface $request): RequestInterface
    {
        $apiKey = (string) $this->settings->getApiKey();

        if (str_starts_with($apiKey, 'sk-ant-oat')) {
            return $request->withHeader('Authorization', 'Bearer ' . $apiKey);
        }

        return $request->withHeader('x-api-key', $apiKey);
    }

    /**
     * Send chat via ACP daemon (persistent agent process).
     *
     * Connects to the running ACP daemon via Unix socket,
     * sends the prompt, and returns the collected response.
     *
     * @param list<array{role: string, content: string}> $messages
     * @return array<string, mixed>
     */
    private function sendAcpChat(array $messages): array
    {
        if ($this->acpDaemonManager === null) {
            return ['error' => 'ACP provider is not configured (missing AcpDaemonManager).'];
        }

        if (!$this->acpDaemonManager->isRunning()) {
            return ['error' => 'ACP daemon is not running. Please reconnect the ACP provider.'];
        }

        $timeout = (float) $this->settings->getTimeout();
        $customPrompt = $this->settings->getCustomPrompt();

        try {
            $data = $this->acpDaemonManager->sendPrompt($messages, $customPrompt, $timeout);
        } catch (\RuntimeException $e) {
            return ['error' => 'ACP daemon error: ' . $e->getMessage()];
        }

        if (isset($data['error'])) {
            return ['error' => $data['error']];
        }

        $response = new AcpResponse(
            text: $data['text'] ?? '',
            stopReason: $data['stopReason'] ?? 'end_turn',
            agentName: $data['agentName'] ?? '',
            agentVersion: $data['agentVersion'] ?? '',
            toolCalls: $data['toolCalls'] ?? [],
        );

        return $response->toOpenAiFormat();
    }

    /**
     * ACP agents manage their own models — return a single placeholder entry.
     *
     * @return list<array{id: string, name: string, context_length: int, pricing: array}>
     */
    private function fetchAcpModels(): array
    {
        return [
            [
                'id' => 'acp-agent',
                'name' => 'ACP Agent (model managed by agent)',
                'context_length' => 0,
                'pricing' => [],
            ],
        ];
    }

    /**
     * Send an HTTP request with the configured LLM timeout.
     */
    private function sendLlmRequest(RequestInterface $request): ResponseInterface
    {
        $timeout = $this->settings->getTimeout();

        if (class_exists(Client::class)) {
            return new Client(['timeout' => $timeout])->sendRequest($request);
        }

        return $this->httpClient->sendRequest($request);
    }
}
