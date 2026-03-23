<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Fixture;

final readonly class Fixture
{
    public string $method;

    /** @var array<string, string> */
    public array $headers;

    public ?string $body;

    /**
     * @param string $name Human-readable fixture name
     * @param string $endpoint HTTP endpoint path (e.g., /test/fixtures/logs)
     * @param array<string, list<Expectation>> $expectations Collector name → list of expectations
     * @param RequestOptions $request HTTP request options (method, headers, body)
     */
    public function __construct(
        public string $name,
        public string $endpoint,
        public array $expectations = [],
        public RequestOptions $request = new RequestOptions(),
    ) {
        $this->method = $this->request->method;
        $this->headers = $this->request->headers;
        $this->body = $this->request->body;
    }
}
