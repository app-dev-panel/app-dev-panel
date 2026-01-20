<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yiisoft\Api\Debug\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\DataResponse;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;
use AppDevPanel\Adapter\Yiisoft\Api\Debug\Exception\NotFoundException;

final class ResponseDataWrapper implements MiddlewareInterface
{
    public function __construct(private DataResponseFactoryInterface $responseFactory, private CurrentRoute $currentRoute)
    {
    }

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
        }

        return $this->responseFactory->createResponse($data, $status);
    }
}
