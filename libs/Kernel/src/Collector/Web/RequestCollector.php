<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector\Web;

use AppDevPanel\Kernel\Collector\CollectorTrait;
use AppDevPanel\Kernel\Collector\SummaryCollectorInterface;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class RequestCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    /**
     * Bodies larger than this are not materialised into `requestRaw` / `responseRaw`.
     */
    public const int DEFAULT_MAX_BODY_SIZE = 1024 * 1024;

    private readonly HttpMessageRenderer $renderer;

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
        int $maxBodySize = self::DEFAULT_MAX_BODY_SIZE,
    ) {
        $this->renderer = new HttpMessageRenderer($maxBodySize);
    }

    public function getCollected(): array
    {
        return [
            'requestUrl' => $this->requestUrl,
            'requestPath' => $this->requestPath,
            'requestQuery' => $this->requestQuery,
            'requestMethod' => $this->requestMethod,
            'requestIsAjax' => $this->requestIsAjax,
            'userIp' => $this->userIp,
            'responseStatusCode' => $this->responseStatusCode,
            'request' => $this->request,
            'requestRaw' => $this->request === null ? null : $this->renderer->render($this->request),
            'response' => $this->response,
            'responseRaw' => $this->response === null ? null : $this->renderer->render($this->response),
        ];
    }

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
        $this->timelineCollector->collect($this, 'request');
    }

    public function collectResponse(ResponseInterface $response): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->response = $response;
        $this->responseStatusCode = $response->getStatusCode();
        $this->timelineCollector->collect($this, 'response');
    }

    public function getSummary(): array
    {
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
        $this->requestPath = '';
        $this->requestQuery = '';
        $this->requestMethod = '';
        $this->requestIsAjax = false;
        $this->userIp = null;
        $this->responseStatusCode = 200;
    }
}
