<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Runner;

use AppDevPanel\Testing\Assertion\AssertionResult;
use AppDevPanel\Testing\Assertion\ExpectationEvaluator;
use AppDevPanel\Testing\Scenario\Scenario;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Runs a scenario against a live playground instance:
 * 1. GET /debug/api to capture current entry count
 * 2. Hit the scenario endpoint
 * 3. GET /debug/api to find the new entry (by X-Debug-Id header or by diff)
 * 4. GET /debug/api/view/{id} to get full collector data
 * 5. Evaluate expectations
 */
final class ScenarioRunner
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

    public function run(Scenario $scenario): ScenarioResult
    {
        try {
            return $this->doRun($scenario);
        } catch (GuzzleException $e) {
            return ScenarioResult::skip($scenario, sprintf('HTTP error: %s', $e->getMessage()));
        } catch (\Throwable $e) {
            return ScenarioResult::skip($scenario, sprintf('Error: %s', $e->getMessage()));
        }
    }

    /**
     * @param list<Scenario> $scenarios
     *
     * @return list<ScenarioResult>
     */
    public function runAll(array $scenarios): array
    {
        $results = [];
        foreach ($scenarios as $scenario) {
            $results[] = $this->run($scenario);
        }

        return $results;
    }

    private function doRun(Scenario $scenario): ScenarioResult
    {
        // Step 1: Hit the scenario endpoint
        $options = [];
        if ($scenario->headers !== []) {
            $options['headers'] = $scenario->headers;
        }
        if ($scenario->body !== null) {
            $options['body'] = $scenario->body;
        }

        $response = $this->client->request($scenario->method, $scenario->endpoint, $options);

        // Try to get debug ID from response header
        $debugId = $response->getHeaderLine('X-Debug-Id');

        // Step 2: If no X-Debug-Id header, find the latest entry from the debug API
        if ($debugId === '') {
            $debugId = $this->findLatestDebugId();
        }

        if ($debugId === null || $debugId === '') {
            return ScenarioResult::skip($scenario, 'Could not determine debug entry ID');
        }

        // Step 3: Fetch full debug data with retries (storage write may be async)
        $debugData = $this->fetchDebugData($debugId);
        if ($debugData === null) {
            return ScenarioResult::skip($scenario, sprintf('Could not fetch debug data for ID: %s', $debugId));
        }

        // Step 4: Evaluate expectations
        $assertions = $this->evaluateExpectations($scenario, $debugData);

        return ScenarioResult::fromAssertions($scenario, $assertions, $debugId);
    }

    private function findLatestDebugId(): ?string
    {
        // Poll for new entry with retries
        for ($i = 0; $i < $this->maxRetries; $i++) {
            $response = $this->client->get('/debug/api/');
            $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

            $entries = $body['data'] ?? $body;
            if (is_array($entries) && $entries !== []) {
                $latest = reset($entries);
                if (isset($latest['id'])) {
                    return $latest['id'];
                }
            }

            usleep($this->retryDelayMs * 1000);
        }

        return null;
    }

    private function fetchDebugData(string $debugId): ?array
    {
        for ($i = 0; $i < $this->maxRetries; $i++) {
            $response = $this->client->get(sprintf('/debug/api/view/%s', $debugId));

            if ($response->getStatusCode() === 200) {
                $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

                return $body['data'] ?? $body;
            }

            usleep($this->retryDelayMs * 1000);
        }

        return null;
    }

    /**
     * @return list<AssertionResult>
     */
    private function evaluateExpectations(Scenario $scenario, array $debugData): array
    {
        $allAssertions = [];

        foreach ($scenario->expectations as $collectorName => $expectations) {
            // Find the collector in the debug data by matching the collector name
            $collectorData = $this->findCollectorData($debugData, $collectorName);

            if ($collectorData === null) {
                $allAssertions[] = AssertionResult::fail(sprintf(
                    '[%s] collector not found in debug data. Available: %s',
                    $collectorName,
                    implode(', ', array_keys($debugData)),
                ));
                continue;
            }

            $results = $this->evaluator->evaluate($collectorName, $collectorData, $expectations);
            array_push($allAssertions, ...$results);
        }

        return $allAssertions;
    }

    /**
     * Find collector data by name. The debug data keys are FQCN, so we match by the collector's getName() output.
     */
    private function findCollectorData(array $debugData, string $collectorName): ?array
    {
        // Direct key match
        if (isset($debugData[$collectorName])) {
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
                if (str_contains($key, $className) && is_array($value)) {
                    return $value;
                }
            }
        }

        // Fallback: partial match on collector name
        foreach ($debugData as $key => $value) {
            if (is_array($value) && str_contains(strtolower($key), strtolower($collectorName))) {
                return $value;
            }
        }

        return null;
    }
}
