<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;
use yii\base\Action;

final class HttpClientAction extends Action
{
    public function run(): array
    {
        /** @var ClientInterface $httpClient */
        $httpClient = \Yii::$container->get(ClientInterface::class);

        $baseUrl = \Yii::$app->request->getHostInfo() ?: 'http://127.0.0.1:8080';
        $target = $baseUrl . '/test/fixtures/request-info';

        $results = [];

        // GET
        $r = $httpClient->sendRequest(new Request('GET', $target));
        $results['GET'] = $r->getStatusCode();

        // POST JSON
        $r = $httpClient->sendRequest(new Request('POST', $target, ['Content-Type' => 'application/json'], json_encode([
            'action' => 'create',
            'name' => 'test-item',
        ], JSON_THROW_ON_ERROR)));
        $results['POST_JSON'] = $r->getStatusCode();

        // PUT JSON
        $r = $httpClient->sendRequest(new Request('PUT', $target, ['Content-Type' => 'application/json'], json_encode([
            'action' => 'update',
            'id' => 1,
            'name' => 'updated-item',
        ], JSON_THROW_ON_ERROR)));
        $results['PUT'] = $r->getStatusCode();

        // DELETE
        $r = $httpClient->sendRequest(new Request('DELETE', $target));
        $results['DELETE'] = $r->getStatusCode();

        // OPTIONS
        $r = $httpClient->sendRequest(new Request('OPTIONS', $target));
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
        $r = $httpClient->sendRequest(
            new Request('POST', $target, ['Content-Type' => 'multipart/form-data; boundary=' . $boundary], $multipart),
        );
        $results['POST_MULTIPART'] = $r->getStatusCode();

        return [
            'fixture' => 'http-client:basic',
            'status' => 'ok',
            'results' => $results,
        ];
    }
}
