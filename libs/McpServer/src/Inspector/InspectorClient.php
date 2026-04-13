<?php

declare(strict_types=1);

namespace AppDevPanel\McpServer\Inspector;

/**
 * HTTP client for ADP Inspector API.
 * Uses file_get_contents with stream context to avoid requiring a PSR-18 client dependency.
 */
class InspectorClient implements InspectorInterface
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly int $timeoutSeconds = 10,
    ) {}

    public static function fromOptionalUrl(?string $url): ?self
    {
        return $url !== null && $url !== '' ? new self($url) : null;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * GET request to an Inspector API endpoint.
     *
     * @param array<string, string> $query Query parameters
     *
     * @return array{success: bool, data: mixed, error: ?string}
     */
    public function get(string $path, array $query = []): array
    {
        $url = $this->buildUrl($path, $query);

        return $this->request('GET', $url);
    }

    /**
     * POST request to an Inspector API endpoint.
     *
     * @return array{success: bool, data: mixed, error: ?string}
     */
    public function post(string $path, array $body = []): array
    {
        $url = $this->buildUrl($path);

        return $this->request('POST', $url, $body);
    }

    private function buildUrl(string $path, array $query = []): string
    {
        $url = rtrim($this->baseUrl, '/') . '/inspect/api' . $path;

        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }

        return $url;
    }

    /**
     * @return array{success: bool, data: mixed, error: ?string}
     */
    private function request(string $method, string $url, ?array $body = null): array
    {
        $options = [
            'http' => [
                'method' => $method,
                'timeout' => $this->timeoutSeconds,
                'ignore_errors' => true,
                'header' => "Accept: application/json\r\n",
            ],
        ];

        if ($body !== null) {
            $json = json_encode($body, JSON_THROW_ON_ERROR);
            $options['http']['content'] = $json;
            $options['http']['header'] .= "Content-Type: application/json\r\n";
            $options['http']['header'] .= sprintf("Content-Length: %d\r\n", strlen($json));
        }

        $context = stream_context_create($options);

        try {
            $response = @file_get_contents($url, false, $context);
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'data' => null,
                'error' => sprintf('Failed to connect to %s: %s', $url, $e->getMessage()),
            ];
        }

        if ($response === false) {
            $lastError = error_get_last();
            $detail = $lastError !== null ? $lastError['message'] : 'unknown error';
            return [
                'success' => false,
                'data' => null,
                'error' => sprintf('Failed to connect to %s: %s', $url, $detail),
            ];
        }

        try {
            $decoded = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return ['success' => false, 'data' => null, 'error' => 'Invalid JSON response from Inspector API'];
        }

        // Inspector API wraps responses in {id, data, error, success}
        if (is_array($decoded) && array_key_exists('data', $decoded)) {
            if (($decoded['success'] ?? false) === false) {
                $error = $decoded['error'] ?? 'Unknown error';
                if (is_array($error)) {
                    $error = $error['message'] ?? json_encode($error, JSON_THROW_ON_ERROR);
                }
                return ['success' => false, 'data' => null, 'error' => (string) $error];
            }

            return ['success' => true, 'data' => $decoded['data'], 'error' => null];
        }

        // Raw response (no wrapper)
        return ['success' => true, 'data' => $decoded, 'error' => null];
    }
}
