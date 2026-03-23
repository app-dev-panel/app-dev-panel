<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Runner;

use GuzzleHttp\Client;

final class DebugDataFetcher
{
    public function __construct(
        private readonly Client $client,
        private readonly int $retryDelayMs,
        private readonly int $maxRetries,
    ) {}

    public function findLatestDebugId(): ?string
    {
        for ($i = 0; $i < $this->maxRetries; $i++) {
            $response = $this->client->get('/debug/api/');
            /** @var array<string, mixed> $body */
            $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

            $entries = $body['data'] ?? $body;
            if (is_array($entries) && $entries !== []) {
                $latest = reset($entries);
                if (is_array($latest) && is_string($latest['id'] ?? null)) {
                    return $latest['id'];
                }
            }

            usleep($this->retryDelayMs * 1000);
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function fetchDebugData(string $debugId): ?array
    {
        for ($i = 0; $i < $this->maxRetries; $i++) {
            $response = $this->client->get(sprintf('/debug/api/view/%s', $debugId));

            if ($response->getStatusCode() === 200) {
                /** @var array<string, mixed> $body */
                $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
                /** @var array<string, mixed> */
                return is_array($body['data'] ?? null) ? $body['data'] : $body;
            }

            usleep($this->retryDelayMs * 1000);
        }

        return null;
    }
}
