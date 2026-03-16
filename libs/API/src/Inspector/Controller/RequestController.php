<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Controller;

use Alexkart\CurlBuilder\Command;
use AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Message;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\VarDumper\VarDumper;

final class RequestController
{
    private const REQUEST_COLLECTOR = 'AppDevPanel\Kernel\Collector\Web\RequestCollector';

    /**
     * @param string[] $allowedHosts Hosts allowed for request replay. Empty array = all hosts allowed.
     */
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private readonly array $allowedHosts = [],
    ) {}

    public function request(
        ServerRequestInterface $request,
        CollectorRepositoryInterface $collectorRepository,
    ): ResponseInterface {
        $queryParams = $request->getQueryParams();
        $debugEntryId = $queryParams['debugEntryId'] ?? null;

        $data = $collectorRepository->getDetail($debugEntryId);
        $rawRequest = $data[self::REQUEST_COLLECTOR]['requestRaw'];

        $parsedRequest = Message::parseRequest($rawRequest);

        $this->validateHost($parsedRequest->getUri()->getHost());

        $client = new Client();
        $response = $client->send($parsedRequest);

        $result = VarDumper::create($response)->asPrimitives();

        return $this->responseFactory->createResponse($result);
    }

    public function buildCurl(
        ServerRequestInterface $request,
        CollectorRepositoryInterface $collectorRepository,
    ): ResponseInterface {
        $queryParams = $request->getQueryParams();
        $debugEntryId = $queryParams['debugEntryId'] ?? null;

        $data = $collectorRepository->getDetail($debugEntryId);
        $rawRequest = $data[self::REQUEST_COLLECTOR]['requestRaw'];

        $parsedRequest = Message::parseRequest($rawRequest);

        try {
            $output = new Command()
                ->setRequest($parsedRequest)
                ->build();
        } catch (Throwable $e) {
            return $this->responseFactory->createResponse([
                'command' => null,
                'exception' => (string) $e,
            ]);
        }

        return $this->responseFactory->createResponse([
            'command' => $output,
        ]);
    }

    private function validateHost(string $host): void
    {
        if ($this->allowedHosts === []) {
            return;
        }

        if (!in_array($host, $this->allowedHosts, true)) {
            throw new InvalidArgumentException(sprintf(
                'Host "%s" is not in the allowed hosts list. Allowed: %s',
                $host,
                implode(', ', $this->allowedHosts),
            ));
        }
    }
}
