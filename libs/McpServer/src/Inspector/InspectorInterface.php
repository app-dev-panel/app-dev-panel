<?php

declare(strict_types=1);

namespace AppDevPanel\McpServer\Inspector;

/**
 * Contract for HTTP clients that query the ADP Inspector API.
 * Implementations make GET/POST requests to Inspector endpoints and return a normalized result array.
 */
interface InspectorInterface
{
    /**
     * GET request to an Inspector API endpoint.
     *
     * @param array<string, string> $query Query parameters
     *
     * @return array{success: bool, data: mixed, error: ?string}
     */
    public function get(string $path, array $query = []): array;

    /**
     * POST request to an Inspector API endpoint.
     *
     * @param array<string, mixed> $body Request body (will be JSON-encoded)
     *
     * @return array{success: bool, data: mixed, error: ?string}
     */
    public function post(string $path, array $body = []): array;
}
