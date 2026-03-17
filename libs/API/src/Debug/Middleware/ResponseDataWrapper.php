<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Debug\Middleware;

use AppDevPanel\Api\Debug\Exception\NotFoundException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\DataResponse;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;

final class ResponseDataWrapper implements MiddlewareInterface
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private CurrentRoute $currentRoute,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $data = [
            'id' => $this->currentRoute->getArgument('id'),
            'data' => null,
            'error' => null,
            'success' => true,
        ];
        try {
            $response = $handler->handle($request);
            if (!$response instanceof DataResponse) {
                return $response;
            }
            $data['data'] = $response->getData();
            $status = $response->getStatusCode();
            if ($status >= 400) {
                $data['success'] = false;
            }
        } catch (NotFoundException $exception) {
            $data['success'] = false;
            $data['error'] = $exception->getMessage();
            $status = Status::NOT_FOUND;
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
            $status = Status::INTERNAL_SERVER_ERROR;
        }

        return $this->responseFactory->createResponse($data, $status);
    }
}
