<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Debug\Middleware;

use AppDevPanel\Api\Debug\Exception\NotFoundException;
use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ResponseDataWrapper implements MiddlewareInterface
{
    public function __construct(
        private readonly JsonResponseFactoryInterface $responseFactory,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $data = [
            'id' => $request->getAttribute('id'),
            'data' => null,
            'error' => null,
            'success' => true,
        ];
        try {
            $response = $handler->handle($request);

            // Non-JSON responses (e.g., SSE streams) pass through unwrapped
            $contentType = $response->getHeaderLine('Content-Type');
            if ($contentType !== '' && !str_contains($contentType, 'application/json')) {
                return $response;
            }

            $body = $response->getBody()->getContents();
            $responseData = $body !== '' ? json_decode($body, true, 512, JSON_THROW_ON_ERROR) : null;

            $data['data'] = $responseData;
            $status = $response->getStatusCode();
            if ($status >= 400) {
                $data['success'] = false;
            }
        } catch (NotFoundException $exception) {
            $data['success'] = false;
            $data['error'] = $exception->getMessage();
            $status = 404;
        } catch (\Throwable $exception) {
            $data['success'] = false;
            $data['error'] = $exception->getMessage();
            $data['data'] = [
                'class' => $exception::class,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => array_slice(
                    array_map(static fn(array $frame): string => sprintf(
                        '%s%s%s() at %s:%d',
                        $frame['class'] ?? '',
                        $frame['type'] ?? '',
                        $frame['function'] ?? '',
                        $frame['file'] ?? 'unknown',
                        $frame['line'] ?? 0,
                    ), $exception->getTrace()),
                    0,
                    20,
                ),
            ];
            $status = 500;
        }

        return $this->responseFactory->createJsonResponse($data, $status);
    }
}
