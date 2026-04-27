<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Project;

use AppDevPanel\Kernel\Project\SecretsConfig;
use PHPUnit\Framework\TestCase;

final class SecretsConfigTest extends TestCase
{
    public function testEmpty(): void
    {
        $config = SecretsConfig::empty();

        self::assertSame([], $config->llm);
        self::assertSame(['version' => SecretsConfig::CURRENT_VERSION, 'llm' => []], $config->toArray());
    }

    public function testFromArrayHydratesKnownLlmKeys(): void
    {
        $config = SecretsConfig::fromArray([
            'llm' => [
                'apiKey' => 'sk-test',
                'provider' => 'anthropic',
                'model' => 'claude-3-opus',
                'timeout' => 60,
                'customPrompt' => 'Be concise',
                'acpCommand' => 'claude',
                'acpArgs' => ['--model', 'opus'],
                'acpEnv' => ['ANTHROPIC_API_KEY' => 'sk-other'],
            ],
        ]);

        self::assertSame('sk-test', $config->llm['apiKey']);
        self::assertSame('anthropic', $config->llm['provider']);
        self::assertSame('claude-3-opus', $config->llm['model']);
        self::assertSame(60, $config->llm['timeout']);
        self::assertSame(['--model', 'opus'], $config->llm['acpArgs']);
        self::assertSame(['ANTHROPIC_API_KEY' => 'sk-other'], $config->llm['acpEnv']);
    }

    public function testFromArrayDropsMalformedAcpEntries(): void
    {
        $config = SecretsConfig::fromArray([
            'llm' => [
                'acpArgs' => ['ok', 42, true, '--flag'],
                'acpEnv' => ['VALID' => 'value', 'BAD_INT' => 1, 'BAD_NULL' => null, 99 => 'numkey'],
            ],
        ]);

        self::assertSame(['ok', '--flag'], $config->llm['acpArgs']);
        self::assertSame(['VALID' => 'value'], $config->llm['acpEnv']);
    }

    public function testFromArrayCoercesEmptyApiKeyToNull(): void
    {
        $config = SecretsConfig::fromArray(['llm' => ['apiKey' => '']]);

        self::assertNull($config->llm['apiKey']);
    }

    public function testFromArrayIgnoresNonStringTimeout(): void
    {
        $config = SecretsConfig::fromArray(['llm' => ['timeout' => 'not-a-number']]);

        self::assertArrayNotHasKey('timeout', $config->llm);
    }

    public function testFromArrayParsesNumericStringTimeout(): void
    {
        $config = SecretsConfig::fromArray(['llm' => ['timeout' => '90']]);

        self::assertSame(90, $config->llm['timeout']);
    }

    public function testWithLlmReplacesEntireSection(): void
    {
        $original = SecretsConfig::fromArray(['llm' => ['apiKey' => 'old', 'model' => 'gpt-4']]);
        $updated = $original->withLlm(['apiKey' => 'new']);

        self::assertNotSame($original, $updated);
        self::assertSame('new', $updated->llm['apiKey']);
        self::assertArrayNotHasKey('model', $updated->llm);
        // Original is unchanged.
        self::assertSame('old', $original->llm['apiKey']);
    }

    public function testWithLlmPatchMergesOverExisting(): void
    {
        $original = SecretsConfig::fromArray([
            'llm' => ['apiKey' => 'sk-old', 'provider' => 'anthropic', 'model' => 'gpt-4'],
        ]);
        $updated = $original->withLlmPatch(['apiKey' => 'sk-new', 'timeout' => 30]);

        self::assertSame('sk-new', $updated->llm['apiKey']);
        self::assertSame('anthropic', $updated->llm['provider']);
        self::assertSame('gpt-4', $updated->llm['model']);
        self::assertSame(30, $updated->llm['timeout']);
    }

    public function testWithLlmPatchNullValueDeletesKey(): void
    {
        $original = SecretsConfig::fromArray(['llm' => ['apiKey' => 'sk-old', 'model' => 'gpt-4']]);
        $updated = $original->withLlmPatch(['apiKey' => null]);

        self::assertArrayNotHasKey('apiKey', $updated->llm);
        self::assertSame('gpt-4', $updated->llm['model']);
    }

    public function testFromArrayIgnoresUnknownKeys(): void
    {
        $config = SecretsConfig::fromArray([
            'llm' => ['apiKey' => 'k', 'unknown' => 'ignored'],
            'other_section' => ['stuff' => 'also-ignored'],
        ]);

        self::assertSame('k', $config->llm['apiKey']);
        self::assertArrayNotHasKey('unknown', $config->llm);
    }

    public function testToArrayIncludesVersion(): void
    {
        $config = SecretsConfig::fromArray(['llm' => ['apiKey' => 'k']]);

        self::assertSame(SecretsConfig::CURRENT_VERSION, $config->toArray()['version']);
    }
}
