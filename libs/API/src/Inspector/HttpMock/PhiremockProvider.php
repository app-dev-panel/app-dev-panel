<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\HttpMock;

use RuntimeException;

/**
 * Phiremock-based HTTP mock provider.
 *
 * Communicates with a running Phiremock server via its REST API.
 * Does NOT depend on mcustiel/phiremock-client — uses plain HTTP calls instead.
 */
final class PhiremockProvider implements HttpMockProviderInterface
{
    public function __construct(
        private readonly string $host = '127.0.0.1',
        private readonly int $port = 8086,
    ) {}

    public function getStatus(): array
    {
        $running = false;

        try {
            $response = $this->request('GET', '/__phiremock/expectations');
            $running = $response['statusCode'] < 500;
        } catch (RuntimeException $e) {
            // @mago-expect no-empty-catch-clause: Intentionally swallowed — server unreachable means not running
            unset($e);
        }

        return [
            'running' => $running,
            'host' => $this->host,
            'port' => $this->port,
        ];
    }

    public function listExpectations(): array
    {
        $response = $this->request('GET', '/__phiremock/expectations');

        return $response['body'];
    }

    public function createExpectation(array $expectation): void
    {
        $this->request('POST', '/__phiremock/expectations', $expectation);
    }

    public function clearExpectations(): void
    {
        $this->request('DELETE', '/__phiremock/expectations');
    }

    public function verifyRequest(array $requestCondition): int
    {
        $response = $this->request('POST', '/__phiremock/executions', $requestCondition);

        return $response['body']['count'] ?? 0;
    }

    public function getRequestHistory(): array
    {
        $response = $this->request('GET', '/__phiremock/executions');

        return $response['body'] ?? [];
    }

    public function reset(): void
    {
        $this->request('POST', '/__phiremock/reset');
    }

    /**
     * @param array<string, mixed>|null $body
     *
     * @return array{statusCode: int, body: mixed}
     */
    private function request(string $method, string $path, ?array $body = null): array
    {
        $url = sprintf('http://%s:%d%s', $this->host, $this->port, $path);

        $context = [
            'http' => [
                'method' => $method,
                'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
                'timeout' => 5,
                'ignore_errors' => true,
            ],
        ];

        if ($body !== null) {
            $context['http']['content'] = json_encode($body, JSON_THROW_ON_ERROR);
        }

        set_error_handler(static fn(): bool => true);
        try {
            $result = file_get_contents($url, false, stream_context_create($context));
        } finally {
            restore_error_handler();
        }

        if ($result === false) {
            throw new RuntimeException(sprintf(
                'Could not connect to Phiremock server at %s:%d',
                $this->host,
                $this->port,
            ));
        }

        // Extract status code from response headers
        $statusCode = 200;
        $responseHeader = $http_response_header[0] ?? null;
        if ($responseHeader !== null && preg_match('/\d{3}/', $responseHeader, $matches)) {
            $statusCode = (int) $matches[0];
        }

        $decoded = json_decode($result, true);

        return [
            'statusCode' => $statusCode,
            'body' => $decoded ?? [],
        ];
    }
}
