<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Llm\Acp;

use RuntimeException;

/**
 * ACP (Agent Client Protocol) client implementation.
 *
 * Acts as an ACP client (the role normally played by editors like Zed/JetBrains)
 * to communicate with ACP agents (Claude Code, Gemini CLI, Codex CLI, etc.).
 *
 * Protocol: JSON-RPC 2.0 over stdio.
 * Spec: https://agentclientprotocol.com/protocol/overview
 */
final class AcpClient
{
    private const int PROTOCOL_VERSION = 1;
    private const string CLIENT_NAME = 'ADP';
    private const string CLIENT_VERSION = '1.0.0';

    private int $nextId = 1;

    public function __construct(
        private readonly AcpTransportInterface $transport,
    ) {}

    /**
     * Spawn an ACP agent and perform the full chat lifecycle:
     * initialize → session/new → session/prompt → close.
     *
     * @param list<array{role: string, content: string}> $messages Chat messages
     * @param list<string> $args CLI arguments for the agent
     * @param array<string, string> $env Environment variables for the agent
     */
    public function chat(
        string $command,
        array $messages,
        array $args = [],
        array $env = [],
        float $timeout = 60.0,
        string $customPrompt = '',
    ): AcpResponse {
        try {
            $this->transport->spawn($command, $args, $env);

            $initResult = $this->initialize($timeout);
            $agentName = $initResult['agentInfo']['name'] ?? '';
            $agentVersion = $initResult['agentInfo']['version'] ?? '';

            $sessionId = $this->createSession($timeout);

            $promptContent = $this->buildPromptContent($messages, $customPrompt);

            return $this->sendPrompt($sessionId, $promptContent, $agentName, $agentVersion, $timeout);
        } finally {
            $this->transport->close();
        }
    }

    /**
     * Send initialize request and return agent capabilities.
     */
    private function initialize(float $timeout): array
    {
        $this->sendRequest('initialize', [
            'protocolVersion' => self::PROTOCOL_VERSION,
            'capabilities' => (object) [],
            'clientInfo' => [
                'name' => self::CLIENT_NAME,
                'version' => self::CLIENT_VERSION,
            ],
        ]);

        $response = $this->receiveResponse($timeout);

        if (isset($response['error'])) {
            throw new RuntimeException(sprintf(
                'ACP initialize failed: %s',
                $response['error']['message'] ?? 'Unknown error',
            ));
        }

        return $response['result'] ?? [];
    }

    /**
     * Create a new ACP session and return the session ID.
     */
    private function createSession(float $timeout): string
    {
        $this->sendRequest('session/new', (object) []);

        $response = $this->receiveResponse($timeout);

        if (isset($response['error'])) {
            throw new RuntimeException(sprintf(
                'ACP session/new failed: %s',
                $response['error']['message'] ?? 'Unknown error',
            ));
        }

        $sessionId = $response['result']['sessionId'] ?? null;
        if (!is_string($sessionId) || $sessionId === '') {
            throw new RuntimeException('ACP agent did not return a session ID.');
        }

        return $sessionId;
    }

    /**
     * Send a prompt and collect the streaming response.
     */
    private function sendPrompt(
        string $sessionId,
        array $content,
        string $agentName,
        string $agentVersion,
        float $timeout,
    ): AcpResponse {
        $requestId = $this->sendRequest('session/prompt', [
            'sessionId' => $sessionId,
            'prompt' => [
                'content' => $content,
            ],
        ]);

        return $this->collectPromptResponse($requestId, $agentName, $agentVersion, $timeout);
    }

    /**
     * Collect session/update notifications until the final session/prompt response arrives.
     */
    private function collectPromptResponse(
        int $requestId,
        string $agentName,
        string $agentVersion,
        float $timeout,
    ): AcpResponse {
        $textParts = [];
        $toolCalls = [];
        $stopReason = 'end_turn';
        $deadline = microtime(true) + $timeout;

        while (microtime(true) < $deadline) {
            $remaining = $deadline - microtime(true);
            $message = $this->transport->receive(min($remaining, 5.0));

            if ($message === null) {
                if (!$this->transport->isAlive()) {
                    $stderr = $this->transport->readStderr();
                    throw new RuntimeException(sprintf(
                        'ACP agent process terminated unexpectedly.%s',
                        $stderr !== '' ? " stderr: {$stderr}" : '',
                    ));
                }
                continue;
            }

            // Notification (no id) — session/update
            if (!isset($message['id'])) {
                $this->handleNotification($message, $textParts, $toolCalls);
                continue;
            }

            // Response to our prompt request
            if ($message['id'] === $requestId) {
                if (isset($message['error'])) {
                    throw new RuntimeException(sprintf(
                        'ACP session/prompt failed: %s',
                        $message['error']['message'] ?? 'Unknown error',
                    ));
                }

                $stopReason = $message['result']['stopReason'] ?? 'end_turn';
                break;
            }

            // Response to a different request (e.g., agent calling us) — handle gracefully
            $this->handleAgentRequest($message);
        }

        return new AcpResponse(
            text: implode('', $textParts),
            stopReason: $stopReason,
            agentName: $agentName,
            agentVersion: $agentVersion,
            toolCalls: $toolCalls,
        );
    }

