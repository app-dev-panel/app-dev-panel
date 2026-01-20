<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Collector\Web;

use GuzzleHttp\Psr7\Message;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\Debug\Collector\CollectorTrait;
use Yiisoft\Yii\Debug\Collector\SummaryCollectorInterface;
use Yiisoft\Yii\Debug\Collector\TimelineCollector;
use Yiisoft\Yii\Http\Event\AfterRequest;
use Yiisoft\Yii\Http\Event\BeforeRequest;

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
        private readonly TimelineCollector $timelineCollector
    ) {
    }

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

    public function collect(object $event): void
    {
        if (!$this->isActive()) {
            return;
        }

        if ($event instanceof BeforeRequest) {
            $request = $event->getRequest();

            $this->request = $request;
            $this->requestUrl = (string) $request->getUri();
            $this->requestPath = $request->getUri()->getPath();
            $this->requestQuery = $request->getUri()->getQuery();
            $this->requestMethod = $request->getMethod();
            $this->requestIsAjax = strtolower($request->getHeaderLine('X-Requested-With')) === 'xmlhttprequest';
            $this->userIp = $request->getServerParams()['REMOTE_ADDR'] ?? null;
        }
        if ($event instanceof AfterRequest) {
            $response = $event->getResponse();

            $this->response = $response;
            $this->responseStatusCode = $response !== null ? $response->getStatusCode() : 500;
        }
        $this->timelineCollector->collect($this, spl_object_id($event));
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
