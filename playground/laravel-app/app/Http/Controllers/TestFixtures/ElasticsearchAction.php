<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use AppDevPanel\Kernel\Collector\ElasticsearchCollector;
use AppDevPanel\Kernel\Collector\ElasticsearchRequestRecord;
use Illuminate\Http\JsonResponse;

final readonly class ElasticsearchAction
{
    public function __construct(
        private ElasticsearchCollector $elasticsearchCollector,
    ) {}

    public function __invoke(): JsonResponse
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

        return new JsonResponse(['fixture' => 'elasticsearch:basic', 'status' => 'ok']);
    }
}
