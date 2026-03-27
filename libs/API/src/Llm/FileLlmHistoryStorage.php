<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Llm;

final class FileLlmHistoryStorage implements LlmHistoryStorageInterface
{
    private const int MAX_ENTRIES = 100;

    /** @var list<array{query: string, response: string, timestamp: int, error?: string}> */
    private array $entries = [];
    private bool $loaded = false;

    public function __construct(
        private readonly string $storagePath,
    ) {}

    public function getAll(): array
    {
        $this->load();

        return $this->entries;
    }

    public function add(array $entry): void
    {
        $this->load();
        array_unshift($this->entries, $entry);
        $this->entries = array_slice($this->entries, 0, self::MAX_ENTRIES);
        $this->save();
    }

    public function delete(int $index): void
    {
        $this->load();
        if (!isset($this->entries[$index])) {
            return;
        }
        array_splice($this->entries, $index, 1);
        $this->save();
    }

    public function clear(): void
    {
        $this->entries = [];
        $this->loaded = true;

        $file = $this->filePath();
        if (file_exists($file)) {
            unlink($file);
        }
    }

    private function load(): void
    {
        if ($this->loaded) {
            return;
        }
        $this->loaded = true;

        $file = $this->filePath();
        if (!file_exists($file)) {
            $this->entries = [];
            return;
        }

        $contents = file_get_contents($file);
        if ($contents === false) {
            $this->entries = [];
            return;
        }

        /** @var list<array{query: string, response: string, timestamp: int, error?: string}> $data */
        $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        $this->entries = is_array($data) ? $data : [];
    }

    private function save(): void
    {
        $dir = dirname($this->filePath());
        if (!is_dir($dir)) {
            mkdir($dir, 0o755, true);
        }

        file_put_contents(
            $this->filePath(),
            json_encode($this->entries, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            LOCK_EX,
        );
    }

    private function filePath(): string
    {
        return $this->storagePath . '/.llm-history.json';
    }
}
