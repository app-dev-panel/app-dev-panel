<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Tests\Unit\Collector;

use PHPUnit\Framework\TestCase;
use stdClass;
use Yiisoft\VarDumper\HandlerInterface;
use Yiisoft\Yii\Debug\Collector\TimelineCollector;
use Yiisoft\Yii\Debug\Collector\VarDumperCollector;
use Yiisoft\Yii\Debug\Collector\VarDumperHandlerInterfaceProxy;

final class VarDumperHandlerInterfaceProxyTest extends TestCase
{
    public function testMethodHandle(): void
    {
        $handler = $this->createMock(HandlerInterface::class);
        $handler
            ->expects($this->once())
            ->method('handle');
        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new VarDumperCollector($timeline);
        $collector->startup();
        $proxy = new VarDumperHandlerInterfaceProxy($handler, $collector);

        $proxy->handle(true, 50, true);

        $this->assertEquals([
            [
                'variable' => true,
                'line' => __FILE__ . ':28',
            ],
        ], $collector->getCollected());
        $this->assertEquals([
            'var-dumper' => [
                'total' => 1,
            ],
        ], $collector->getSummary());

        $this->assertCount(1, $timeline->getCollected());

        $event = $timeline->getCollected()[0];
        $this->assertEquals(1, $event[1]);
        $this->assertEquals(VarDumperCollector::class, $event[2]);
        $this->assertEquals([], $event[3]);
    }

    public function testProxyDecoratedCall(): void
    {
        $handler = new class () implements HandlerInterface {
            public $var = null;

            public function getProxiedCall(): string
            {
                return 'ok';
            }

            public function setProxiedCall($args): mixed
            {
                return $args;
            }

            public function handle(mixed $variable, int $depth, bool $highlight = false): void
            {
            }
        };
        $collector = new VarDumperCollector(new TimelineCollector());
        $proxy = new VarDumperHandlerInterfaceProxy($handler, $collector);

        $this->assertEquals('ok', $proxy->getProxiedCall());
        $this->assertEquals($args = [1, new stdClass(), 'string'], $proxy->setProxiedCall($args));
        $proxy->var = '123';
        $this->assertEquals('123', $proxy->var);
    }
}
