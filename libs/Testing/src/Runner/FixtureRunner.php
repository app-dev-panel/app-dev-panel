<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Runner;

use AppDevPanel\Testing\Assertion\AssertionResult;
use AppDevPanel\Testing\Assertion\ExpectationEvaluator;
use AppDevPanel\Testing\Fixture\Fixture;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

final class FixtureRunner
{
    private readonly Client $client;
    private readonly ExpectationEvaluator $evaluator;
    private readonly CollectorDataResolver $collectorResolver;
    private readonly DebugDataFetcher $debugDataFetcher;

    public function __construct(
        private readonly string $baseUrl,
        int $retryDelayMs = 200,
        int $maxRetries = 10,
        int $timeoutSeconds = 30,
    ) {
        $this->client = new Client([
            'base_uri' => rtrim($this->baseUrl, '/'),
            'http_errors' => false,
            'timeout' => $timeoutSeconds,
        ]);
        $this->evaluator = new ExpectationEvaluator();
        $this->collectorResolver = new CollectorDataResolver();
        $this->debugDataFetcher = new DebugDataFetcher($this->client, $retryDelayMs, $maxRetries);
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
        $response = $this->client->request($fixture->method, $fixture->endpoint, $this->buildRequestOptions($fixture));

        $debugId = $this->resolveDebugId($response);
        if ($debugId === null) {
            return FixtureResult::skip($fixture, 'Could not determine debug entry ID');
        }

        $debugData = $this->debugDataFetcher->fetchDebugData($debugId);
        if ($debugData === null) {
            return FixtureResult::skip($fixture, sprintf('Could not fetch debug data for ID: %s', $debugId));
        }

        $assertions = $this->evaluateExpectations($fixture, $debugData);

        return FixtureResult::fromAssertions($fixture, $assertions, $debugId);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRequestOptions(Fixture $fixture): array
    {
        $options = [];
        if ($fixture->headers !== []) {
            $options['headers'] = $fixture->headers;
        }
        if ($fixture->body !== null) {
            $options['body'] = $fixture->body;
        }

        return $options;
    }

    private function resolveDebugId(ResponseInterface $response): ?string
    {
        $debugId = $response->getHeaderLine('X-Debug-Id');

        if ($debugId === '') {
            $debugId = $this->debugDataFetcher->findLatestDebugId();
        }

        return $debugId === null || $debugId === '' ? null : $debugId;
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
            $collectorData = $this->collectorResolver->resolve($debugData, $collectorName);

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
}
