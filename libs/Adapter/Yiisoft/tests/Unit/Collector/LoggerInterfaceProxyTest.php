<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Tests\Unit\Collector;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use stdClass;
use Yiisoft\Yii\Debug\Collector\LogCollector;
use Yiisoft\Yii\Debug\Collector\LoggerInterfaceProxy;

final class LoggerInterfaceProxyTest extends TestCase
{
    #[DataProvider('logMethodsProvider')]
    public function testLogMethods(string $method, string $level, string $message, array $context): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method($method);
        $collector = $this->createMock(LogCollector::class);
        $collector
            ->expects($this->once())
            ->method('collect')
            ->with($level, $message, $context, __FILE__ . ':32');
        $proxy = new LoggerInterfaceProxy($logger, $collector);

        $proxy->$method($message, $context);
    }

    #[DataProvider('logMethodsProvider')]
    public function testMethodLog($method, string $level, string $message, array $context): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('log');
        $collector = $this->createMock(LogCollector::class);
        $collector
            ->expects($this->once())
            ->method('collect')
            ->with($level, $message, $context, __FILE__ . ':49');
        $proxy = new LoggerInterfaceProxy($logger, $collector);

        $proxy->log($level, $message, $context);
    }

    public static function logMethodsProvider(): iterable
    {
        yield 'alert' => ['alert', LogLevel::ALERT, 'message', []];
        yield 'critical' => ['critical', LogLevel::CRITICAL, 'message', []];
        yield 'debug' => ['debug', LogLevel::DEBUG, 'message', []];
        yield 'emergency' => ['emergency', LogLevel::EMERGENCY, 'message', []];
        yield 'notice' => ['notice', LogLevel::NOTICE, 'message', []];
        yield 'error' => ['error', LogLevel::ERROR, 'message', ['context']];
        yield 'info' => ['info', LogLevel::INFO, 'message', ['context']];
        yield 'warning' => ['warning', LogLevel::WARNING, 'message', ['context']];
    }

    public function testProxyDecoratedCall(): void
    {
        $logger = new class () implements LoggerInterface {
            use LoggerTrait;

            public $var = null;

            public function getProxiedCall(): string
            {
                return 'ok';
            }

            public function setProxiedCall($args): mixed
            {
                return $args;
            }

            public function log($level, \Stringable|string $message, array $context = []): void
            {
            }
        };
        $collector = $this->createMock(LogCollector::class);
        $proxy = new LoggerInterfaceProxy($logger, $collector);

        $this->assertEquals('ok', $proxy->getProxiedCall());
        $this->assertEquals($args = [1, new stdClass(), 'string'], $proxy->setProxiedCall($args));
        $proxy->var = '123';
        $this->assertEquals('123', $proxy->var);
    }
}
