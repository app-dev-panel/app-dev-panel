<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Controller;

use Alexkart\CurlBuilder\Command;
use AppDevPanel\Adapter\Yiisoft\Collector\Web\RequestCollector;
use AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Message;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\VarDumper\VarDumper;

final class RequestController
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
    ) {}

    public function request(
        ServerRequestInterface $request,
        CollectorRepositoryInterface $collectorRepository,
    ): ResponseInterface {
        $request = $request->getQueryParams();
        $debugEntryId = $request['debugEntryId'] ?? null;

        $data = $collectorRepository->getDetail($debugEntryId);
        $rawRequest = $data[RequestCollector::class]['requestRaw'];

        $request = Message::parseRequest($rawRequest);

        $client = new Client();
        $response = $client->send($request);

        $result = VarDumper::create($response)->asPrimitives();

        return $this->responseFactory->createResponse($result);
    }

    public function buildCurl(
        ServerRequestInterface $request,
        CollectorRepositoryInterface $collectorRepository,
    ): ResponseInterface {
        $request = $request->getQueryParams();
        $debugEntryId = $request['debugEntryId'] ?? null;

        $data = $collectorRepository->getDetail($debugEntryId);
        $rawRequest = $data[RequestCollector::class]['requestRaw'];

        $request = Message::parseRequest($rawRequest);

        try {
            $output = new Command()
                ->setRequest($request)
                ->build();
        } catch (Throwable $e) {
            return $this->responseFactory->createResponse([
                'command' => null,
                'exception' => (string) $e,
            ]);
        }

        return $this->responseFactory->createResponse([
            'command' => $output,
        ]);
    }
}