    /**
     * Handle a session/update notification by extracting text chunks and tool calls.
     *
     * @param list<string> $textParts Accumulated text parts (by reference)
     * @param list<array{role: string, content: string}> $toolCalls Accumulated tool calls (by reference)
     *
     * @param-out list<string> $textParts
     * @param-out list<array{role: string, content: string}> $toolCalls
     */
    private function handleNotification(array $message, array &$textParts, array &$toolCalls): void
    {
        $method = $message['method'] ?? '';
        if ($method !== 'session/update') {
            return;
        }

        $update = $message['params']['update'] ?? [];
        $type = $update['type'] ?? '';

        if ($type === 'message_chunk') {
            $content = $update['content'] ?? [];
            foreach ($content as $block) {
                if (($block['type'] ?? '') === 'text' && isset($block['text'])) {
                    $textParts[] = (string) $block['text'];
                }
            }
        }

        if ($type === 'tool_call_start' || $type === 'tool_call_update') {
            $toolName = (string) ($update['toolCall']['name'] ?? $update['name'] ?? 'unknown');
            $toolCalls[] = [
                'role' => 'tool',
                'content' => $toolName,
            ];
        }
    }

    /**
     * Handle requests from the agent to the client (e.g., fs/read_text_file, request_permission).
     *
     * MVP: reject all agent-initiated requests gracefully.
     */
    private function handleAgentRequest(array $message): void
    {
        if (!isset($message['method']) || !isset($message['id'])) {
            return;
        }

        // Agent is requesting something from us (client).
        // MVP: respond with "method not found" for all agent requests.
        $this->transport->send([
            'jsonrpc' => '2.0',
            'id' => $message['id'],
            'error' => [
                'code' => -32601,
                'message' => 'Method not supported by ADP ACP client.',
            ],
        ]);
    }

    /**
     * Build ACP content blocks from chat messages.
     *
     * Merges system messages and user messages into a single prompt.
     *
     * @param list<array{role: string, content: string}> $messages
     * @return list<array{type: string, text: string}>
     */
    private function buildPromptContent(array $messages, string $customPrompt): array
    {
        $parts = [];

        if ($customPrompt !== '') {
            $parts[] = "[Instructions: {$customPrompt}]";
        }

        foreach ($messages as $message) {
            if (!isset($message['role'], $message['content'])) {
                continue;
            }

            $role = (string) $message['role'];
            $content = (string) $message['content'];

            if ($role === 'system') {
                $parts[] = "[System: {$content}]";
            } elseif ($role === 'assistant') {
                $parts[] = "[Previous assistant response: {$content}]";
            } else {
                $parts[] = $content;
            }
        }

        return [
            [
                'type' => 'text',
                'text' => implode("\n\n", $parts),
            ],
        ];
    }

    /**
     * Send a JSON-RPC request and return the request ID.
     */
    private function sendRequest(string $method, mixed $params): int
    {
        $id = $this->nextId++;

        $this->transport->send([
            'jsonrpc' => '2.0',
            'id' => $id,
            'method' => $method,
            'params' => $params,
        ]);

        return $id;
    }

    /**
     * Wait for a JSON-RPC response (skipping notifications).
     */
    private function receiveResponse(float $timeout): array
    {
        $deadline = microtime(true) + $timeout;

        while (microtime(true) < $deadline) {
            $remaining = $deadline - microtime(true);
            $message = $this->transport->receive(min($remaining, 5.0));

            if ($message === null) {
                if (!$this->transport->isAlive()) {
                    $stderr = $this->transport->readStderr();
                    throw new RuntimeException(sprintf(
                        'ACP agent process terminated during initialization.%s',
                        $stderr !== '' ? " stderr: {$stderr}" : '',
                    ));
                }
                continue;
            }

            // Skip notifications (no id).
            if (isset($message['id'])) {
                return $message;
            }
        }

        throw new RuntimeException('Timeout waiting for ACP agent response.');
    }
}
