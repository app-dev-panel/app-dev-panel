<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Project;

use AppDevPanel\Kernel\Helper\Silencer;
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

        if (!is_file($file) || !is_readable($file)) {
            return ProjectConfig::empty();
        }

        $contents = file_get_contents($file);
        if ($contents === false) {
            return ProjectConfig::empty();
        }

        // An empty file is a JsonException below and therefore also yields the empty config.

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

        chmod($tmp, 0o644);

        // Muted: the rename races with concurrent writers / external editors; failure is reported below.
        if (!Silencer::run(static fn(): bool => rename($tmp, $target))) {
            unlink($tmp); // a failed rename leaves the temp file in place
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

        // Muted: a concurrent process may create the directory between the checks.
        $created = Silencer::run(fn(): bool => mkdir($this->configDir, 0o755, true));
        if (!$created && !is_dir($this->configDir)) {
            throw new RuntimeException(sprintf('Failed to create project config directory "%s".', $this->configDir));
        }
    }

    /**
     * Best effort: the `.gitignore` is a convenience, so write failures are muted and
     * never block saving `project.json` itself.
     */
    private function ensureGitignore(): void
    {
        $path = rtrim($this->configDir, '/\\') . DIRECTORY_SEPARATOR . self::GITIGNORE_FILENAME;

        if (!is_file($path)) {
            $contents = self::GITIGNORE_HEADER . self::GITIGNORE_SECRETS_LINE . "\n";
            Silencer::run(static fn(): int|false => file_put_contents($path, $contents));
            return;
        }

        $existing = is_readable($path) ? file_get_contents($path) : false;
        if ($existing === false || self::hasSecretsRule($existing)) {
            return;
        }

        $line = (str_ends_with($existing, "\n") ? '' : "\n") . self::GITIGNORE_SECRETS_LINE . "\n";
        Silencer::run(static fn(): int|false => file_put_contents($path, $line, FILE_APPEND));
    }

    private static function hasSecretsRule(string $gitignore): bool
    {
        $lines = array_map(trim(...), preg_split('/\r\n|\n|\r/', $gitignore) ?: []);

        return in_array(self::GITIGNORE_SECRETS_LINE, $lines, true);
    }
}
