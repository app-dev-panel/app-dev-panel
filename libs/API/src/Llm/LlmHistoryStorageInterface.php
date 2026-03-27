<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Llm;

interface LlmHistoryStorageInterface
{
    /**
     * @return list<array{query: string, response: string, timestamp: int, error?: string}>
     */
    public function getAll(): array;

    /**
     * @param array{query: string, response: string, timestamp: int, error?: string} $entry
     */
    public function add(array $entry): void;

    public function delete(int $index): void;

    public function clear(): void;
}
