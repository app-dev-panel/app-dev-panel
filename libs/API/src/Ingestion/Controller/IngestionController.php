<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Ingestion\Controller;

use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Dumper;
use AppDevPanel\Kernel\Storage\StorageInterface;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Json\Json;

final class IngestionController
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private StorageInterface $storage,
    ) {}

    /**
     * Ingest a single debug entry from an external application.
     */
    public function ingest(ServerRequestInterface $request): ResponseInterface
    {
        $body = Json::decode($request->getBody()->getContents());

        if (!isset($body['collectors']) || !is_array($body['collectors'])) {
            throw new InvalidArgumentException('Field "collectors" is required and must be an object.');
        }

        $id = $this->writeEntry($body);

        return $this->responseFactory->createResponse([
            'id' => $id,
            'success' => true,
        ], 201);
    }

    /**
     * Ingest multiple debug entries in a single request.
     */
    public function ingestBatch(ServerRequestInterface $request): ResponseInterface
    {
        $body = Json::decode($request->getBody()->getContents());

        if (!isset($body['entries']) || !is_array($body['entries'])) {
            throw new InvalidArgumentException('Field "entries" is required and must be an array.');
        }

        if (count($body['entries']) > 100) {
            throw new InvalidArgumentException('Maximum 100 entries per batch.');
        }

        $ids = [];
        foreach ($body['entries'] as $entry) {
            if (!isset($entry['collectors']) || !is_array($entry['collectors'])) {
                throw new InvalidArgumentException('Each entry must have a "collectors" field.');
            }
            $ids[] = $this->writeEntry($entry);
        }

        return $this->responseFactory->createResponse([
            'ids' => $ids,
            'count' => count($ids),
        ], 201);
    }

    /**
     * Convenience endpoint: ingest a single log message.
     */
    public function ingestLog(ServerRequestInterface $request): ResponseInterface
    {
        $body = Json::decode($request->getBody()->getContents());

        if (!isset($body['level'], $body['message'])) {
            throw new InvalidArgumentException('Fields "level" and "message" are required.');
        }

        $logEntry = [
            'time' => microtime(true),
            'level' => $body['level'],
            'message' => $body['message'],
            'context' => $body['context'] ?? [],
            'line' => $body['line'] ?? '',
        ];

        $entry = [
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

        $id = $this->writeEntry($entry);

        return $this->responseFactory->createResponse([
            'id' => $id,
            'success' => true,
        ], 201);
    }

    /**
     * Serve the OpenAPI specification.
     */
    public function openapi(): ResponseInterface
    {
        $specPath = dirname(__DIR__, 4) . '/openapi/ingestion.yaml';
        if (!file_exists($specPath)) {
            throw new \RuntimeException('OpenAPI spec not found.');
        }

        $yaml = file_get_contents($specPath);

        // Convert YAML to JSON for easier consumption
        // Simple YAML parsing for this spec structure
        $json = json_encode(yaml_parse($yaml), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return $this->responseFactory->createResponse($json);
    }

    /**
     * Write a single debug entry to storage.
     */
    private function writeEntry(array $entry): string
    {
        $idGenerator = new DebuggerIdGenerator();
        $id = $entry['debugId'] ?? $idGenerator->getId();

        $collectors = $entry['collectors'];
        $context = $entry['context'] ?? [];
        $summaryExtra = $entry['summary'] ?? [];

        $summary = array_merge([
            'id' => $id,
            'collectors' => array_keys($collectors),
            'context' => $context,
        ], $summaryExtra);

        $data = $collectors;

        // Write directly to storage path
        $this->writeToStorage($id, $summary, $data);

        return $id;
    }

    /**
     * Write debug entry files to storage using the same layout as FileStorage.
     */
    private function writeToStorage(string $id, array $summary, array $data): void
    {
        $summaryJson = Json::encode($summary);
        $dataJson = Dumper::create($data)->asJson(30);
        $objectsJson = Dumper::create($data)->asJsonObjectsMap(30);

        // Use StorageInterface::read to determine the base path pattern
        // For now, write via a dedicated method approach:
        // We access the storage path through reflection or use a direct write approach.
        $this->directWrite($id, $summaryJson, $dataJson, $objectsJson);
    }

    /**
     * Write files directly to the storage directory.
     *
     * This bypasses the collector-based flush mechanism to allow external data ingestion.
     */
    private function directWrite(string $id, string $summaryJson, string $dataJson, string $objectsJson): void
    {
        // Determine storage path from existing data or use configured path.
        // We need access to the storage path. For FileStorage, we use reflection.
        $storage = $this->storage;
        $reflection = new \ReflectionClass($storage);

        if (!$reflection->hasProperty('path')) {
            // For non-FileStorage (e.g., MemoryStorage), use a fallback approach
            // by reading current data structure
            throw new \RuntimeException(
                'Direct ingestion requires FileStorage. Current storage does not support direct writes.',
            );
        }

        $pathProp = $reflection->getProperty('path');
        $basePath = $pathProp->getValue($storage) . '/' . date('Y-m-d') . '/' . $id . '/';

        if (!is_dir($basePath)) {
            mkdir($basePath, 0775, true);
        }

        file_put_contents($basePath . StorageInterface::TYPE_SUMMARY . '.json', $summaryJson, LOCK_EX);
        file_put_contents($basePath . StorageInterface::TYPE_DATA . '.json', $dataJson, LOCK_EX);
        file_put_contents($basePath . StorageInterface::TYPE_OBJECTS . '.json', $objectsJson, LOCK_EX);
    }
}
