<?php

declare(strict_types=1);

namespace AppDevPanel\McpServer\Tool;

interface ToolInterface
{
    /**
     * Unique tool name (e.g., "list_debug_entries").
     */
    public function getName(): string;

    /**
     * Human-readable description for AI clients.
     */
    public function getDescription(): string;

    /**
     * JSON Schema for the tool's input parameters.
     *
     * @return array<string, mixed>
     */
    public function getInputSchema(): array;

    /**
     * Execute the tool and return result content.
     *
     * @param array<string, mixed> $arguments
     *
     * @return array{content: list<array{type: string, text: string}>, isError?: bool}
     */
    public function execute(array $arguments): array;
}
