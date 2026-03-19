<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Fixture;

/**
 * A single test fixture: hit an endpoint, then verify collectors captured expected data.
 */
final readonly class Fixture
{
    /**
     * @param string $name Human-readable fixture name
     * @param string $endpoint HTTP endpoint path (e.g., /test/fixtures/logs)
     * @param string $method HTTP method
     * @param array<string, list<Expectation>> $expectations Collector name → list of expectations
     * @param array<string, string> $headers Request headers
     * @param string|null $body Request body (for POST/PUT)
     */
    public function __construct(
        public string $name,
        public string $endpoint,
        public string $method = 'GET',
        public array $expectations = [],
        public array $headers = [],
        public ?string $body = null,
    ) {}
}
