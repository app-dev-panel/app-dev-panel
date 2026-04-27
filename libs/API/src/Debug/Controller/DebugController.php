<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Debug\Controller;

use AppDevPanel\Api\Debug\Exception\NotFoundException;
use AppDevPanel\Api\Debug\HtmlViewProviderInterface;
use AppDevPanel\Api\Debug\LiveEventStreamFactory;
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
     *
     * If the requested collector implements {@see HtmlViewProviderInterface}, this endpoint
     * renders the collector's PHP view template on the server with the collector's data
     * exposed as `$data` and responds with `{"__html": "..."}` so the panel can embed the
     * rendered fragment as-is.
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

            if (is_string($collectorClass) && is_subclass_of($collectorClass, HtmlViewProviderInterface::class)) {
                $html = $this->renderCollectorView($collectorClass::getViewPath(), is_array($data) ? $data : []);
                return $this->responseFactory->createJsonResponse(['__html' => $html]);
            }
        }

        return $this->responseFactory->createJsonResponse($data);
    }

    /**
     * Render an SSR collector view file with `$data` exposed inside the template.
     * The closure binding hides the controller's `$this` from the template scope.
     *
     * @param array<string, mixed>|list<mixed> $data
     */
    private function renderCollectorView(string $viewPath, array $data): string
    {
        $render = static function (string $__viewPath__, array $data): void {
            require $__viewPath__;
        };

        ob_start();
        try {
            $render($viewPath, $data);
            return (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
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
        [$stream, $close] = LiveEventStreamFactory::create(deadlineSeconds: 30);

        return $this->psrResponseFactory
            ->createResponse()
            ->withHeader('Content-Type', 'text/event-stream')
            ->withHeader('Cache-Control', 'no-cache')
            ->withHeader('Connection', 'keep-alive')
            ->withBody(new ServerSentEventsStream(
                stream: $stream,
                pollIntervalMicros: 0, // No extra sleep — recv timeout handles pacing
                onClose: $close,
            ));
    }
}
