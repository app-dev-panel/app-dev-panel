<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Tests\Unit\Container;

use AppDevPanel\Adapter\Spiral\Container\QueueProxyInjector;
use AppDevPanel\Adapter\Spiral\Queue\TracingQueue;
use AppDevPanel\Kernel\Collector\QueueCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;
use Spiral\Core\Container;
use Spiral\Queue\OptionsInterface;
use Spiral\Queue\QueueInterface;

final class QueueProxyInjectorTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        ContainerStubsBootstrap::install();
    }

    public function testProxyDecoratesUnderlyingService(): void
    {
        $container = new Container();
        $binder = $container->getBinder();
        $collector = new QueueCollector(new TimelineCollector());

        $fake = self::recordingQueue('id-123');

        $container->bindSingleton(QueueInterface::class, $fake);

        $injector = new QueueProxyInjector($container, $binder, $collector);
        $injector->setUnderlying($fake);
        $container->bindSingleton(QueueProxyInjector::class, $injector);

        $binder->bindInjector(QueueInterface::class, QueueProxyInjector::class);

        $resolved = $container->get(QueueInterface::class);

        self::assertInstanceOf(TracingQueue::class, $resolved);
        $reflection = new ReflectionProperty(TracingQueue::class, 'inner');
        self::assertSame($fake, $reflection->getValue($resolved));
    }

    public function testCollectorReceivesPushIntercept(): void
    {
        $container = new Container();
        $binder = $container->getBinder();
        $collector = new QueueCollector(new TimelineCollector());
        $collector->startup();

        $fake = self::recordingQueue('id-42');
        $injector = new QueueProxyInjector($container, $binder, $collector);
        $injector->setUnderlying($fake);
        $container->bindSingleton(QueueProxyInjector::class, $injector);

        $binder->bindInjector(QueueInterface::class, QueueProxyInjector::class);

        /** @var QueueInterface $queue */
        $queue = $container->get(QueueInterface::class);
        $id = $queue->push('SendEmail', ['userId' => 7], self::options('emails'));

        self::assertSame('id-42', $id);

        $entries = $collector->getCollected();
        self::assertCount(1, $entries['messages']);
        $entry = $entries['messages'][0];
        self::assertSame('SendEmail', $entry['messageClass']);
        self::assertSame('spiral-queue', $entry['bus']);
        self::assertSame('emails', $entry['transport']);
        self::assertTrue($entry['dispatched']);
        self::assertFalse($entry['failed']);
    }

    public function testCollectorRecordsFailureWhenInnerThrows(): void
    {
        $container = new Container();
        $binder = $container->getBinder();
        $collector = new QueueCollector(new TimelineCollector());
        $collector->startup();

        $fake = new class implements QueueInterface {
            public function push(string $name, array|object $payload = [], ?OptionsInterface $options = null): string
            {
                throw new RuntimeException('boom');
            }
        };

        $injector = new QueueProxyInjector($container, $binder, $collector);
        $injector->setUnderlying($fake);
        $container->bindSingleton(QueueProxyInjector::class, $injector);

        $binder->bindInjector(QueueInterface::class, QueueProxyInjector::class);

        /** @var QueueInterface $queue */
        $queue = $container->get(QueueInterface::class);

        $threw = false;
        try {
            $queue->push('FailingJob', ['x' => 1]);
        } catch (RuntimeException) {
            $threw = true;
        }
        self::assertTrue($threw);

        $entries = $collector->getCollected();
        self::assertCount(1, $entries['messages']);
        $entry = $entries['messages'][0];
        self::assertTrue($entry['failed']);
        self::assertFalse($entry['dispatched']);
        self::assertSame('FailingJob', $entry['messageClass']);
    }

    public function testInactiveCollectorDoesNotRecord(): void
    {
        $container = new Container();
        $binder = $container->getBinder();
        $collector = new QueueCollector(new TimelineCollector());

        $fake = self::recordingQueue('id-9');
        $injector = new QueueProxyInjector($container, $binder, $collector);
        $injector->setUnderlying($fake);
        $container->bindSingleton(QueueProxyInjector::class, $injector);

        $binder->bindInjector(QueueInterface::class, QueueProxyInjector::class);

        /** @var QueueInterface $queue */
        $queue = $container->get(QueueInterface::class);
        $queue->push('Inactive', []);

        $entries = $collector->getCollected();
        self::assertSame([], $entries['messages']);
    }

    private static function recordingQueue(string $returnedId): QueueInterface
    {
        return new class($returnedId) implements QueueInterface {
            /** @var list<array{name: string, payload: mixed, options: ?OptionsInterface}> */
            public array $pushed = [];

            public function __construct(
                private readonly string $id,
            ) {}

            public function push(string $name, array|object $payload = [], ?OptionsInterface $options = null): string
            {
                $this->pushed[] = ['name' => $name, 'payload' => $payload, 'options' => $options];
                return $this->id;
            }
        };
    }

    private static function options(string $queue): OptionsInterface
    {
        return new class($queue) implements OptionsInterface {
            public function __construct(
                private readonly string $queue,
            ) {}

            public function getQueue(): ?string
            {
                return $this->queue;
            }
        };
    }
}
