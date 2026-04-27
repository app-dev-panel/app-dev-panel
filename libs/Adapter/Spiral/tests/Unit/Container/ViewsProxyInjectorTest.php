<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Tests\Unit\Container;

use AppDevPanel\Adapter\Spiral\Container\ViewsProxyInjector;
use AppDevPanel\Adapter\Spiral\View\TracingViews;
use AppDevPanel\Kernel\Collector\TemplateCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;
use Spiral\Core\Container;
use Spiral\Views\ViewInterface;
use Spiral\Views\ViewsInterface;

final class ViewsProxyInjectorTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        ContainerStubsBootstrap::install();
    }

    public function testProxyDecoratesUnderlyingService(): void
    {
        $container = new Container();
        $binder = $container->getBinder();
        $collector = new TemplateCollector(new TimelineCollector());

        $fake = self::recordingViews(['layout/main' => 'rendered']);

        $container->bindSingleton(ViewsInterface::class, $fake);

        $injector = new ViewsProxyInjector($container, $binder, $collector);
        $injector->setUnderlying($fake);
        $container->bindSingleton(ViewsProxyInjector::class, $injector);

        $binder->bindInjector(ViewsInterface::class, ViewsProxyInjector::class);

        $resolved = $container->get(ViewsInterface::class);

        self::assertInstanceOf(TracingViews::class, $resolved);
        $reflection = new ReflectionProperty(TracingViews::class, 'inner');
        self::assertSame($fake, $reflection->getValue($resolved));
    }

    public function testCollectorRecordsRender(): void
    {
        $container = new Container();
        $binder = $container->getBinder();
        $collector = new TemplateCollector(new TimelineCollector());
        $collector->startup();

        $fake = self::recordingViews(['home' => 'home output']);
        $injector = new ViewsProxyInjector($container, $binder, $collector);
        $injector->setUnderlying($fake);
        $container->bindSingleton(ViewsProxyInjector::class, $injector);

        $binder->bindInjector(ViewsInterface::class, ViewsProxyInjector::class);

        /** @var ViewsInterface $views */
        $views = $container->get(ViewsInterface::class);
        $output = $views->render('home', ['title' => 'Welcome']);

        self::assertSame('home output', $output);

        $entries = $collector->getCollected();
        self::assertCount(1, $entries['renders']);
        $entry = $entries['renders'][0];
        self::assertSame('home', $entry['template']);
        self::assertSame('home output', $entry['output']);
        self::assertSame(['title' => 'Welcome'], $entry['parameters']);
    }

    public function testCollectorRecordsRenderEvenWhenInnerThrows(): void
    {
        $container = new Container();
        $binder = $container->getBinder();
        $collector = new TemplateCollector(new TimelineCollector());
        $collector->startup();

        $fake = new class implements ViewsInterface {
            public function render(string $path, array $data = []): string
            {
                throw new RuntimeException('boom');
            }

            public function get(string $path): ViewInterface
            {
                throw new RuntimeException('not used');
            }

            public function compile(string $path): void {}

            public function reset(string $path): void {}
        };

        $injector = new ViewsProxyInjector($container, $binder, $collector);
        $injector->setUnderlying($fake);
        $container->bindSingleton(ViewsProxyInjector::class, $injector);

        $binder->bindInjector(ViewsInterface::class, ViewsProxyInjector::class);

        /** @var ViewsInterface $views */
        $views = $container->get(ViewsInterface::class);

        $threw = false;
        try {
            $views->render('boom-template', ['x' => 1]);
        } catch (RuntimeException) {
            $threw = true;
        }
        self::assertTrue($threw);

        $entries = $collector->getCollected();
        self::assertCount(1, $entries['renders']);
        self::assertSame('boom-template', $entries['renders'][0]['template']);
        // No output captured on failure.
        self::assertSame('', $entries['renders'][0]['output']);
    }

    public function testInactiveCollectorDoesNotRecord(): void
    {
        $container = new Container();
        $binder = $container->getBinder();
        $collector = new TemplateCollector(new TimelineCollector());

        $fake = self::recordingViews(['home' => 'inactive output']);
        $injector = new ViewsProxyInjector($container, $binder, $collector);
        $injector->setUnderlying($fake);
        $container->bindSingleton(ViewsProxyInjector::class, $injector);

        $binder->bindInjector(ViewsInterface::class, ViewsProxyInjector::class);

        /** @var ViewsInterface $views */
        $views = $container->get(ViewsInterface::class);
        $output = $views->render('home');
        self::assertSame('inactive output', $output);

        $entries = $collector->getCollected();
        self::assertSame([], $entries['renders']);
    }

    /**
     * @param array<string, string> $templates
     */
    private static function recordingViews(array $templates): ViewsInterface
    {
        return new class($templates) implements ViewsInterface {
            /** @param array<string, string> $templates */
            public function __construct(
                private readonly array $templates,
            ) {}

            public function render(string $path, array $data = []): string
            {
                return $this->templates[$path] ?? '';
            }

            public function get(string $path): ViewInterface
            {
                throw new RuntimeException('get() not used in tests');
            }

            public function compile(string $path): void {}

            public function reset(string $path): void {}
        };
    }
}
