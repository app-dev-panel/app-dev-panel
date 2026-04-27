<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Project;

use AppDevPanel\Kernel\Project\FileProjectConfigStorage;
use AppDevPanel\Kernel\Project\ProjectConfig;
use PHPUnit\Framework\TestCase;

final class FileProjectConfigStorageTest extends TestCase
{
    private string $configDir;

    protected function setUp(): void
    {
        $this->configDir = sys_get_temp_dir() . '/adp-project-config-' . uniqid('', true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->configDir);
    }

    public function testLoadFromMissingDirReturnsEmpty(): void
    {
        $storage = new FileProjectConfigStorage($this->configDir);

        $config = $storage->load();

        $this->assertSame([], $config->frames);
        $this->assertSame([], $config->openapi);
    }

    public function testSaveCreatesDirAndFile(): void
    {
        $storage = new FileProjectConfigStorage($this->configDir);
        $config = new ProjectConfig(frames: ['Logs' => 'https://logs.example/'], openapi: ['Main' => '/openapi.json']);

        $storage->save($config);

        $this->assertDirectoryExists($this->configDir);
        $this->assertFileExists($this->configDir . '/project.json');
    }

    public function testSavePersistsRoundTrip(): void
    {
        $storage = new FileProjectConfigStorage($this->configDir);
        $config = new ProjectConfig(frames: [
            'Logs' => 'https://logs.example/',
            'Metrics' => 'https://metrics.example/',
        ], openapi: ['Main' => '/openapi.json']);

        $storage->save($config);

        $loaded = new FileProjectConfigStorage($this->configDir)->load();
        $this->assertSame($config->frames, $loaded->frames);
        $this->assertSame($config->openapi, $loaded->openapi);
    }

    public function testSaveWritesPrettyJsonWithVersion(): void
    {
        $storage = new FileProjectConfigStorage($this->configDir);
        $storage->save(new ProjectConfig(frames: ['A' => 'https://a/']));

        $contents = (string) file_get_contents($this->configDir . '/project.json');
        $this->assertStringContainsString("\n", $contents, 'project.json should be pretty-printed');

        $decoded = json_decode($contents, true);
        $this->assertIsArray($decoded);
        $this->assertSame(ProjectConfig::CURRENT_VERSION, $decoded['version']);
    }

    public function testSaveCreatesGitignoreWithSecretsRule(): void
    {
        $storage = new FileProjectConfigStorage($this->configDir);
        $storage->save(ProjectConfig::empty());

        $gitignore = $this->configDir . '/.gitignore';
        $this->assertFileExists($gitignore);
        $this->assertStringContainsString('secrets.json', (string) file_get_contents($gitignore));
    }

    public function testSaveDoesNotDuplicateGitignoreRule(): void
    {
        $storage = new FileProjectConfigStorage($this->configDir);
        $storage->save(ProjectConfig::empty());
        $storage->save(ProjectConfig::empty());

        $gitignore = (string) file_get_contents($this->configDir . '/.gitignore');
        $matches = preg_match_all('/^secrets\.json$/m', $gitignore, $captured);
        $this->assertSame(1, $matches);
        $this->assertSame(['secrets.json'], $captured[0]);
    }

    public function testSaveAppendsSecretsRuleToExistingGitignore(): void
    {
        mkdir($this->configDir, 0o755, true);
        file_put_contents($this->configDir . '/.gitignore', "# Existing rule\n*.log\n");

        $storage = new FileProjectConfigStorage($this->configDir);
        $storage->save(ProjectConfig::empty());

        $gitignore = (string) file_get_contents($this->configDir . '/.gitignore');
        $this->assertStringContainsString('*.log', $gitignore);
        $this->assertStringContainsString('secrets.json', $gitignore);
    }

    public function testLoadIgnoresMalformedJson(): void
    {
        mkdir($this->configDir, 0o755, true);
        file_put_contents($this->configDir . '/project.json', '{not valid json');

        $config = new FileProjectConfigStorage($this->configDir)->load();

        $this->assertSame([], $config->frames);
        $this->assertSame([], $config->openapi);
    }

    public function testLoadHandlesPartialFile(): void
    {
        mkdir($this->configDir, 0o755, true);
        file_put_contents($this->configDir . '/project.json', json_encode(['frames' => [
            'Only' => 'https://only.example/',
        ]]));

        $config = new FileProjectConfigStorage($this->configDir)->load();

        $this->assertSame(['Only' => 'https://only.example/'], $config->frames);
        $this->assertSame([], $config->openapi);
    }

    public function testGetConfigDirReturnsConstructorPath(): void
    {
        $storage = new FileProjectConfigStorage($this->configDir);
        $this->assertSame($this->configDir, $storage->getConfigDir());
    }

    public function testSaveOverwritesPreviousFile(): void
    {
        $storage = new FileProjectConfigStorage($this->configDir);
        $storage->save(new ProjectConfig(frames: ['Old' => 'https://old/']));
        $storage->save(new ProjectConfig(frames: ['New' => 'https://new/']));

        $loaded = new FileProjectConfigStorage($this->configDir)->load();
        $this->assertSame(['New' => 'https://new/'], $loaded->frames);
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
