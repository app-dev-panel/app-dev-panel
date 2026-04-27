<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Project\Controller;

use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Kernel\Project\ProjectConfig;
use AppDevPanel\Kernel\Project\ProjectConfigStorageInterface;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Exposes the project-level configuration (frames, OpenAPI specs, ...) that
 * is committed alongside the application source code in `config/adp/project.json`.
 *
 * The frontend keeps its own copy in `localStorage` for an offline-first UX
 * but treats the server response as the source of truth: on load it pulls
 * the latest version, and on every mutation it pushes the full document back.
 */
final class ProjectController
{
    public function __construct(
        private readonly JsonResponseFactoryInterface $responseFactory,
        private readonly ProjectConfigStorageInterface $storage,
    ) {}

    /**
     * GET /debug/api/project/config — return the persisted project config.
     */
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $config = $this->storage->load();

        return $this->responseFactory->createJsonResponse([
            'config' => $config->toArray(),
            'configDir' => $this->storage->getConfigDir(),
        ]);
    }

    /**
     * PUT /debug/api/project/config — overwrite the persisted project config.
     *
     * Accepts either the bare config document (`{frames, openapi, ...}`) or
     * the same shape the GET endpoint returns (`{config: {...}}`); the latter
     * lets the frontend round-trip the response unchanged.
     */
    public function update(ServerRequestInterface $request): ResponseInterface
    {
        try {
            /** @var mixed $payload */
            $payload = json_decode((string) $request->getBody(), true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            return $this->responseFactory->createJsonResponse(['error' => 'Invalid JSON: ' . $e->getMessage()], 400);
        }

        if (!is_array($payload)) {
            return $this->responseFactory->createJsonResponse(['error' => 'Request body must be a JSON object.'], 400);
        }

        $rawConfig = isset($payload['config']) && is_array($payload['config']) ? $payload['config'] : $payload;
        $config = ProjectConfig::fromArray($rawConfig);

        $this->storage->save($config);

        return $this->responseFactory->createJsonResponse([
            'config' => $config->toArray(),
            'configDir' => $this->storage->getConfigDir(),
        ]);
    }
}
