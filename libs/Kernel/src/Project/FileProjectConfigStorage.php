<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Project;

use JsonException;
use RuntimeException;

/**
 * JSON-file-backed implementation of {@see ProjectConfigStorageInterface}.
 *
 * Writes are atomic (temp-file + rename) and the file is created with `0644`
 * so it is safe to commit to source control. The storage directory is created
 * on first save, and a `.gitignore` is dropped alongside the project file
 * with `secrets.json` pre-listed — this anticipates a future companion file
 * for API keys without forcing users to maintain the rule themselves.
 */
final class FileProjectConfigStorage implements ProjectConfigStorageInterface
{
    public const string PROJECT_FILENAME = 'project.json';
    private const string GITIGNORE_FILENAME = '.gitignore';
    private const string GITIGNORE_HEADER = "# ADP local-only files (never commit)\n";
    private const string GITIGNORE_SECRETS_LINE = 'secrets.json';

    public function __construct(
        private readonly string $configDir,
    ) {}

    public function load(): ProjectConfig
    {
        $file = $this->filePath();

        if (!is_file($file)) {
            return ProjectConfig::empty();
        }

        $contents = @file_get_contents($file);
        if ($contents === false || $contents === '') {
            return ProjectConfig::empty();
        }

        try {
            /** @var array<string, mixed> $data */
            $data = json_decode($contents, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return ProjectConfig::empty();
        }

        return ProjectConfig::fromArray($data);
    }

    public function save(ProjectConfig $config): void
    {
        $this->ensureConfigDir();
        $this->ensureGitignore();

        $payload =
            json_encode(
                $config->toArray(),
                JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            ) . "\n";

        $target = $this->filePath();
        $tmp = $target . '.tmp';

        if (file_put_contents($tmp, $payload, LOCK_EX) === false) {
            throw new RuntimeException(sprintf('Failed to write project config to "%s".', $tmp));
        }

        @chmod($tmp, 0o644);

        if (!@rename($tmp, $target)) {
            @unlink($tmp);
            throw new RuntimeException(sprintf('Failed to move project config to "%s".', $target));
        }
    }

    public function getConfigDir(): string
    {
        return $this->configDir;
    }

    private function filePath(): string
    {
        return rtrim($this->configDir, '/\\') . DIRECTORY_SEPARATOR . self::PROJECT_FILENAME;
    }

    private function ensureConfigDir(): void
    {
        if (is_dir($this->configDir)) {
            return;
        }

        if (!@mkdir($this->configDir, 0o755, true) && !is_dir($this->configDir)) {
            throw new RuntimeException(sprintf('Failed to create project config directory "%s".', $this->configDir));
        }
    }

    private function ensureGitignore(): void
    {
        $path = rtrim($this->configDir, '/\\') . DIRECTORY_SEPARATOR . self::GITIGNORE_FILENAME;

        if (!is_file($path)) {
            @file_put_contents($path, self::GITIGNORE_HEADER . self::GITIGNORE_SECRETS_LINE . "\n");
            return;
        }

        $existing = @file_get_contents($path);
        if ($existing === false) {
            return;
        }

        $hasRule = false;
        foreach (preg_split('/\r\n|\n|\r/', $existing) ?: [] as $line) {
            if (trim($line) === self::GITIGNORE_SECRETS_LINE) {
                $hasRule = true;
                break;
            }
        }

        if (!$hasRule) {
            $separator = str_ends_with($existing, "\n") ? '' : "\n";
            @file_put_contents($path, $separator . self::GITIGNORE_SECRETS_LINE . "\n", FILE_APPEND);
        }
    }
}
