<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector\Web;

use AppDevPanel\Kernel\Collector\CollectorTrait;
use AppDevPanel\Kernel\Collector\SummaryCollectorInterface;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use GuzzleHttp\Psr7\Message;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class RequestCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    private string $requestUrl = '';
    private string $requestPath = '';
    private string $requestQuery = '';
    private string $requestMethod = '';
    private bool $requestIsAjax = false;
    private ?string $userIp = null;
    private int $responseStatusCode = 200;
    private ?ServerRequestInterface $request = null;
    private ?ResponseInterface $response = null;

    public function __construct(
        private readonly TimelineCollector $timelineCollector,
    ) {}

    public function getCollected(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        $requestRaw = null;
        if ($this->request instanceof ServerRequestInterface) {
            $requestRaw = Message::toString($this->request);
            Message::rewindBody($this->request);
        }

        $responseRaw = null;
        if ($this->response instanceof ResponseInterface) {
            $responseRaw = Message::toString($this->response);
            Message::rewindBody($this->response);
        }

        return [
            'requestUrl' => $this->requestUrl,
            'requestPath' => $this->requestPath,
            'requestQuery' => $this->requestQuery,
            'requestMethod' => $this->requestMethod,
            'requestIsAjax' => $this->requestIsAjax,
            'userIp' => $this->userIp,
            'responseStatusCode' => $this->responseStatusCode,
            'request' => $this->request,
            'requestRaw' => $requestRaw,
            'response' => $this->response,
            'responseRaw' => $responseRaw,
        ];
    }

    /**
     * Collect request data from a PSR-7 request.
     */
    public function collectRequest(ServerRequestInterface $request): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->request = $request;
        $this->requestUrl = (string) $request->getUri();
        $this->requestPath = $request->getUri()->getPath();
        $this->requestQuery = $request->getUri()->getQuery();
        $this->requestMethod = $request->getMethod();
        $this->requestIsAjax = strtolower($request->getHeaderLine('X-Requested-With')) === 'xmlhttprequest';
        $this->userIp = $request->getServerParams()['REMOTE_ADDR'] ?? null;
        $this->timelineCollector->collect($this, 0);
    }

    /**
     * Collect response data from a PSR-7 response.
     */
    public function collectResponse(ResponseInterface $response): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->response = $response;
        $this->responseStatusCode = $response->getStatusCode();
        $this->timelineCollector->collect($this, 1);
    }

    /**
     * Generic event-based collection for backward compatibility.
     * Adapters should prefer collectRequest() and collectResponse().
     */
    public function collect(object $event): void
    {
        if (!$this->isActive()) {
            return;
        }

        if (method_exists($event, 'getRequest')) {
            $request = $event->getRequest();
            if ($request instanceof ServerRequestInterface) {
                $this->collectRequest($request);
            }
        }
        if (method_exists($event, 'getResponse')) {
            $response = $event->getResponse();
            if ($response instanceof ResponseInterface) {
                $this->collectResponse($response);
            }
        }
    }

    public function getSummary(): array
    {
        if (!$this->isActive()) {
            return [];
        }
        return [
            'request' => [
                'url' => $this->requestUrl,
                'path' => $this->requestPath,
                'query' => $this->requestQuery,
                'method' => $this->requestMethod,
                'isAjax' => $this->requestIsAjax,
                'userIp' => $this->userIp,
            ],
            'response' => [
                'statusCode' => $this->responseStatusCode,
            ],
        ];
    }

    private function reset(): void
    {
        $this->request = null;
        $this->response = null;
        $this->requestUrl = '';
        $this->requestMethod = '';
        $this->requestIsAjax = false;
        $this->userIp = null;
        $this->responseStatusCode = 200;
    }
}
