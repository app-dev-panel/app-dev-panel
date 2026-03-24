<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Ingestion\Controller;

use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\StorageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Handles Spatie Ray debug tool protocol.
 *
 * Ray sends debug data as JSON payloads. This controller accepts them
 * and maps the data to the ADP var-dumper collector format.
 */
final class RayController
{
    public function __construct(
        private readonly JsonResponseFactoryInterface $responseFactory,
        private readonly StorageInterface $storage,
    ) {}

    /**
     * Ray availability check — must return a non-200 response to signal the server is ready.
     */
    public function availability(ServerRequestInterface $request): ResponseInterface
    {
        return $this->responseFactory->createJsonResponse(['active' => true], 400);
    }

    /**
     * Accept Ray event payloads and store as var-dumper data.
     */
    public function event(ServerRequestInterface $request): ResponseInterface
    {
        $body = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $payloads = $body['payloads'] ?? [];
        if ($payloads === []) {
            return $this->responseFactory->createJsonResponse(['success' => true]);
        }

        $uuid = $body['uuid'] ?? null;
        $dumps = [];

        foreach ($payloads as $payload) {
            $type = $payload['type'] ?? 'log';
            $content = $payload['content'] ?? [];
            $origin = $payload['origin'] ?? [];

            $variable = $this->extractVariable($type, $content);
            $line = $this->extractLine($origin);
            $label = $content['label'] ?? $type;

            $dumps[] = [
                'variable' => $variable,
                'line' => $line,
                'label' => $label,
                'ray_type' => $type,
                'ray_color' => $content['color'] ?? null,
            ];
        }

        $idGenerator = new DebuggerIdGenerator();
        $id = $uuid ?? $idGenerator->getId();

        $collectors = [
            'var-dumper' => $dumps,
        ];
        $summary = [
            'id' => $id,
            'collectors' => array_map(
                static fn(string $key) => ['id' => $key, 'name' => $key],
                array_keys($collectors),
            ),
            'context' => [
                'type' => 'generic',
                'service' => 'ray',
            ],
            'var-dumper' => ['total' => count($dumps)],
        ];

        $this->storage->write($id, $summary, $collectors, $collectors);

        return $this->responseFactory->createJsonResponse([
            'id' => $id,
            'success' => true,
        ]);
    }

    /**
     * Handle lock check — Ray uses locks to pause execution.
     */
    public function lockStatus(ServerRequestInterface $request): ResponseInterface
    {
        return $this->responseFactory->createJsonResponse([
            'active' => false,
            'stop_execution' => false,
        ]);
    }

    private function extractVariable(string $type, array $content): mixed
    {
        return match ($type) {
            'log' => $content['values'] ?? $content['value'] ?? null,
            'custom' => $content['content'] ?? null,
            'table' => $content['values'] ?? null,
            'json_string' => $content['value'] ?? null,
            'carbon' => $content['formatted'] ?? $content['timestamp'] ?? null,
            'exception' => $content['class'] . ': ' . ($content['message'] ?? ''),
            'trace' => $content['frames'] ?? null,
            'caller' => $content['frame'] ?? null,
            default => $content,
        };
    }

    private function extractLine(array $origin): string
    {
        if ($origin === []) {
            return '';
        }

        $file = $origin['file'] ?? '';
        $lineNumber = $origin['line_number'] ?? '';

        if ($file === '') {
            return '';
        }

        return $lineNumber !== '' ? "{$file}:{$lineNumber}" : $file;
    }
}
