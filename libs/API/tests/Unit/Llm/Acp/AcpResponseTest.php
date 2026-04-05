<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Llm\Acp;

use AppDevPanel\Api\Llm\Acp\AcpResponse;
use PHPUnit\Framework\TestCase;

final class AcpResponseTest extends TestCase
{
    public function testBasicResponse(): void
    {
        $response = new AcpResponse(
            text: 'Hello from ACP agent',
            stopReason: 'end_turn',
            agentName: 'Claude Code',
            agentVersion: '1.0.0',
        );

        $this->assertSame('Hello from ACP agent', $response->text);
        $this->assertSame('end_turn', $response->stopReason);
        $this->assertSame('Claude Code', $response->agentName);
        $this->assertSame('1.0.0', $response->agentVersion);
        $this->assertSame([], $response->toolCalls);
    }

    public function testToOpenAiFormat(): void
    {
        $response = new AcpResponse(
            text: 'Analysis complete.',
            stopReason: 'end_turn',
            agentName: 'Claude Code',
            agentVersion: '2.0.0',
            toolCalls: [
                ['role' => 'tool', 'content' => 'list_debug_entries'],
            ],
        );

        $result = $response->toOpenAiFormat('acp-agent');

        $this->assertSame('Analysis complete.', $result['choices'][0]['message']['content']);
        $this->assertSame('assistant', $result['choices'][0]['message']['role']);
        $this->assertSame('acp-agent', $result['model']);
        $this->assertSame([], $result['usage']);
        $this->assertSame('Claude Code', $result['acp']['agentName']);
        $this->assertSame('2.0.0', $result['acp']['agentVersion']);
        $this->assertSame('end_turn', $result['acp']['stopReason']);
        $this->assertSame(1, $result['acp']['toolCallCount']);
    }

    public function testDefaults(): void
    {
        $response = new AcpResponse(text: 'Hello');

        $this->assertSame('end_turn', $response->stopReason);
        $this->assertSame('', $response->agentName);
        $this->assertSame('', $response->agentVersion);
        $this->assertSame([], $response->toolCalls);
    }

    public function testToOpenAiFormatDefaultModel(): void
    {
        $response = new AcpResponse(text: 'Test');
        $result = $response->toOpenAiFormat();

        $this->assertSame('acp', $result['model']);
    }
}
