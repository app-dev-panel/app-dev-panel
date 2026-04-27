<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Tests\Unit\Interceptor;

use AppDevPanel\Adapter\Spiral\Interceptor\DebugQueueInterceptor;
use AppDevPanel\Kernel\Collector\QueueCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\MemoryStorage;
use PHPUnit\Framework\TestCase;
use ReflectionFunctionAbstract;
use RuntimeException;
use Spiral\Interceptors\Context\CallContextInterface;
use Spiral\Interceptors\Context\TargetInterface;
use Spiral\Interceptors\HandlerInterface;

final class DebugQueueInterceptorTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        InterceptorStubsBootstrap::install();
    }

    public function testHandledJobIsRecordedAsHandledMessage(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $timeline = new TimelineCollector();
        $collector = new QueueCollector($timeline);
        $debugger = new Debugger($idGenerator, $storage, [$timeline, $collector]);

        $payload = new class {
            public string $email = 'a@b.test';
        };

        $context = self::context(arguments: ['payload' => $payload], attributes: ['name' => 'emails']);

        $captured = null;
        $handler = self::handler(static function (CallContextInterface $ctx) use (&$captured): bool {
            $captured = $ctx;
            return true;
        });

        $interceptor = new DebugQueueInterceptor($debugger, $collector);
        $result = $interceptor->intercept($context, $handler);

        self::assertTrue($result);
        self::assertSame($context, $captured);

        $entries = $collector->getCollected();
        self::assertCount(1, $entries['messages']);
        $entry = $entries['messages'][0];

        self::assertSame($payload::class, $entry['messageClass']);
        self::assertSame('spiral-queue', $entry['bus']);
        self::assertSame('emails', $entry['transport']);
        self::assertTrue($entry['handled']);
        self::assertFalse($entry['failed']);
        self::assertTrue($entry['dispatched']);

        // collectWorkerProcessing() ran — queueName + payload tracked.
        self::assertNotSame([], $entries['processingMessages']);
    }

    public function testFailedJobIsRecordedAndExceptionPropagates(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $timeline = new TimelineCollector();
        $collector = new QueueCollector($timeline);
        $debugger = new Debugger($idGenerator, $storage, [$timeline, $collector]);

        $payload = new class {};

        $context = self::context(arguments: ['payload' => $payload], attributes: ['name' => 'critical']);

        $handler = self::handler(static function (): never {
            throw new RuntimeException('handler boom');
        });

        $interceptor = new DebugQueueInterceptor($debugger, $collector);

        $caught = null;
        try {
            $interceptor->intercept($context, $handler);
        } catch (RuntimeException $e) {
            $caught = $e;
        }

        self::assertNotNull($caught);
        self::assertSame('handler boom', $caught->getMessage());

        $entries = $collector->getCollected();
        self::assertCount(1, $entries['messages']);
        $entry = $entries['messages'][0];

        self::assertSame($payload::class, $entry['messageClass']);
        self::assertSame('critical', $entry['transport']);
        self::assertFalse($entry['handled']);
        self::assertTrue($entry['failed']);
    }

    public function testFallsBackToUnknownMessageClassWhenNoPayload(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $timeline = new TimelineCollector();
        $collector = new QueueCollector($timeline);
        $debugger = new Debugger($idGenerator, $storage, [$timeline, $collector]);

        $context = self::context(arguments: ['name' => 'BackgroundJob'], attributes: []);

        $handler = self::handler(static fn(): null => null);

        $interceptor = new DebugQueueInterceptor($debugger, $collector);
        $interceptor->intercept($context, $handler);

        $entry = $collector->getCollected()['messages'][0];
        self::assertSame('unknown', $entry['messageClass']);
        // name fell through from the arguments array since the attribute was absent.
        self::assertSame('BackgroundJob', $entry['transport']);
    }

    /**
     * @param array<int|string, mixed> $arguments
     * @param array<non-empty-string, mixed> $attributes
     */
    private static function context(array $arguments, array $attributes): CallContextInterface
    {
        return new class($arguments, $attributes) implements CallContextInterface {
            /** @param array<int|string, mixed> $arguments */
            /** @param array<non-empty-string, mixed> $attributes */
            public function __construct(
                private array $arguments,
                private array $attributes,
            ) {}

            public function getTarget(): TargetInterface
            {
                return new class implements TargetInterface {
                    public function getPath(): array
                    {
                        return [];
                    }

                    public function withPath(array $path, ?string $delimiter = null): static
                    {
                        return $this;
                    }

                    public function getReflection(): ?ReflectionFunctionAbstract
                    {
                        return null;
                    }

                    public function getObject(): ?object
                    {
                        return null;
                    }

                    public function getCallable(): callable|array|null
                    {
                        return null;
                    }

                    public function __toString(): string
                    {
                        return '';
                    }
                };
            }

            public function getArguments(): array
            {
                return $this->arguments;
            }

            public function withTarget(TargetInterface $target): static
            {
                return $this;
            }

            public function withArguments(array $arguments): static
            {
                $clone = clone $this;
                $clone->arguments = $arguments;
                return $clone;
            }

            public function getAttributes(): array
            {
                return $this->attributes;
            }

            public function getAttribute(string $name, mixed $default = null): mixed
            {
                return $this->attributes[$name] ?? $default;
            }

            public function withAttribute(string $name, mixed $value): static
            {
                $clone = clone $this;
                $clone->attributes[$name] = $value;
                return $clone;
            }

            public function withoutAttribute(string $name): static
            {
                $clone = clone $this;
                unset($clone->attributes[$name]);
                return $clone;
            }
        };
    }

    /**
     * @param callable(CallContextInterface): mixed $callback
     */
    private static function handler(callable $callback): HandlerInterface
    {
        return new class($callback) implements HandlerInterface {
            /** @var callable(CallContextInterface): mixed */
            private $callback;

            public function __construct(callable $callback)
            {
                $this->callback = $callback;
            }

            public function handle(CallContextInterface $context): mixed
            {
                return ($this->callback)($context);
            }
        };
    }
}
