<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Controller;

use Alexkart\CurlBuilder\Command;
use AppDevPanel\Api\Debug\Exception\NotFoundException;
use AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface;
use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Api\Security\DebugIdValidator;
use AppDevPanel\Kernel\Inspector\Primitives;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Message;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class RequestController
{
    private const REQUEST_COLLECTOR = 'AppDevPanel\Kernel\Collector\Web\RequestCollector';

    /**
     * @param string[] $allowedHosts Hosts allowed for request replay. Empty array = all hosts allowed.
     */
    public function __construct(
        private readonly JsonResponseFactoryInterface $responseFactory,
        private readonly CollectorRepositoryInterface $collectorRepository,
        private readonly array $allowedHosts = [],
    ) {}

    public function request(ServerRequestInterface $request): ResponseInterface
    {
        $parsedRequest = $this->loadCapturedRequest($request);

        $this->validateHost($parsedRequest->getUri()->getHost());

        $client = new Client(['timeout' => 15, 'connect_timeout' => 5]);
        $response = $client->send($parsedRequest);

        $result = Primitives::dump($response);

        return $this->responseFactory->createJsonResponse($result);
    }

    public function buildCurl(ServerRequestInterface $request): ResponseInterface
    {
        $parsedRequest = $this->loadCapturedRequest($request);

        try {
            $output = new Command()
                ->setRequest($parsedRequest)
                ->build();
        } catch (Throwable $e) {
            return $this->responseFactory->createJsonResponse([
                'command' => null,
                'exception' => (string) $e,
            ]);
        }

        return $this->responseFactory->createJsonResponse([
            'command' => $output,
        ]);
    }

    /**
     * @throws \AppDevPanel\Api\Debug\Exception\BadRequestException when `debugEntryId` is missing or malformed
     * @throws NotFoundException when the entry has no captured raw request
     */
    private function loadCapturedRequest(ServerRequestInterface $request): RequestInterface
    {
        $queryParams = $request->getQueryParams();
        $debugEntryId = DebugIdValidator::assertValid($queryParams['debugEntryId'] ?? null, 'debugEntryId');

        $data = $this->collectorRepository->getDetail($debugEntryId);
        $rawRequest = $data[self::REQUEST_COLLECTOR]['requestRaw'] ?? null;

        if (!is_string($rawRequest) || $rawRequest === '') {
            throw new NotFoundException(sprintf('Debug entry "%s" has no captured raw HTTP request.', $debugEntryId));
        }

        return Message::parseRequest($rawRequest);
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
