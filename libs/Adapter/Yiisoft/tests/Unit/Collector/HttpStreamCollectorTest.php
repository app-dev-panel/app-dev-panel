<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Tests\Unit\Collector;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Yii\Debug\Collector\CollectorInterface;
use Yiisoft\Yii\Debug\Collector\Stream\HttpStreamCollector;
use Yiisoft\Yii\Debug\Tests\Shared\AbstractCollectorTestCase;

final class HttpStreamCollectorTest extends AbstractCollectorTestCase
{
    /**
     * @param HttpStreamCollector $collector
     */
    protected function collectTestData(CollectorInterface $collector): void
    {
        $collector->collect(
            operation: 'read',
            path: __FILE__,
            args: ['arg1' => 'v1', 'arg2' => 'v2'],
        );
        $collector->collect(
            operation: 'read',
            path: __FILE__,
            args: ['arg3' => 'v3', 'arg4' => 'v4'],
        );
    }

    #[DataProvider('dataSkipCollectOnMatchIgnoreReferences')]
    public function testSkipCollectOnMatchIgnoreReferences(
        string $url,
        callable $before,
        array $ignoredPathPatterns,
        array $ignoredClasses,
        array $ignoredUrls,
        callable $operation,
        callable $after,
        array|callable $assertResult,
    ): void {
        $before($url);

        try {
            $collector = new HttpStreamCollector(
                ignoredPathPatterns: $ignoredPathPatterns,
                ignoredClasses: $ignoredClasses,
                ignoredUrls: $ignoredUrls,
            );
            $collector->startup();

            $operation($url);

            $collected = $collector->getCollected();
            $collector->shutdown();
        } finally {
            $after($url);
        }
        if (is_array($assertResult)) {
            $this->assertSame($assertResult, $collected);
        } else {
            $assertResult($this, $url, $collected);
        }
    }

    public static function dataSkipCollectOnMatchIgnoreReferences(): iterable
    {
        $httpStreamBefore = function (string $url) {
        };
        $httpStreamOperation = static function (string $url) {
            $stream = fopen($url, 'r');
            fread($stream, 4);
            ftell($stream);
            feof($stream);
            fstat($stream);
            fclose($stream);
        };
        $httpStreamAfter = $httpStreamBefore;

        yield 'file stream matched' => [
            $url = 'http://example.com',
            $httpStreamBefore,
            [],
            [],
            [],
            $httpStreamOperation,
            $httpStreamAfter,
            function (TestCase $testCase, string $url, array $collected) {
                $testCase->assertArrayHasKey('read', $collected);
                $testCase->assertIsArray($collected['read']);
                $testCase->assertCount(1, $collected['read']);

                $readItem = $collected['read'][0];
                $testCase->assertSame($url, $readItem['uri']);
                $testCase->assertArrayHasKey('args', $readItem);

                $readItemArgs = $readItem['args'];
                $testCase->assertCount(3, $readItemArgs);

                $testCase->assertSame('GET', $readItemArgs['method']);
                $testCase->assertIsArray($readItemArgs['response_headers']);
                $testCase->assertNotEmpty($readItemArgs['response_headers']);
                $testCase->assertIsArray($readItemArgs['request_headers']);
                $testCase->assertEmpty($readItemArgs['request_headers']);
            },
        ];
        yield 'file stream ignored by path' => [
            $url,
            $httpStreamBefore,
            [basename(__FILE__, '.php')],
            [],
            [],
            $httpStreamOperation,
            $httpStreamAfter,
            [],
        ];
        yield 'file stream ignored by class' => [
            $url,
            $httpStreamBefore,
            [],
            [self::class],
            [],
            $httpStreamOperation,
            $httpStreamAfter,
            [],
        ];
        yield 'file stream ignored by url' => [
            $url,
            $httpStreamBefore,
            [],
            [],
            ['example'],
            $httpStreamOperation,
            $httpStreamAfter,
            [],
        ];
    }

    protected function getCollector(): CollectorInterface
    {
        return new HttpStreamCollector();
    }

    protected function checkCollectedData(array $data): void
    {
        parent::checkCollectedData($data);
        $collected = $data;
        $this->assertCount(1, $collected);

        $this->assertCount(2, $collected['read']);
        $this->assertEquals([
            ['uri' => __FILE__, 'args' => ['arg1' => 'v1', 'arg2' => 'v2']],
            ['uri' => __FILE__, 'args' => ['arg3' => 'v3', 'arg4' => 'v4']],
        ], $collected['read']);
    }

    protected function checkSummaryData(array $data): void
    {
        parent::checkSummaryData($data);
        $this->assertArrayHasKey('http_stream', $data);
        $this->assertEquals(['read' => 2], $data['http_stream']);
    }
}
