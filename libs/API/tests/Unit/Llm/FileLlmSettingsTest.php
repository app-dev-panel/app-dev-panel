<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Llm;

use AppDevPanel\Api\Llm\FileLlmSettings;
use AppDevPanel\Kernel\Project\FileSecretsStorage;
use PHPUnit\Framework\TestCase;

final class FileLlmSettingsTest extends TestCase
{
    private string $runtimeDir;
    private string $configDir;

    protected function setUp(): void
    {
        $base = sys_get_temp_dir() . '/adp-llm-settings-' . uniqid('', true);
        $this->runtimeDir = $base . '/runtime';
        $this->configDir = $base . '/config-adp';
        mkdir($this->runtimeDir, 0o755, true);
        mkdir($this->configDir, 0o755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->runtimeDir);
        $this->removeDir($this->configDir);
        $base = dirname($this->runtimeDir);
        @rmdir($base);
    }

    private function build(): FileLlmSettings
    {
        return new FileLlmSettings($this->runtimeDir, new FileSecretsStorage($this->configDir));
    }

    public function testDefaults(): void
    {
        $settings = $this->build();

        $this->assertNull($settings->getApiKey());
        $this->assertSame('openrouter', $settings->getProvider());
        $this->assertNull($settings->getModel());
        $this->assertSame(30, $settings->getTimeout());
        $this->assertFalse($settings->isConnected());
    }

    public function testSetAndGetApiKey(): void
    {
        $settings = $this->build();
        $settings->setApiKey('sk-test-123');

        $this->assertSame('sk-test-123', $settings->getApiKey());
        $this->assertTrue($settings->isConnected());
    }

    public function testSetAndGetProvider(): void
    {
        $settings = $this->build();
        $settings->setProvider('anthropic');

        $this->assertSame('anthropic', $settings->getProvider());
    }

    public function testSetAndGetModel(): void
    {
        $settings = $this->build();
        $settings->setModel('claude-3-opus');

        $this->assertSame('claude-3-opus', $settings->getModel());
    }

    public function testSetTimeout(): void
    {
        $settings = $this->build();
        $settings->setTimeout(60);

        $this->assertSame(60, $settings->getTimeout());
    }

    public function testSetTimeoutClampedMin(): void
    {
        $settings = $this->build();
        $settings->setTimeout(1);

        $this->assertSame(5, $settings->getTimeout());
    }

    public function testSetTimeoutClampedMax(): void
    {
        $settings = $this->build();
        $settings->setTimeout(999);

        $this->assertSame(300, $settings->getTimeout());
    }

    public function testSetAndGetCustomPrompt(): void
    {
        $settings = $this->build();
        $settings->setCustomPrompt('Be helpful');

        $this->assertSame('Be helpful', $settings->getCustomPrompt());
    }

    public function testClear(): void
    {
        $settings = $this->build();
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
        $settings = $this->build();
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
        $settings = $this->build();
        $settings->setApiKey('sk-persist');
        $settings->setModel('gpt-4');

        // New instance backed by the same directories should read from disk.
        $settings2 = $this->build();
        $this->assertSame('sk-persist', $settings2->getApiKey());
        $this->assertSame('gpt-4', $settings2->getModel());
    }

    public function testClearWritesEmptySecretsFile(): void
    {
        $settings = $this->build();
        $settings->setApiKey('sk-test');
        $settings->clear();

        // The secrets file is rewritten with an empty document; reading it back
        // produces a config with no LLM data.
        $reread = $this->build();
        $this->assertNull($reread->getApiKey());
        $this->assertFalse($reread->isConnected());
    }

    public function testIsConnectedWithEmptyString(): void
    {
        $settings = $this->build();
        $settings->setApiKey('');

        $this->assertFalse($settings->isConnected());
    }

    public function testDefaultCustomPrompt(): void
    {
        $settings = $this->build();

        $this->assertStringContainsString('English', $settings->getCustomPrompt());
    }

    public function testSetNullApiKey(): void
    {
        $settings = $this->build();
        $settings->setApiKey('key');
        $settings->setApiKey(null);

        $this->assertNull($settings->getApiKey());
        $this->assertFalse($settings->isConnected());
    }

    public function testSetNullModel(): void
    {
        $settings = $this->build();
        $settings->setModel('gpt-4');
        $settings->setModel(null);

        $this->assertNull($settings->getModel());
    }

    public function testToArrayWithDefaults(): void
    {
        $settings = $this->build();

        $arr = $settings->toArray();

        $this->assertFalse($arr['connected']);
        $this->assertSame('openrouter', $arr['provider']);
        $this->assertNull($arr['model']);
        $this->assertSame(30, $arr['timeout']);
        $this->assertStringContainsString('English', $arr['customPrompt']);
    }

    public function testClearResetsCustomPromptToDefault(): void
    {
        $settings = $this->build();
        $settings->setCustomPrompt('Custom prompt');
        $settings->clear();

        $this->assertStringContainsString('English', $settings->getCustomPrompt());
    }

    // --- Migration from legacy .llm-settings.json -------------------------------

