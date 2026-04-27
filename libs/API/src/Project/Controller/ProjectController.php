<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Project\Controller;

use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Api\ServerSentEventsStream;
use AppDevPanel\Kernel\Project\ProjectConfig;
use AppDevPanel\Kernel\Project\ProjectConfigStorageInterface;
use Closure;
use JsonException;
use Psr\Http\Message\ResponseFactoryInterface;
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
    /** Hold each SSE connection at most this long before letting EventSource reconnect. */
    private const int SSE_DEADLINE_SECONDS = 30;

    /** How often to stat the watched files (in microseconds). */
    private const int SSE_POLL_INTERVAL_MICROS = 1_000_000;

    public function __construct(
        private readonly JsonResponseFactoryInterface $responseFactory,
        private readonly ProjectConfigStorageInterface $storage,
        private readonly ?ResponseFactoryInterface $psrResponseFactory = null,
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

    /**
     * GET /debug/api/project/event-stream — push notifications when the
     * `project.json` or `secrets.json` files change on disk.
     *
     * Catches both `git pull` (file edited externally) and same-tab/other-tab
     * PUT/PATCH (file rewritten by the API). The frontend's
     * `projectSyncMiddleware` reacts by force-refetching `getProjectConfig`
     * and `getSecrets`, which then flow through the existing fulfilled
     * handlers and re-hydrate the slices.
     *
     * Implementation: stat both files every second, emit
     * `{"type":"project-config-changed"}` when the (mtime,size) tuple
     * changes. The stream auto-closes after 30s so EventSource reconnects
     * on its own; this also lets PHP-built-in-server free the worker.
     */
    public function eventStream(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->psrResponseFactory === null) {
            // Adapters that don't pass a PSR-17 ResponseFactoryInterface still
            // get a sensible fallback — return a single 200 with no body so
            // EventSource backs off and retries (instead of crashing).
            return $this->responseFactory->createJsonResponse(['error' => 'SSE not configured.'], 503);
        }

        $configDir = rtrim($this->storage->getConfigDir(), '/\\');
        $files = [$configDir . '/project.json', $configDir . '/secrets.json'];

        $stream = $this->buildEventStream($files);

        return $this->psrResponseFactory
            ->createResponse()
            ->withHeader('Content-Type', 'text/event-stream')
            ->withHeader('Cache-Control', 'no-cache')
            ->withHeader('Connection', 'keep-alive')
            ->withBody(new ServerSentEventsStream(stream: $stream, pollIntervalMicros: self::SSE_POLL_INTERVAL_MICROS));
    }

    /**
     * @param list<string> $files
     */
    private function buildEventStream(array $files): Closure
    {
        $lastHash = $this->hashFiles($files);
        // Emit one event right after connection so the client knows it's listening.
        $primed = false;
        $deadline = time() + self::SSE_DEADLINE_SECONDS;

        return function (array &$buffer) use (&$lastHash, &$primed, &$deadline, $files): bool {
            if (!$primed) {
                $primed = true;
                $buffer[] = json_encode([
                    'type' => 'project-config-stream-ready',
                ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
                return true;
            }

            if (time() > $deadline) {
                return false;
            }

            $hash = $this->hashFiles($files);
            if ($hash !== $lastHash) {
                $lastHash = $hash;
                $buffer[] = json_encode([
                    'type' => 'project-config-changed',
                ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            }

            return true;
        };
    }

    /**
     * @param list<string> $files
     */
    private function hashFiles(array $files): string
    {
        $parts = [];
        foreach ($files as $file) {
            // clearstatcache so we see writes that just landed via the same
            // PHP process (otherwise PHP caches stat() results per-script).
            clearstatcache(true, $file);
            if (!is_file($file)) {
                $parts[] = $file . ':missing';
                continue;
            }
            $stat = @stat($file);
            if ($stat === false) {
                $parts[] = $file . ':missing';
                continue;
            }
            $parts[] = $file . ':' . (string) $stat['mtime'] . ':' . (string) $stat['size'];
        }

        return implode('|', $parts);
    }
}
