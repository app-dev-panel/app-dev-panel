<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Collector;

use AppDevPanel\Kernel\Collector\CollectorTrait;
use AppDevPanel\Kernel\Collector\SummaryCollectorInterface;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Collects HTTP request/response data from Symfony's HttpFoundation objects.
 *
 * Unlike the Kernel's RequestCollector (which depends on Yii events),
 * this collector works directly with Symfony's Request/Response objects.
 */
final class SymfonyRequestCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    private string $requestUrl = '';
    private string $requestPath = '';
    private string $requestQuery = '';
    private string $requestMethod = '';
    private bool $requestIsAjax = false;
    private ?string $userIp = null;
    private int $responseStatusCode = 200;
    private ?string $routeName = null;
    private ?string $controllerName = null;
    private array $requestHeaders = [];
    private array $responseHeaders = [];
    private ?string $requestContent = null;
    private ?string $responseContent = null;

    public function __construct(
        private readonly TimelineCollector $timelineCollector,
    ) {}

    public function collectRequest(Request $request): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->requestUrl = $request->getUri();
        $this->requestPath = $request->getPathInfo();
        $this->requestQuery = $request->getQueryString() ?? '';
        $this->requestMethod = $request->getMethod();
        $this->requestIsAjax = $request->isXmlHttpRequest();
        $this->userIp = $request->getClientIp();
        $this->routeName = $request->attributes->get('_route');
        $this->controllerName = $request->attributes->get('_controller');
        $this->requestHeaders = $request->headers->all();
        $this->requestContent = $request->getContent();

        $this->timelineCollector->collect($this, spl_object_id($request));
    }

    public function collectResponse(Response $response): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->responseStatusCode = $response->getStatusCode();
        $this->responseHeaders = $response->headers->all();

        // Only capture response content if it's small enough
        $content = $response->getContent();
        if ($content !== false && mb_strlen($content) <= 65536) {
            $this->responseContent = $content;
        }

        $this->timelineCollector->collect($this, spl_object_id($response));
    }

    public function getCollected(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        return [
            'requestUrl' => $this->requestUrl,
            'requestPath' => $this->requestPath,
            'requestQuery' => $this->requestQuery,
            'requestMethod' => $this->requestMethod,
            'requestIsAjax' => $this->requestIsAjax,
            'userIp' => $this->userIp,
            'responseStatusCode' => $this->responseStatusCode,
            'routeName' => $this->routeName,
            'controllerName' => $this->controllerName,
            'requestHeaders' => $this->requestHeaders,
            'responseHeaders' => $this->responseHeaders,
            'requestContent' => $this->requestContent,
            'responseContent' => $this->responseContent,
        ];
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
                'route' => $this->routeName,
                'controller' => $this->controllerName,
            ],
            'response' => [
                'statusCode' => $this->responseStatusCode,
            ],
        ];
    }

    private function reset(): void
    {
        $this->requestUrl = '';
        $this->requestPath = '';
        $this->requestQuery = '';
        $this->requestMethod = '';
        $this->requestIsAjax = false;
        $this->userIp = null;
        $this->responseStatusCode = 200;
        $this->routeName = null;
        $this->controllerName = null;
        $this->requestHeaders = [];
        $this->responseHeaders = [];
        $this->requestContent = null;
        $this->responseContent = null;
    }
}