    public function testMigratesLegacyFileOnFirstLoad(): void
    {
        // Pretend an older install left settings in runtime/.llm-settings.json.
        file_put_contents(
            $this->runtimeDir . '/.llm-settings.json',
            (string) json_encode([
                'apiKey' => 'sk-legacy',
                'provider' => 'anthropic',
                'model' => 'claude-3-haiku',
                'timeout' => 45,
            ]),
        );

        $settings = $this->build();

        // First read auto-migrates; values become available transparently.
        $this->assertSame('sk-legacy', $settings->getApiKey());
        $this->assertSame('anthropic', $settings->getProvider());
        $this->assertSame('claude-3-haiku', $settings->getModel());
        $this->assertSame(45, $settings->getTimeout());

        // The new canonical file must exist with the migrated values.
        $this->assertFileExists($this->configDir . '/secrets.json');

        // The legacy file is renamed (not deleted) so an admin can recover.
        $this->assertFileDoesNotExist($this->runtimeDir . '/.llm-settings.json');
        $this->assertFileExists($this->runtimeDir . '/.llm-settings.json.migrated');

        // A second instance reads only from the new file, never re-migrating.
        @unlink($this->runtimeDir . '/.llm-settings.json.migrated');
        $secondInstance = $this->build();
        $this->assertSame('sk-legacy', $secondInstance->getApiKey());
    }

    public function testNoMigrationWhenLegacyFileMissing(): void
    {
        $settings = $this->build();
        $settings->getApiKey(); // triggers load()

        $this->assertFileDoesNotExist($this->runtimeDir . '/.llm-settings.json.migrated');
    }

    public function testNoMigrationWhenSecretsAlreadyHasData(): void
    {
        // Pre-existing secrets file (e.g. user already migrated, or pre-existing v1).
        $settings = $this->build();
        $settings->setApiKey('sk-current');

        // Legacy file appears later (someone restored a backup).
        file_put_contents($this->runtimeDir . '/.llm-settings.json', (string) json_encode(['apiKey' => 'sk-stale']));

        $reread = $this->build();
        $this->assertSame('sk-current', $reread->getApiKey());
        // Legacy file is NOT touched because the canonical store already has data.
        $this->assertFileExists($this->runtimeDir . '/.llm-settings.json');
        $this->assertFileDoesNotExist($this->runtimeDir . '/.llm-settings.json.migrated');
    }

    public function testMigrationIgnoresMalformedLegacyFile(): void
    {
        file_put_contents($this->runtimeDir . '/.llm-settings.json', '{not json');

        $settings = $this->build();
        $this->assertNull($settings->getApiKey());
        // Malformed legacy file is left in place — operator can inspect / fix.
        $this->assertFileExists($this->runtimeDir . '/.llm-settings.json');
    }

    // --- ACP --------------------------------------------------------------------

    public function testAcpCommandDefaults(): void
    {
        $settings = $this->build();

        $this->assertSame('claude', $settings->getAcpCommand());
        $this->assertSame([], $settings->getAcpArgs());
        $this->assertSame([], $settings->getAcpEnv());
    }

    public function testSetAndGetAcpCommand(): void
    {
        $settings = $this->build();
        $settings->setAcpCommand('gemini');

        $this->assertSame('gemini', $settings->getAcpCommand());
    }

    public function testSetAndGetAcpArgs(): void
    {
        $settings = $this->build();
        $settings->setAcpArgs(['--model', 'opus']);

        $this->assertSame(['--model', 'opus'], $settings->getAcpArgs());
    }

    public function testSetAndGetAcpEnv(): void
    {
        $settings = $this->build();
        $settings->setAcpEnv(['MY_CUSTOM_VAR' => 'test-value']);

        $this->assertSame(['MY_CUSTOM_VAR' => 'test-value'], $settings->getAcpEnv());
    }

    public function testAcpProviderIsConnectedWithoutApiKey(): void
    {
        $settings = $this->build();
        $settings->setProvider('acp');

        $this->assertTrue($settings->isConnected());
    }

    public function testAcpProviderIsNotConnectedWithEmptyCommand(): void
    {
        $settings = $this->build();
        $settings->setProvider('acp');
        $settings->setAcpCommand('');

        $this->assertFalse($settings->isConnected());
    }

    public function testAcpSettingsPersistToDisk(): void
    {
        $settings = $this->build();
        $settings->setProvider('acp');
        $settings->setAcpCommand('codex');
        $settings->setAcpArgs(['--flag']);
        $settings->setAcpEnv(['KEY' => 'val']);

        $settings2 = $this->build();
        $this->assertSame('acp', $settings2->getProvider());
        $this->assertSame('codex', $settings2->getAcpCommand());
        $this->assertSame(['--flag'], $settings2->getAcpArgs());
        $this->assertSame(['KEY' => 'val'], $settings2->getAcpEnv());
    }

    public function testClearResetsAcpSettings(): void
    {
        $settings = $this->build();
        $settings->setAcpCommand('gemini');
        $settings->setAcpArgs(['--arg']);
        $settings->setAcpEnv(['K' => 'V']);
        $settings->clear();

        $this->assertSame('claude', $settings->getAcpCommand());
        $this->assertSame([], $settings->getAcpArgs());
        $this->assertSame([], $settings->getAcpEnv());
    }

    public function testToArrayIncludesAcpFields(): void
    {
        $settings = $this->build();
        $settings->setProvider('acp');
        $settings->setAcpCommand('gemini');

        $arr = $settings->toArray();

        $this->assertSame('gemini', $arr['acpCommand']);
        $this->assertSame([], $arr['acpArgs']);
        $this->assertSame([], $arr['acpEnv']);
    }

    public function testGetStoragePathReturnsRuntimeDir(): void
    {
        $settings = $this->build();

        $this->assertSame($this->runtimeDir, $settings->getStoragePath());
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach ((array) scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..' || !is_string($entry)) {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($path)) {
                $this->removeDir($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
