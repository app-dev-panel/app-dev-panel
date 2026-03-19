<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Controller;

use AppDevPanel\Api\ApiApplication;
use Nyholm\Psr7\Factory\Psr17Factory;
use yii\web\Controller;
use yii\web\Response;

/**
 * Catch-all controller that bridges Yii 2 HTTP into the framework-agnostic ADP ApiApplication.
 *
 * Converts Yii 2 Request -> PSR-7, delegates to ApiApplication, converts PSR-7 Response -> Yii 2 Response.
 * Equivalent to Symfony's AdpApiController.
 */
final class AdpApiController extends Controller
{
    /**
     * Disable CSRF validation for API routes.
     */
    public $enableCsrfValidation = false;

    public function beforeAction($action): bool
    {
        // Add CORS headers
        $response = \Yii::$app->getResponse();
        $response
            ->getHeaders()
            ->set('Access-Control-Allow-Origin', '*')
            ->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
            ->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Debug-Token, X-Requested-With')
            ->set('Access-Control-Max-Age', '86400');

        // Handle OPTIONS preflight
        if (\Yii::$app->getRequest()->getMethod() === 'OPTIONS') {
            $response->setStatusCode(204);
            $response->content = '';
            $response->send();
            \Yii::$app->end();
        }

        return parent::beforeAction($action);
    }

    public function actionHandle(string $path = ''): Response
    {
        /** @var ApiApplication $apiApp */
        $apiApp = \Yii::$container->get(ApiApplication::class);

        $psrRequest = $this->convertYiiRequestToPsr7(\Yii::$app->getRequest());
        $psrResponse = $apiApp->handle($psrRequest);

        return $this->convertPsr7ToYiiResponse($psrResponse);
    }

    private function convertYiiRequestToPsr7(\yii\web\Request $yiiRequest): \Psr\Http\Message\ServerRequestInterface
    {
        $factory = new Psr17Factory();

        $uri = $factory->createUri($yiiRequest->getAbsoluteUrl());
        $psrRequest = $factory->createServerRequest($yiiRequest->getMethod(), $uri, $_SERVER);

        // Copy headers
        foreach ($yiiRequest->getHeaders() as $name => $values) {
            $psrRequest = $psrRequest->withHeader($name, $values);
        }

        // Copy query params
        $psrRequest = $psrRequest->withQueryParams($yiiRequest->getQueryParams());

        // Copy body
        $rawBody = $yiiRequest->getRawBody();
        if ($rawBody !== '') {
            $body = $factory->createStream($rawBody);
            $psrRequest = $psrRequest->withBody($body);
        }

        return $psrRequest;
    }

    private function convertPsr7ToYiiResponse(\Psr\Http\Message\ResponseInterface $psrResponse): Response
    {
        $yiiResponse = \Yii::$app->getResponse();
        $yiiResponse->setStatusCode($psrResponse->getStatusCode());

        // Check if this is an SSE stream
        $contentType = $psrResponse->getHeaderLine('Content-Type');
        if (str_contains($contentType, 'text/event-stream')) {
            return $this->createStreamedResponse($psrResponse, $yiiResponse);
        }

        // Copy headers
        foreach ($psrResponse->getHeaders() as $name => $values) {
            $yiiResponse->getHeaders()->set($name, implode(', ', $values));
        }

        $yiiResponse->content = (string) $psrResponse->getBody();
        $yiiResponse->format = Response::FORMAT_RAW;

        return $yiiResponse;
    }

    private function createStreamedResponse(
        \Psr\Http\Message\ResponseInterface $psrResponse,
        Response $yiiResponse,
    ): Response {
        foreach ($psrResponse->getHeaders() as $name => $values) {
            $yiiResponse->getHeaders()->set($name, implode(', ', $values));
        }

        $body = $psrResponse->getBody();

        // Yii 2's Response::sendContent() calls the stream callable and iterates
        // the return value with foreach. Must return a Generator, not echo directly.
        $yiiResponse->format = Response::FORMAT_RAW;
        $yiiResponse->stream = static function () use ($body): \Generator {
            while (!$body->eof()) {
                yield $body->read(8192);
            }
        };

        return $yiiResponse;
    }
}
