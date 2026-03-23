<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Fixture;

final readonly class RequestOptions
{
    /**
     * @param string $method HTTP method
     * @param array<string, string> $headers Request headers
     * @param string|null $body Request body (for POST/PUT)
     */
    public function __construct(
        public string $method = 'GET',
        public array $headers = [],
        public ?string $body = null,
    ) {}
}
