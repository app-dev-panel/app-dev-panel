<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii3\Tests\Unit\Inspector;

use AppDevPanel\Adapter\Yii3\Inspector\Yii3ConfigProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Config\ConfigInterface;

final class Yii3ConfigProviderTest extends TestCase
{
    public function testGetDelegatesNonEventGroupsToUnderlyingConfig(): void
    {
        $provider = new Yii3ConfigProvider(
            $this->configWith([
                'params' => ['foo' => 'bar'],
                'di' => ['service' => 'stdClass'],
            ]),
        );

        $this->assertSame(['foo' => 'bar'], $provider->get('params'));
        $this->assertSame(['service' => 'stdClass'], $provider->get('di'));
    }

    public function testGetEventsWebReturnsEmptyListWhenConfigThrows(): void
    {
        $provider = new Yii3ConfigProvider($this->throwingConfig());

        $this->assertSame([], $provider->get('events-web'));
        $this->assertSame([], $provider->get('events'));
        $this->assertSame([], $provider->get('events-console'));
    }

    public function testGetEventsWebNormalizesRawMapIntoStructuredEntries(): void
    {
        $provider = new Yii3ConfigProvider($this->configWith([
            'events-web' => [
                'app.event' => ['HandlerClass::handle'],
            ],
        ]));

        $result = $provider->get('events-web');

        $this->assertCount(1, $result);
        $this->assertSame('app.event', $result[0]['name']);
        $this->assertNull($result[0]['class']);
        $this->assertSame(['HandlerClass::handle'], $result[0]['listeners']);
    }

    public function testGetEventsPreservesClassNameWhenEventNameIsFqcn(): void
    {
        $provider = new Yii3ConfigProvider($this->configWith([
            'events-web' => [
                self::class => ['listener'],
            ],
        ]));

        $result = $provider->get('events-web');

        $this->assertCount(1, $result);
        $this->assertSame(self::class, $result[0]['name']);
        $this->assertSame(self::class, $result[0]['class']);
    }

    public function testGetEventsSortsEntriesByName(): void
    {
        $provider = new Yii3ConfigProvider($this->configWith([
            'events-web' => [
                'z.event' => ['handler'],
                'a.event' => ['handler'],
            ],
        ]));

        $result = $provider->get('events-web');

        $this->assertSame('a.event', $result[0]['name']);
        $this->assertSame('z.event', $result[1]['name']);
    }

    public function testDescribeListenerHandlesStringCallable(): void
    {
        $provider = new Yii3ConfigProvider($this->configWith([
            'events-web' => ['app.event' => ['Foo::bar']],
        ]));

        $result = $provider->get('events-web');

        $this->assertSame('Foo::bar', $result[0]['listeners'][0]);
    }

    public function testDescribeListenerHandlesClassMethodTuple(): void
    {
        $provider = new Yii3ConfigProvider($this->configWith([
            'events-web' => ['app.event' => [['Foo\\Bar', 'method']]],
        ]));

        $result = $provider->get('events-web');

        $this->assertSame('Foo\\Bar::method', $result[0]['listeners'][0]);
    }

    public function testDescribeListenerHandlesInstanceMethodTuple(): void
    {
        $instance = new class {
            public function handle(): void {}
        };
        $provider = new Yii3ConfigProvider($this->configWith([
            'events-web' => ['app.event' => [[$instance, 'handle']]],
        ]));

        $result = $provider->get('events-web');

        $this->assertSame($instance::class . '::handle', $result[0]['listeners'][0]);
    }

    public function testDescribeListenerDescribesClosureAsStructuredArray(): void
    {
        $provider = new Yii3ConfigProvider($this->configWith([
            'events-web' => [
                'app.event' => [static function (object $event): string {
                    return $event::class;
                }],
            ],
        ]));

        $result = $provider->get('events-web');
        $listener = $result[0]['listeners'][0];

        $this->assertIsArray($listener);
        $this->assertTrue($listener['__closure']);
        $this->assertStringContainsString('static function', $listener['source']);
        $this->assertStringContainsString('object $event', $listener['source']);
        $this->assertStringContainsString('return $event::class', $listener['source']);
        $this->assertSame(__FILE__, $listener['file']);
        $this->assertIsInt($listener['startLine']);
        $this->assertIsInt($listener['endLine']);
    }

    public function testDescribeListenerDescribesArrowFunction(): void
    {
        $provider = new Yii3ConfigProvider($this->configWith([
            'events-web' => ['app.event' => [static fn(object $e): string => $e::class]],
        ]));

        $result = $provider->get('events-web');
        $listener = $result[0]['listeners'][0];

        $this->assertIsArray($listener);
        $this->assertTrue($listener['__closure']);
        $this->assertStringContainsString('fn', $listener['source']);
        $this->assertStringContainsString('$e', $listener['source']);
    }

    public function testDescribeListenerHandlesInvokableObject(): void
    {
        $invokable = new class {
            public function __invoke(): void {}
        };
        $provider = new Yii3ConfigProvider($this->configWith([
            'events-web' => ['app.event' => [$invokable]],
        ]));

        $result = $provider->get('events-web');

        $this->assertSame($invokable::class . '::__invoke', $result[0]['listeners'][0]);
    }

    public function testDescribeListenerFallsBackToDebugTypeForUnknown(): void
    {
        $provider = new Yii3ConfigProvider($this->configWith([
            'events-web' => ['app.event' => [123]],
        ]));

        $result = $provider->get('events-web');

        $this->assertSame('int', $result[0]['listeners'][0]);
    }

    public function testGetEventsSkipsNonStringKeysAndNonArrayValues(): void
    {
        $provider = new Yii3ConfigProvider($this->configWith([
            'events-web' => [
                0 => ['ignored'],
                'app.event' => ['kept'],
                'invalid' => 'not-an-array',
            ],
        ]));

        $result = $provider->get('events-web');

        $this->assertCount(1, $result);
        $this->assertSame('app.event', $result[0]['name']);
        $this->assertSame(['kept'], $result[0]['listeners']);
    }

    /**
     * @param array<string, array<mixed>> $groups
     */
    private function configWith(array $groups): ConfigInterface
    {
        return new class($groups) implements ConfigInterface {
            /**
             * @param array<string, array<mixed>> $groups
             */
            public function __construct(
                private array $groups,
            ) {}

            public function get(string $group): array
            {
                return $this->groups[$group] ?? [];
            }

            public function has(string $group): bool
            {
                return array_key_exists($group, $this->groups);
            }
        };
    }

    private function throwingConfig(): ConfigInterface
    {
        return new class implements ConfigInterface {
            public function get(string $group): array
            {
                throw new \RuntimeException('config group not available: ' . $group);
            }

            public function has(string $group): bool
            {
                return false;
            }
        };
    }
}
