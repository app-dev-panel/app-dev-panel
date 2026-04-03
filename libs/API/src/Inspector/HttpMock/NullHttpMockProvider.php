<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\HttpMock;

final class NullHttpMockProvider implements HttpMockProviderInterface
{
    public function getStatus(): array
    {
        return [
            'running' => false,
            'host' => '0.0.0.0',
            'port' => 8086,
        ];
    }

    public function listExpectations(): array
    {
        return [];
    }

    public function createExpectation(array $expectation): void {}

    public function clearExpectations(): void {}

    public function verifyRequest(array $requestCondition): int
    {
        return 0;
    }

    public function getRequestHistory(): array
    {
        return [];
    }

    public function reset(): void {}
}
