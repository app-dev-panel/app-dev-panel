<?php

declare(strict_types=1);

namespace App\actions\testScenarios;

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
        $psrRequest = new Request('GET', $baseUrl . '/test/scenarios/request-info');
        $response = $httpClient->sendRequest($psrRequest);

        return [
            'scenario' => 'http-client:basic',
            'status' => 'ok',
            'response_status' => $response->getStatusCode(),
        ];
    }
}
