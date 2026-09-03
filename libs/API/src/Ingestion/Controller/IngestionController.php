<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Ingestion\Controller;

use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Api\Ingestion\OpenApiSpecLoader;
use AppDevPanel\Api\Security\DebugIdValidator;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\StorageInterface;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class IngestionController
{
    public function __construct(
        private readonly JsonResponseFactoryInterface $responseFactory,
        private readonly StorageInterface $storage,
    ) {}

    /**
     * Ingest a single debug entry from an external application.
     */
    public function ingest(ServerRequestInterface $request): ResponseInterface
    {
        $body = $this->decodeRequestBody($request);
        $this->requireArrayField($body, 'collectors', 'Field "collectors" is required and must be an object.');

        $id = $this->writeEntry($body);

        return $this->responseFactory->createJsonResponse([
            'id' => $id,
            'success' => true,
        ], 201);
    }

    /**
     * Ingest multiple debug entries in a single request.
     */
    public function ingestBatch(ServerRequestInterface $request): ResponseInterface
    {
        $body = $this->decodeRequestBody($request);
        $this->requireArrayField($body, 'entries', 'Field "entries" is required and must be an array.');

        if (count($body['entries']) > 100) {
            throw new InvalidArgumentException('Maximum 100 entries per batch.');
        }

        $ids = [];
        foreach ($body['entries'] as $entry) {
            $this->requireArrayField($entry, 'collectors', 'Each entry must have a "collectors" field.');
            $ids[] = $this->writeEntry($entry);
        }

        return $this->responseFactory->createJsonResponse([
            'ids' => $ids,
            'count' => count($ids),
        ], 201);
    }

    /**
     * Convenience endpoint: ingest a single log message.
     */
    public function ingestLog(ServerRequestInterface $request): ResponseInterface
    {
        $body = $this->decodeRequestBody($request);
        $id = $this->writeEntry($this->buildLogEntry($body));

        return $this->responseFactory->createJsonResponse([
            'id' => $id,
            'success' => true,
        ], 201);
    }

    /**
     * Serve the OpenAPI specification (decoded document; the factory encodes it exactly once).
     */
    public function openapi(ServerRequestInterface $request): ResponseInterface
    {
        return $this->responseFactory->createJsonResponse(new OpenApiSpecLoader()->load());
    }

    private function decodeRequestBody(ServerRequestInterface $request): array
    {
        return json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function requireArrayField(array $data, string $field, string $message): void
    {
        if (!array_key_exists($field, $data) || !is_array($data[$field])) {
            throw new InvalidArgumentException($message);
        }
    }

    private function buildLogEntry(array $body): array
    {
        if (!array_key_exists('level', $body) || !array_key_exists('message', $body)) {
            throw new InvalidArgumentException('Fields "level" and "message" are required.');
        }

        $logEntry = [
            'time' => microtime(true),
            'level' => $body['level'],
            'message' => $body['message'],
            'context' => $body['context'] ?? [],
            'line' => $body['line'] ?? '',
        ];

        return [
            'collectors' => [
                'logs' => [$logEntry],
            ],
            'context' => [
                'type' => 'generic',
                'service' => $body['service'] ?? 'external',
            ],
            'summary' => [
                'logger' => ['total' => 1],
            ],
        ];
    }

    private function writeEntry(array $entry): string
    {
        $id = array_key_exists('debugId', $entry)
            ? DebugIdValidator::assertValid($entry['debugId'], 'debugId')
            : new DebuggerIdGenerator()->getId();

        /** @var array<string, array> $collectors */
        $collectors = $entry['collectors'];
        /** @var array $context */
        $context = $entry['context'] ?? [];
        /** @var array $summaryExtra */
        $summaryExtra = $entry['summary'] ?? [];

        $summary = array_merge([
            'id' => $id,
            'collectors' => array_map(static fn(string $key) => [
                'id' => $key,
                'name' => $key,
            ], array_keys($collectors)),
            'context' => $context,
        ], $summaryExtra);

        $this->storage->write($id, $summary, $collectors, $collectors);

        return $id;
    }
}
