<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Tests\Unit\Collector;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Yiisoft\Yii\Debug\Collector\CollectorInterface;
use Yiisoft\Yii\Debug\Collector\HttpClientCollector;
use Yiisoft\Yii\Debug\Collector\TimelineCollector;
use Yiisoft\Yii\Debug\Tests\Shared\AbstractCollectorTestCase;

final class HttpClientCollectorTest extends AbstractCollectorTestCase
{
    /**
     * @param CollectorInterface|HttpClientCollector $collector
     */
    protected function collectTestData(CollectorInterface $collector): void
    {
        $collector->collect(
            new Request('GET', 'http://example.com'),
            startTime: 10.10,
            line: 'file1:123',
            uniqueId: 'test1',
        );
        $collector->collect(
            new Request('POST', 'http://yiiframework.com'),
            startTime: 12.10,
            line: 'file2:555',
            uniqueId: 'test2'
        );
        $collector->collect(
            new Request('GET', 'http://yiiframework.com'),
            startTime: 15.00,
            line: 'file2:666',
            uniqueId: 'test3'
        );

        $collector->collectTotalTime(
            new Response(200, [], 'test'),
            endTime: 13.10,
            uniqueId: 'test1'
        );
        $collector->collectTotalTime(
            new Response(200, [], 'test'),
            endTime: 12.20,
            uniqueId: 'test2'
        );
        $collector->collectTotalTime(
            new Response(200, [], 'test'),
            endTime: 20.00,
            uniqueId: 'test4'
        );
    }

    protected function getCollector(): CollectorInterface
    {
        return new HttpClientCollector(new TimelineCollector());
    }

    protected function checkCollectedData(array $data): void
    {
        parent::checkCollectedData($data);

        $this->assertCount(3, $data);

        $entry = $data[0];
        $this->assertEquals(10.10, $entry['startTime']);
        $this->assertEquals(13.10, $entry['endTime']);
        $this->assertEquals(3.0, $entry['totalTime']);
        $this->assertEquals('GET', $entry['method']);
        $this->assertEquals('http://example.com', $entry['uri']);
        $this->assertEquals(['Host' => ['example.com']], $entry['headers']);
        $this->assertEquals('file1:123', $entry['line']);

        $entry = $data[1];
        $this->assertEquals(12.10, $entry['startTime']);
        $this->assertEquals(12.20, $entry['endTime']);
        $this->assertEquals(0.1, round($entry['totalTime'], 1));
        $this->assertEquals('POST', $entry['method']);
        $this->assertEquals('http://yiiframework.com', $entry['uri']);
        $this->assertEquals(['Host' => ['yiiframework.com']], $entry['headers']);
        $this->assertEquals('file2:555', $entry['line']);

        $entry = $data[2];
        $this->assertEquals(15.0, $entry['startTime']);
        $this->assertEquals(15.0, $entry['endTime']);
        $this->assertEquals(0.0, round($entry['totalTime'], 1));
        $this->assertEquals('GET', $entry['method']);
        $this->assertEquals('http://yiiframework.com', $entry['uri']);
        $this->assertEquals(['Host' => ['yiiframework.com']], $entry['headers']);
        $this->assertEquals('file2:666', $entry['line']);
    }

    protected function checkSummaryData(array $data): void
    {
        parent::checkSummaryData($data);
        $this->assertCount(1, $data);
        $this->assertArrayHasKey('http', $data);
        $this->assertCount(2, $data['http']);
        $this->assertArrayHasKey('count', $data['http']);
        $this->assertArrayHasKey('totalTime', $data['http']);

        $this->assertEquals(3, $data['http']['count']);
        $this->assertEquals(3.1, round($data['http']['totalTime'], 1));
    }
}
