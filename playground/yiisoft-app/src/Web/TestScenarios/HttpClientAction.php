<?php

declare(strict_types=1);

namespace App\Web\TestScenarios;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;

final readonly class HttpClientAction implements RequestHandlerInterface
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private ClientInterface $httpClient,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $uri = $request->getUri();
        $baseUrl = sprintf('%s://%s', $uri->getScheme() ?: 'http', $uri->getAuthority());

        $psrRequest = new Request('GET', $baseUrl . '/test/scenarios/request-info');
        $response = $this->httpClient->sendRequest($psrRequest);

        return $this->responseFactory->createResponse([
            'scenario' => 'http-client:basic',
            'status' => 'ok',
            'response_status' => $response->getStatusCode(),
        ]);
    }
}
