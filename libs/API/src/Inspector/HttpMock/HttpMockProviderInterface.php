<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\HttpMock;

interface HttpMockProviderInterface
{
    /**
     * @return array{running: bool, host: string, port: int}
     */
    public function getStatus(): array;

    /**
     * @return list<array<string, mixed>>
     */
    public function listExpectations(): array;

    /**
     * @param array<string, mixed> $expectation
     */
    public function createExpectation(array $expectation): void;

    public function clearExpectations(): void;

    /**
     * @param array<string, mixed> $requestCondition
     */
    public function verifyRequest(array $requestCondition): int;

    /**
     * @return list<array<string, mixed>>
     */
    public function getRequestHistory(): array;

    public function reset(): void;
}
