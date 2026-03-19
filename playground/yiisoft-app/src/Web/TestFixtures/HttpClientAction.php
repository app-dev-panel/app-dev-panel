<?php

declare(strict_types=1);

namespace App\Web\TestFixtures;

use GuzzleHttp\Psr7\MultipartStream;
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

        return $this->responseFactory->createResponse([
            'fixture' => 'http-client:basic',
            'status' => 'ok',
            'results' => $results,
        ]);
    }
}
