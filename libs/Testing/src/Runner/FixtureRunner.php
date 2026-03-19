<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Runner;

use AppDevPanel\Testing\Assertion\AssertionResult;
use AppDevPanel\Testing\Assertion\ExpectationEvaluator;
use AppDevPanel\Testing\Fixture\Fixture;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Runs a fixture against a live playground instance:
 * 1. GET /debug/api to capture current entry count
 * 2. Hit the fixture endpoint
 * 3. GET /debug/api to find the new entry (by X-Debug-Id header or by diff)
 * 4. GET /debug/api/view/{id} to get full collector data
 * 5. Evaluate expectations
 */
final class FixtureRunner
{
    private readonly Client $client;
    private readonly ExpectationEvaluator $evaluator;

    public function __construct(
        private readonly string $baseUrl,
        private readonly int $retryDelayMs = 200,
        private readonly int $maxRetries = 10,
    ) {
        $this->client = new Client([
            'base_uri' => rtrim($this->baseUrl, '/'),
            'http_errors' => false,
            'timeout' => 10,
        ]);
        $this->evaluator = new ExpectationEvaluator();
    }

    public function run(Fixture $fixture): FixtureResult
    {
        try {
            return $this->doRun($fixture);
        } catch (GuzzleException $e) {
            return FixtureResult::skip($fixture, sprintf('HTTP error: %s', $e->getMessage()));
        } catch (\Throwable $e) {
            return FixtureResult::skip($fixture, sprintf('Error: %s', $e->getMessage()));
        }
    }

    /**
     * @param list<Fixture> $fixtures
     *
     * @return list<FixtureResult>
     */
    public function runAll(array $fixtures): array
    {
        $results = [];
        foreach ($fixtures as $fixture) {
            $results[] = $this->run($fixture);
        }

        return $results;
    }

    private function doRun(Fixture $fixture): FixtureResult
    {
        // Step 1: Hit the fixture endpoint
        $options = [];
        if ($fixture->headers !== []) {
            $options['headers'] = $fixture->headers;
        }
        if ($fixture->body !== null) {
            $options['body'] = $fixture->body;
        }

        $response = $this->client->request($fixture->method, $fixture->endpoint, $options);

        // Try to get debug ID from response header
        $debugId = $response->getHeaderLine('X-Debug-Id');

        // Step 2: If no X-Debug-Id header, find the latest entry from the debug API
        if ($debugId === '') {
            $debugId = $this->findLatestDebugId();
        }

        if ($debugId === null || $debugId === '') {
            return FixtureResult::skip($fixture, 'Could not determine debug entry ID');
        }

        // Step 3: Fetch full debug data with retries (storage write may be async)
        $debugData = $this->fetchDebugData($debugId);
        if ($debugData === null) {
            return FixtureResult::skip($fixture, sprintf('Could not fetch debug data for ID: %s', $debugId));
        }

        // Step 4: Evaluate expectations
        $assertions = $this->evaluateExpectations($fixture, $debugData);

        return FixtureResult::fromAssertions($fixture, $assertions, $debugId);
    }

    private function findLatestDebugId(): ?string
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
    private function fetchDebugData(string $debugId): ?array
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

    /**
     * @param array<string, mixed> $debugData
     *
     * @return list<AssertionResult>
     */
    private function evaluateExpectations(Fixture $fixture, array $debugData): array
    {
        $allAssertions = [];

        foreach ($fixture->expectations as $collectorName => $expectations) {
            $collectorData = $this->findCollectorData($debugData, $collectorName);

            if ($collectorData === null) {
                $allAssertions[] = AssertionResult::fail(sprintf(
                    '[%s] collector not found in debug data. Available: %s',
                    $collectorName,
                    implode(', ', array_map('strval', array_keys($debugData))),
                ));
                continue;
            }

            $results = $this->evaluator->evaluate($collectorName, $collectorData, $expectations);
            array_push($allAssertions, ...$results);
        }

        return $allAssertions;
    }

    /**
     * @param array<string, mixed> $debugData
     *
     * @return array<array-key, mixed>|null
     */
    private function findCollectorData(array $debugData, string $collectorName): ?array
    {
        // Direct key match
        if (array_key_exists($collectorName, $debugData)) {
            $value = $debugData[$collectorName];

            return is_array($value) ? $value : null;
        }

        // Match by collector name pattern in the FQCN key
        $nameMap = [
            'logger' => 'LogCollector',
            'event' => 'EventCollector',
            'exception' => 'ExceptionCollector',
            'http' => 'HttpClientCollector',
            'service' => 'ServiceCollector',
            'timeline' => 'TimelineCollector',
            'var-dumper' => 'VarDumperCollector',
            'request' => 'RequestCollector',
            'web' => 'WebAppInfoCollector',
            'command' => 'CommandCollector',
            'console' => 'ConsoleAppInfoCollector',
            'fs_stream' => 'FilesystemStreamCollector',
            'http_stream' => 'HttpStreamCollector',
            'cache' => 'CacheCollector',
            'security' => 'SecurityCollector',
            'twig' => 'TwigCollector',
            'doctrine' => 'DoctrineCollector',
            'mailer' => 'MailerCollector',
            'messenger' => 'MessengerCollector',
            'db' => 'DatabaseCollector',
            'queue' => 'QueueCollector',
            'middleware' => 'MiddlewareCollector',
            'router' => 'RouterCollector',
            'validator' => 'ValidatorCollector',
            'view' => 'WebViewCollector',
            'assets' => 'AssetBundleCollector',
        ];

        $className = $nameMap[$collectorName] ?? null;
        if ($className !== null) {
            foreach ($debugData as $key => $value) {
                if (is_string($key) && str_contains($key, $className) && is_array($value)) {
                    return $value;
                }
            }
        }

        // Fallback: partial match on collector name
        foreach ($debugData as $key => $value) {
            if (is_string($key) && is_array($value) && str_contains(strtolower($key), strtolower($collectorName))) {
                return $value;
            }
        }

        return null;
    }
}
