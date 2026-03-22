<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Debug\Controller;

use AppDevPanel\Api\Debug\Exception\NotFoundException;
use AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface;
use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Api\ServerSentEventsStream;
use AppDevPanel\Kernel\Storage\StorageInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Debug controller provides endpoints that expose information about requests processed that debugger collected.
 */
final class DebugController
{
    public function __construct(
        private readonly JsonResponseFactoryInterface $responseFactory,
        private readonly CollectorRepositoryInterface $collectorRepository,
        private readonly StorageInterface $storage,
        private readonly ResponseFactoryInterface $psrResponseFactory,
    ) {}

    /**
     * List of requests processed.
     */
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        return $this->responseFactory->createJsonResponse($this->collectorRepository->getSummary());
    }

    /**
     * Summary about a processed request identified by ID specified.
     */
    public function summary(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');
        $data = $this->collectorRepository->getSummary($id);
        return $this->responseFactory->createJsonResponse($data);
    }

    /**
     * Detail information about a processed request identified by ID.
     */
    public function view(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');
        $data = $this->collectorRepository->getDetail($id);

        $collectorClass = $request->getQueryParams()['collector'] ?? null;
        if ($collectorClass !== null) {
            $data = $data[$collectorClass] ?? throw new NotFoundException(sprintf(
                "Requested collector doesn't exist: %s.",
                $collectorClass,
            ));
        }

        return $this->responseFactory->createJsonResponse($data);
    }

    /**
     * Dump information about a processed request identified by ID.
     *
     * @throws NotFoundException
     */
    public function dump(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');
        $data = $this->collectorRepository->getDumpObject($id);

        $collector = $request->getQueryParams()['collector'] ?? null;
        if ($collector !== null) {
            if (array_key_exists($collector, $data)) {
                $data = $data[$collector];
            } else {
                throw new NotFoundException('Requested collector doesn\'t exists.');
            }
        }

        return $this->responseFactory->createJsonResponse($data);
    }

    /**
     * Object information about a processed request identified by ID.
     */
    public function object(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');
        $objectId = $request->getAttribute('objectId');
        $data = $this->collectorRepository->getObject($id, $objectId);

        if (null === $data) {
            throw new NotFoundException('Requested objectId doesn\'t exists.');
        }

        return $this->responseFactory->createJsonResponse([
            'class' => $data[0],
            'value' => $data[1],
        ]);
    }

    public function eventStream(ServerRequestInterface $request): ResponseInterface
    {
        $storage = $this->storage;
        $compareFunction = static function () use ($storage) {
            $read = $storage->read(StorageInterface::TYPE_SUMMARY, null);
            return md5(json_encode($read, JSON_THROW_ON_ERROR));
        };
        $hash = $compareFunction();
        $maxRetries = 30;
        $retries = 0;

        return $this->psrResponseFactory
            ->createResponse()
            ->withHeader('Content-Type', 'text/event-stream')
            ->withHeader('Cache-Control', 'no-cache')
            ->withHeader('Connection', 'keep-alive')
            ->withBody(new ServerSentEventsStream(static function (array &$buffer) use (
                $compareFunction,
                &$hash,
                &$retries,
                $maxRetries,
            ) {
                $newHash = $compareFunction();

                if ($hash !== $newHash) {
                    $response = [
                        'type' => 'debug-updated',
                        'payload' => [],
                    ];

                    $buffer[] = json_encode($response);
                    $hash = $newHash;
                }

                if (connection_aborted()) {
                    return false;
                }
                if ($retries++ > $maxRetries) {
                    return false;
                }

                usleep(500_000);

                return true;
            }));
    }
}
