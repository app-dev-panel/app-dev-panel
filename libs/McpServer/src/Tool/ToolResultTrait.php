<?php

declare(strict_types=1);

namespace AppDevPanel\McpServer\Tool;

trait ToolResultTrait
{
    /**
     * @return array{content: list<array{type: string, text: string}>}
     */
    private static function text(string $text): array
    {
        return ['content' => [['type' => 'text', 'text' => $text]]];
    }

    /**
     * @return array{content: list<array{type: string, text: string}>, isError: true}
     */
    private static function error(string $text): array
    {
        return ['content' => [['type' => 'text', 'text' => $text]], 'isError' => true];
    }
}
