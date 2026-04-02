<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use AppDevPanel\Kernel\Collector\ElasticsearchCollector;
use AppDevPanel\Kernel\Collector\ElasticsearchRequestRecord;
use yii\base\Action;

final class ElasticsearchAction extends Action
{
    public function run(): array
    {
        /** @var \AppDevPanel\Adapter\Yii2\Module $module */
        $module = \Yii::$app->getModule('app-dev-panel');

        /** @var ElasticsearchCollector|null $elasticsearchCollector */
        $elasticsearchCollector = $module->getCollector(ElasticsearchCollector::class);

        if ($elasticsearchCollector === null) {
            return [
                'fixture' => 'elasticsearch:basic',
                'status' => 'error',
                'message' => 'ElasticsearchCollector not found',
            ];
        }

        // Search request
        $elasticsearchCollector->logRequest(new ElasticsearchRequestRecord(
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
        $elasticsearchCollector->logRequest(new ElasticsearchRequestRecord(
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
        $elasticsearchCollector->logRequest(new ElasticsearchRequestRecord(
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

        return ['fixture' => 'elasticsearch:basic', 'status' => 'ok'];
    }
}
