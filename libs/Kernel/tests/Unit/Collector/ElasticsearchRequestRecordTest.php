<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\ElasticsearchRequestRecord;
use PHPUnit\Framework\TestCase;

final class ElasticsearchRequestRecordTest extends TestCase
{
    public function testConstructorAssignsAllProperties(): void
    {
        $record = new ElasticsearchRequestRecord(
            method: 'GET',
            endpoint: '/users/_search',
            body: '{"query":{"match_all":{}}}',
            line: 'Repo.php:10',
            startTime: 100.0,
            endTime: 100.05,
            statusCode: 200,
            responseBody: '{"hits":[]}',
            responseSize: 512,
        );

        $this->assertSame('GET', $record->method);
        $this->assertSame('/users/_search', $record->endpoint);
        $this->assertSame('{"query":{"match_all":{}}}', $record->body);
        $this->assertSame('Repo.php:10', $record->line);
        $this->assertSame(100.0, $record->startTime);
        $this->assertSame(100.05, $record->endTime);
        $this->assertSame(200, $record->statusCode);
        $this->assertSame('{"hits":[]}', $record->responseBody);
        $this->assertSame(512, $record->responseSize);
    }

    public function testDefaultValues(): void
    {
        $record = new ElasticsearchRequestRecord(
            method: 'POST',
            endpoint: '/index/_doc',
            body: '{}',
            line: 'Test.php:1',
            startTime: 0.0,
            endTime: 0.1,
            statusCode: 201,
        );

        $this->assertSame('', $record->responseBody);
        $this->assertSame(0, $record->responseSize);
    }
}
