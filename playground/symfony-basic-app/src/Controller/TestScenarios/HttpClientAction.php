<?php

declare(strict_types=1);

namespace App\Controller\TestScenarios;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/test/scenarios/http-client', name: 'test_http_client', methods: ['GET'])]
final readonly class HttpClientAction
{
    public function __construct(
        private ClientInterface $httpClient,
        private RequestStack $requestStack,
    ) {}

    public function __invoke(): JsonResponse
    {
        $currentRequest = $this->requestStack->getCurrentRequest();
        $baseUrl = $currentRequest !== null ? $currentRequest->getSchemeAndHttpHost() : 'http://127.0.0.1:8080';

        $psrRequest = new Request('GET', $baseUrl . '/test/scenarios/request-info');
        $response = $this->httpClient->sendRequest($psrRequest);

        return new JsonResponse([
            'scenario' => 'http-client:basic',
            'status' => 'ok',
            'response_status' => $response->getStatusCode(),
        ]);
    }
}
