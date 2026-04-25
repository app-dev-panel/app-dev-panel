<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Llm\Acp;

/**
 * Value object representing a collected ACP agent response.
 *
 * Aggregates streamed session/update chunks into a final response.
 */
final readonly class AcpResponse
{
    /**
     * @param list<array{role: string, content: string}> $toolCalls Tool calls executed during the prompt
     */
    public function __construct(
        public string $text,
        public string $stopReason = 'end_turn',
        public string $agentName = '',
        public string $agentVersion = '',
        public array $toolCalls = [],
    ) {}

    /**
     * Convert to OpenAI-compatible chat completion format for uniform frontend handling.
     */
    public function toOpenAiFormat(string $model = 'acp'): array
    {
        return [
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => $this->text,
                    ],
                ],
            ],
            'model' => $model,
            'usage' => [],
            'acp' => [
                'agentName' => $this->agentName,
                'agentVersion' => $this->agentVersion,
                'stopReason' => $this->stopReason,
                'toolCallCount' => count($this->toolCalls),
            ],
        ];
    }
}
