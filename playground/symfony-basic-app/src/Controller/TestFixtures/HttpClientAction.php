<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/test/fixtures/http-client', name: 'test_http_client', methods: ['GET'])]
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
        $target = $baseUrl . '/test/fixtures/request-info';

        $results = [];

        // GET
        $r = $this->httpClient->sendRequest(new Request('GET', $target));
        $results['GET'] = $r->getStatusCode();

        // POST JSON
        $r = $this->httpClient->sendRequest(
            new Request('POST', $target, ['Content-Type' => 'application/json'], json_encode([
                'action' => 'create',
                'name' => 'test-item',
            ], JSON_THROW_ON_ERROR)),
        );
        $results['POST_JSON'] = $r->getStatusCode();

        // PUT JSON
        $r = $this->httpClient->sendRequest(
            new Request('PUT', $target, ['Content-Type' => 'application/json'], json_encode([
                'action' => 'update',
                'id' => 1,
                'name' => 'updated-item',
            ], JSON_THROW_ON_ERROR)),
        );
        $results['PUT'] = $r->getStatusCode();

        // DELETE
        $r = $this->httpClient->sendRequest(new Request('DELETE', $target));
        $results['DELETE'] = $r->getStatusCode();

        // OPTIONS
        $r = $this->httpClient->sendRequest(new Request('OPTIONS', $target));
        $results['OPTIONS'] = $r->getStatusCode();

        // POST multipart form with text fields + files
        $boundary = 'adp-test-boundary-' . uniqid();
        $multipart = new MultipartStream([
            ['name' => 'username', 'contents' => 'test-user'],
            ['name' => 'email', 'contents' => 'test@example.com'],
            [
                'name' => 'avatar',
                'contents' => 'tiny-png-content',
                'filename' => 'avatar.png',
                'headers' => ['Content-Type' => 'image/png'],
            ],
            [
                'name' => 'document',
                'contents' => 'sample csv data',
                'filename' => 'data.csv',
                'headers' => ['Content-Type' => 'text/csv'],
            ],
        ], $boundary);
        $r = $this->httpClient->sendRequest(
            new Request('POST', $target, ['Content-Type' => 'multipart/form-data; boundary=' . $boundary], $multipart),
        );
        $results['POST_MULTIPART'] = $r->getStatusCode();

        return new JsonResponse([
            'fixture' => 'http-client:basic',
            'status' => 'ok',
            'results' => $results,
        ]);
    }
}
