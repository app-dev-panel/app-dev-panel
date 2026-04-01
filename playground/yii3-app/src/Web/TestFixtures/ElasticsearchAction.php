<?php

declare(strict_types=1);

namespace App\Web\TestFixtures;

use AppDevPanel\Kernel\Collector\ElasticsearchCollector;
use AppDevPanel\Kernel\Collector\ElasticsearchRequestRecord;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;

final readonly class ElasticsearchAction implements RequestHandlerInterface
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private ElasticsearchCollector $elasticsearchCollector,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Search request
        $this->elasticsearchCollector->logRequest(new ElasticsearchRequestRecord(
            method: 'GET',
            endpoint: '/users/_search',
            body: '{"query":{"match_all":{}}}',
            line: __FILE__ . ':' . __LINE__,
            startTime: microtime(true),
            endTime: microtime(true) + 0.012,
            statusCode: 200,
            responseBody: '{"hits":{"total":{"value":42},"hits":[]}}',
            responseSize: 256,
        ));

        // Index document
        $this->elasticsearchCollector->logRequest(new ElasticsearchRequestRecord(
            method: 'POST',
            endpoint: '/logs/_doc',
            body: '{"level":"info","message":"test"}',
            line: __FILE__ . ':' . __LINE__,
            startTime: microtime(true),
            endTime: microtime(true) + 0.005,
            statusCode: 201,
            responseBody: '{"_id":"abc123","result":"created"}',
            responseSize: 128,
        ));

        // Delete document
        $this->elasticsearchCollector->logRequest(new ElasticsearchRequestRecord(
            method: 'DELETE',
            endpoint: '/logs/_doc/abc123',
            body: '',
            line: __FILE__ . ':' . __LINE__,
            startTime: microtime(true),
            endTime: microtime(true) + 0.003,
            statusCode: 200,
            responseBody: '{"result":"deleted"}',
            responseSize: 64,
        ));

        return $this->responseFactory->createResponse(['fixture' => 'elasticsearch:basic', 'status' => 'ok']);
    }
}
