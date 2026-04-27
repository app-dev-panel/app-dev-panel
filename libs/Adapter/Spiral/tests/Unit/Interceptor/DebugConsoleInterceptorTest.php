<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Tests\Unit\Interceptor;

use AppDevPanel\Adapter\Spiral\Interceptor\DebugConsoleInterceptor;
use AppDevPanel\Kernel\Collector\Console\CommandCollector;
use AppDevPanel\Kernel\Collector\Console\ConsoleAppInfoCollector;
use AppDevPanel\Kernel\Collector\ExceptionCollector;
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

final class DebugConsoleInterceptorTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        InterceptorStubsBootstrap::install();
    }

    public function testHappyPathManagesDebuggerLifecycleAndRecordsCommand(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $timeline = new TimelineCollector();
        $commandCollector = new CommandCollector($timeline);
        $appInfo = new ConsoleAppInfoCollector($timeline, 'spiral');
        $exception = new ExceptionCollector($timeline);
        $debugger = new Debugger($idGenerator, $storage, [$timeline, $commandCollector, $appInfo, $exception]);

        $interceptor = new DebugConsoleInterceptor($debugger, $commandCollector, $appInfo, $exception);

        $command = self::namedCommand('app:hello');
        $context = self::context(target: self::targetFor($command), arguments: ['arg' => 'world'], attributes: []);

        $captured = null;
        $handler = self::handler(static function (CallContextInterface $ctx) use (&$captured): int {
            $captured = $ctx;
            return 0;
        });

        $result = $interceptor->intercept($context, $handler);

        self::assertSame(0, $result);
        self::assertSame($context, $captured);

        // Two snapshots collected — one before handler, one after with exitCode.
        $collected = $commandCollector->getCollected();
        self::assertNotSame([], $collected);
        $entry = $collected['command'];
        self::assertSame('app:hello', $entry['name']);
        self::assertSame(0, $entry['exitCode']);

        // Debugger lifecycle ran — startup() generated an id and shutdown() flushed.
        self::assertNotSame('', $idGenerator->getId());
    }

    public function testExceptionPathRecordsErrorAndPropagates(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $timeline = new TimelineCollector();
        $commandCollector = new CommandCollector($timeline);
        $appInfo = new ConsoleAppInfoCollector($timeline, 'spiral');
        $exception = new ExceptionCollector($timeline);
        $debugger = new Debugger($idGenerator, $storage, [$timeline, $commandCollector, $appInfo, $exception]);

        $interceptor = new DebugConsoleInterceptor($debugger, $commandCollector, $appInfo, $exception);

        $command = self::namedCommand('app:fail');
        $context = self::context(target: self::targetFor($command), arguments: [], attributes: []);

        $handler = self::handler(static function (): never {
            throw new RuntimeException('command exploded');
        });

        $threw = null;
        try {
            $interceptor->intercept($context, $handler);
        } catch (RuntimeException $e) {
            $threw = $e;
        }

        self::assertNotNull($threw);
        self::assertSame('command exploded', $threw->getMessage());

        $entry = $commandCollector->getCollected()['command'];
        self::assertSame('app:fail', $entry['name']);
        self::assertSame(1, $entry['exitCode']);
        self::assertSame('command exploded', $entry['error']);

        $exceptions = $exception->getCollected();
        self::assertCount(1, $exceptions);
        self::assertSame(RuntimeException::class, $exceptions[0]['class']);
    }

    public function testFallsBackToTargetPathWhenObjectMissingGetName(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $timeline = new TimelineCollector();
        $commandCollector = new CommandCollector($timeline);
        $appInfo = new ConsoleAppInfoCollector($timeline, 'spiral');
        $exception = new ExceptionCollector($timeline);
        $debugger = new Debugger($idGenerator, $storage, [$timeline, $commandCollector, $appInfo, $exception]);

        $interceptor = new DebugConsoleInterceptor($debugger, $commandCollector, $appInfo, $exception);

        // Target with a path but no object exposing getName().
        $target = self::pathTarget(['App\\Console\\Migrate', 'execute']);
        $context = self::context(target: $target, arguments: [], attributes: []);

        $handler = self::handler(static fn(): int => 0);
        $interceptor->intercept($context, $handler);

        $entry = $commandCollector->getCollected()['command'];
        self::assertSame('App\\Console\\Migrate::execute', $entry['name']);
    }

    public function testUsesAttributeNameWhenTargetHasNoPathOrObject(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $timeline = new TimelineCollector();
        $commandCollector = new CommandCollector($timeline);
        $appInfo = new ConsoleAppInfoCollector($timeline, 'spiral');
        $exception = new ExceptionCollector($timeline);
        $debugger = new Debugger($idGenerator, $storage, [$timeline, $commandCollector, $appInfo, $exception]);

        $interceptor = new DebugConsoleInterceptor($debugger, $commandCollector, $appInfo, $exception);

        $target = self::pathTarget([]);
        $context = self::context(target: $target, arguments: [], attributes: ['command_name' => 'queue:run']);

        $handler = self::handler(static fn(): int => 0);
        $interceptor->intercept($context, $handler);

        $entry = $commandCollector->getCollected()['command'];
        self::assertSame('queue:run', $entry['name']);
    }

    private static function namedCommand(string $name): object
    {
        return new class($name) {
            public function __construct(
                private readonly string $name,
            ) {}

            public function getName(): string
            {
                return $this->name;
            }
        };
    }

    private static function targetFor(object $object): TargetInterface
    {
        return new class($object) implements TargetInterface {
            public function __construct(
                private readonly object $object,
            ) {}

            public function getPath(): array
            {
                return [$this->object::class];
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
                return $this->object;
            }

            public function getCallable(): callable|array|null
            {
                return null;
            }

            public function __toString(): string
            {
                return $this->object::class;
            }
        };
    }

    /** @param list<string> $path */
    private static function pathTarget(array $path): TargetInterface
    {
        return new class($path) implements TargetInterface {
            /** @param list<string> $path */
            public function __construct(
                private readonly array $path,
            ) {}

            public function getPath(): array
            {
                return $this->path;
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
                return implode('::', $this->path);
            }
        };
    }

    /**
     * @param array<int|string, mixed> $arguments
     * @param array<non-empty-string, mixed> $attributes
     */
    private static function context(TargetInterface $target, array $arguments, array $attributes): CallContextInterface
    {
        return new class($target, $arguments, $attributes) implements CallContextInterface {
            /** @param array<int|string, mixed> $arguments */
            /** @param array<non-empty-string, mixed> $attributes */
            public function __construct(
                private TargetInterface $target,
                private array $arguments,
                private array $attributes,
            ) {}

            public function getTarget(): TargetInterface
            {
                return $this->target;
            }

            public function getArguments(): array
            {
                return $this->arguments;
            }

            public function withTarget(TargetInterface $target): static
            {
                $clone = clone $this;
                $clone->target = $target;
                return $clone;
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
