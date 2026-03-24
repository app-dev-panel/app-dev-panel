<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Mcp;

/**
 * Manages MCP server enabled/disabled state.
 *
 * Persists the setting as a JSON file in the storage directory.
 */
final class McpSettings
{
    private const string FILENAME = 'mcp-settings.json';

    public function __construct(
        private readonly string $storagePath,
    ) {}

    public function isEnabled(): bool
    {
        $file = $this->filePath();

        if (!is_file($file)) {
            return true;
        }

        $data = json_decode((string) file_get_contents($file), true);

        return ($data['enabled'] ?? true) === true;
    }

    public function setEnabled(bool $enabled): void
    {
        $dir = $this->storagePath;
        if (!is_dir($dir)) {
            mkdir($dir, 0o777, true);
        }

        file_put_contents(
            $this->filePath(),
            json_encode(['enabled' => $enabled], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT),
            LOCK_EX,
        );
    }

    private function filePath(): string
    {
        return rtrim($this->storagePath, '/') . '/' . self::FILENAME;
    }
}
