<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Llm;

use AppDevPanel\Api\Llm\LlmContextBuilder;
use PHPUnit\Framework\TestCase;

final class LlmContextBuilderTest extends TestCase
{
    public function testPrependBrowserContextNoOpForNullOrEmpty(): void
    {
        $builder = new LlmContextBuilder();
        $messages = [['role' => 'user', 'content' => 'hi']];

        $this->assertSame($messages, $builder->prependBrowserContext($messages, 'anthropic', null));
        $this->assertSame($messages, $builder->prependBrowserContext($messages, 'anthropic', []));
    }

    public function testPrependBrowserContextNoOpWhenOnlyEmptyFields(): void
    {
        $builder = new LlmContextBuilder();
        $messages = [['role' => 'user', 'content' => 'hi']];

        // All fields empty → only the intro line would remain → no-op.
        $result = $builder->prependBrowserContext($messages, 'anthropic', [
            'url' => '',
            'title' => '',
            'userAgent' => '',
        ]);

        $this->assertSame($messages, $result);
    }

    public function testAnthropicGetsSystemMessage(): void
    {
        $builder = new LlmContextBuilder();
        $messages = [['role' => 'user', 'content' => 'hi']];

        $result = $builder->prependBrowserContext($messages, 'anthropic', [
            'url' => 'http://localhost/debug?collector=log&debugEntry=xyz',
            'title' => 'Debug',
            'userAgent' => 'UA/1.0',
            'language' => 'en',
            'timezone' => 'UTC',
            'theme' => 'dark',
            'referrer' => 'http://localhost/',
            'viewport' => ['width' => 1920, 'height' => 1080],
            'screen' => ['width' => 2560, 'height' => 1440, 'devicePixelRatio' => 2.0],
        ]);

        $this->assertCount(2, $result);
        $this->assertSame('system', $result[0]['role']);
        $content = $result[0]['content'];
        $this->assertStringContainsString('Debug entry ID: xyz', $content);
        $this->assertStringContainsString('Selected collector: log', $content);
        $this->assertStringContainsString('Viewport: 1920x1080', $content);
        $this->assertStringContainsString('Screen: 2560x1440 @2x', $content);
        $this->assertStringContainsString('Theme: dark', $content);
    }

    public function testOpenaiAlsoGetsSystemMessage(): void
    {
        $builder = new LlmContextBuilder();
        $messages = [['role' => 'user', 'content' => 'hi']];

        $result = $builder->prependBrowserContext($messages, 'openai', ['title' => 'X']);

        $this->assertCount(2, $result);
        $this->assertSame('system', $result[0]['role']);
    }

    public function testOpenrouterMergesIntoFirstUser(): void
    {
        $builder = new LlmContextBuilder();
        $messages = [
            ['role' => 'user', 'content' => 'hi'],
            ['role' => 'assistant', 'content' => 'hello'],
            ['role' => 'user', 'content' => 'more'],
        ];

        $result = $builder->prependBrowserContext($messages, 'openrouter', [
            'url' => 'http://localhost/?debugEntry=abc',
        ]);

        $this->assertCount(3, $result);
        $this->assertStringContainsString('Debug entry ID: abc', $result[0]['content']);
        $this->assertStringEndsWith('hi', $result[0]['content']);
        // Second user message is left intact.
        $this->assertSame('more', $result[2]['content']);
    }

    public function testPrependCustomPromptNoOpForEmpty(): void
    {
        $builder = new LlmContextBuilder();
        $messages = [['role' => 'user', 'content' => 'hi']];

        $this->assertSame($messages, $builder->prependCustomPrompt($messages, 'anthropic', ''));
    }

    public function testPrependCustomPromptAddsSystemForAnthropic(): void
    {
        $builder = new LlmContextBuilder();
        $messages = [['role' => 'user', 'content' => 'hi']];

        $result = $builder->prependCustomPrompt($messages, 'anthropic', 'Be terse');

        $this->assertCount(2, $result);
        $this->assertSame(['role' => 'system', 'content' => 'Be terse'], $result[0]);
    }

    public function testPrependCustomPromptMergesIntoUserForOthers(): void
    {
        $builder = new LlmContextBuilder();
        $messages = [['role' => 'user', 'content' => 'hi']];

        $result = $builder->prependCustomPrompt($messages, 'openrouter', 'Be terse');

        $this->assertCount(1, $result);
        $this->assertStringStartsWith('[Instructions: Be terse]', $result[0]['content']);
        $this->assertStringEndsWith('hi', $result[0]['content']);
    }

    public function testSupportsSystemRole(): void
    {
        $builder = new LlmContextBuilder();

        $this->assertTrue($builder->supportsSystemRole('anthropic'));
        $this->assertTrue($builder->supportsSystemRole('openai'));
        $this->assertFalse($builder->supportsSystemRole('openrouter'));
        $this->assertFalse($builder->supportsSystemRole('acp'));
    }
}
