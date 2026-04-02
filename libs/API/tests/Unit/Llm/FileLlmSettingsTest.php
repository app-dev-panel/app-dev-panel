<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Llm;

use AppDevPanel\Api\Llm\FileLlmSettings;
use PHPUnit\Framework\TestCase;

final class FileLlmSettingsTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/adp-llm-settings-' . uniqid();
        mkdir($this->tmpDir, 0o755, true);
    }

    protected function tearDown(): void
    {
        @unlink($this->tmpDir . '/.llm-settings.json');
        @rmdir($this->tmpDir);
    }

    public function testDefaults(): void
    {
        $settings = new FileLlmSettings($this->tmpDir);

        $this->assertNull($settings->getApiKey());
        $this->assertSame('openrouter', $settings->getProvider());
        $this->assertNull($settings->getModel());
        $this->assertSame(30, $settings->getTimeout());
        $this->assertFalse($settings->isConnected());
    }

    public function testSetAndGetApiKey(): void
    {
        $settings = new FileLlmSettings($this->tmpDir);
        $settings->setApiKey('sk-test-123');

        $this->assertSame('sk-test-123', $settings->getApiKey());
        $this->assertTrue($settings->isConnected());
    }

    public function testSetAndGetProvider(): void
    {
        $settings = new FileLlmSettings($this->tmpDir);
        $settings->setProvider('anthropic');

        $this->assertSame('anthropic', $settings->getProvider());
    }

    public function testSetAndGetModel(): void
    {
        $settings = new FileLlmSettings($this->tmpDir);
        $settings->setModel('claude-3-opus');

        $this->assertSame('claude-3-opus', $settings->getModel());
    }

    public function testSetTimeout(): void
    {
        $settings = new FileLlmSettings($this->tmpDir);
        $settings->setTimeout(60);

        $this->assertSame(60, $settings->getTimeout());
    }

    public function testSetTimeoutClampedMin(): void
    {
        $settings = new FileLlmSettings($this->tmpDir);
        $settings->setTimeout(1);

        $this->assertSame(5, $settings->getTimeout());
    }

    public function testSetTimeoutClampedMax(): void
    {
        $settings = new FileLlmSettings($this->tmpDir);
        $settings->setTimeout(999);

        $this->assertSame(300, $settings->getTimeout());
    }

    public function testSetAndGetCustomPrompt(): void
    {
        $settings = new FileLlmSettings($this->tmpDir);
        $settings->setCustomPrompt('Be helpful');

        $this->assertSame('Be helpful', $settings->getCustomPrompt());
    }

    public function testClear(): void
    {
        $settings = new FileLlmSettings($this->tmpDir);
        $settings->setApiKey('sk-test');
        $settings->setModel('gpt-4');
        $settings->setProvider('openai');
        $settings->setTimeout(60);
        $settings->setCustomPrompt('custom');

        $settings->clear();

        $this->assertNull($settings->getApiKey());
        $this->assertNull($settings->getModel());
        $this->assertSame('openrouter', $settings->getProvider());
        $this->assertSame(30, $settings->getTimeout());
        $this->assertFalse($settings->isConnected());
    }

    public function testToArray(): void
    {
        $settings = new FileLlmSettings($this->tmpDir);
        $settings->setApiKey('sk-test');
        $settings->setModel('claude-3');
        $settings->setProvider('anthropic');

        $arr = $settings->toArray();

        $this->assertTrue($arr['connected']);
        $this->assertSame('anthropic', $arr['provider']);
        $this->assertSame('claude-3', $arr['model']);
        $this->assertSame(30, $arr['timeout']);
        $this->assertArrayHasKey('customPrompt', $arr);
    }

    public function testPersistenceToDisk(): void
    {
        $settings = new FileLlmSettings($this->tmpDir);
        $settings->setApiKey('sk-persist');
        $settings->setModel('gpt-4');

        // New instance should read from disk
        $settings2 = new FileLlmSettings($this->tmpDir);
        $this->assertSame('sk-persist', $settings2->getApiKey());
        $this->assertSame('gpt-4', $settings2->getModel());
    }

    public function testClearDeletesFile(): void
    {
        $settings = new FileLlmSettings($this->tmpDir);
        $settings->setApiKey('sk-test');
        $settings->clear();

        $this->assertFileDoesNotExist($this->tmpDir . '/.llm-settings.json');
    }

    public function testIsConnectedWithEmptyString(): void
    {
        $settings = new FileLlmSettings($this->tmpDir);
        $settings->setApiKey('');

        $this->assertFalse($settings->isConnected());
    }

    public function testDefaultCustomPrompt(): void
    {
        $settings = new FileLlmSettings($this->tmpDir);
        $prompt = $settings->getCustomPrompt();

        $this->assertStringContainsString('English', $prompt);
    }

    public function testSetNullApiKey(): void
    {
        $settings = new FileLlmSettings($this->tmpDir);
        $settings->setApiKey('key');
        $settings->setApiKey(null);

        $this->assertNull($settings->getApiKey());
        $this->assertFalse($settings->isConnected());
    }

    public function testSetNullModel(): void
    {
        $settings = new FileLlmSettings($this->tmpDir);
        $settings->setModel('gpt-4');
        $settings->setModel(null);

        $this->assertNull($settings->getModel());
    }
}
