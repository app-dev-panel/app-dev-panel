<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Project;

use AppDevPanel\Kernel\Project\FileSecretsStorage;
use AppDevPanel\Kernel\Project\SecretsConfig;
use PHPUnit\Framework\TestCase;

final class FileSecretsStorageTest extends TestCase
{
    private string $configDir;

    protected function setUp(): void
    {
        $this->configDir = sys_get_temp_dir() . '/adp-secrets-' . uniqid('', true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->configDir);
    }

    public function testLoadFromMissingDirReturnsEmpty(): void
    {
        $storage = new FileSecretsStorage($this->configDir);

        self::assertSame([], $storage->load()->llm);
    }

    public function testSaveCreatesDirAndFile(): void
    {
        $storage = new FileSecretsStorage($this->configDir);
        $storage->save(SecretsConfig::fromArray(['llm' => ['apiKey' => 'sk-test']]));

        self::assertDirectoryExists($this->configDir);
        self::assertFileExists($this->configDir . '/secrets.json');
    }

    public function testSavedFileIsNotWorldReadable(): void
    {
        $storage = new FileSecretsStorage($this->configDir);
        $storage->save(SecretsConfig::fromArray(['llm' => ['apiKey' => 'sk-test']]));

        $perms = fileperms($this->configDir . '/secrets.json') & 0o777;
        self::assertSame(0o600, $perms, 'secrets.json must be 0600 (owner-only)');
    }

    public function testRoundTripPreservesAllFields(): void
    {
        $storage = new FileSecretsStorage($this->configDir);
        $config = SecretsConfig::fromArray([
            'llm' => [
                'apiKey' => 'sk-roundtrip',
                'provider' => 'anthropic',
                'model' => 'claude-3-opus',
                'timeout' => 45,
                'customPrompt' => 'Reply concisely',
                'acpCommand' => 'claude',
                'acpArgs' => ['--flag'],
                'acpEnv' => ['ANTHROPIC_API_KEY' => 'sk-env'],
            ],
        ]);

        $storage->save($config);
        $loaded = new FileSecretsStorage($this->configDir)->load();

        self::assertSame($config->llm, $loaded->llm);
    }

    public function testLoadIgnoresMalformedJson(): void
    {
        mkdir($this->configDir, 0o755, true);
        file_put_contents($this->configDir . '/secrets.json', '{not valid json');

        $config = new FileSecretsStorage($this->configDir)->load();

        self::assertSame([], $config->llm);
    }

    public function testSaveOverwritesPreviousFile(): void
    {
        $storage = new FileSecretsStorage($this->configDir);
        $storage->save(SecretsConfig::fromArray(['llm' => ['apiKey' => 'old']]));
        $storage->save(SecretsConfig::fromArray(['llm' => ['apiKey' => 'new']]));

        $loaded = new FileSecretsStorage($this->configDir)->load();
        self::assertSame('new', $loaded->llm['apiKey']);
    }

    public function testGetConfigDirReturnsConstructorArg(): void
    {
        $storage = new FileSecretsStorage($this->configDir);
        self::assertSame($this->configDir, $storage->getConfigDir());
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
