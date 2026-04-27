<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Tests\Unit\Container;

use AppDevPanel\Adapter\Spiral\Container\TranslatorProxyInjector;
use AppDevPanel\Adapter\Spiral\Translator\TracingTranslator;
use AppDevPanel\Kernel\Collector\TranslatorCollector;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Spiral\Core\Container;
use Spiral\Translator\Catalogue\CatalogueInterface;
use Spiral\Translator\TranslatorInterface;

final class TranslatorProxyInjectorTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        ContainerStubsBootstrap::install();
    }

    public function testProxyDecoratesUnderlyingService(): void
    {
        $container = new Container();
        $binder = $container->getBinder();
        $collector = new TranslatorCollector();

        $fake = self::recordingTranslator(['hello' => 'Hi!']);

        $container->bindSingleton(TranslatorInterface::class, $fake);

        $injector = new TranslatorProxyInjector($container, $binder, $collector);
        $injector->setUnderlying($fake);
        $container->bindSingleton(TranslatorProxyInjector::class, $injector);

        $binder->bindInjector(TranslatorInterface::class, TranslatorProxyInjector::class);

        $resolved = $container->get(TranslatorInterface::class);

        self::assertInstanceOf(TracingTranslator::class, $resolved);
        $reflection = new ReflectionProperty(TracingTranslator::class, 'inner');
        self::assertSame($fake, $reflection->getValue($resolved));
    }

    public function testCollectorRecordsTranslation(): void
    {
        $container = new Container();
        $binder = $container->getBinder();
        $collector = new TranslatorCollector();
        $collector->startup();

        $fake = self::recordingTranslator(['hello' => 'Bonjour']);
        $injector = new TranslatorProxyInjector($container, $binder, $collector);
        $injector->setUnderlying($fake);
        $container->bindSingleton(TranslatorProxyInjector::class, $injector);

        $binder->bindInjector(TranslatorInterface::class, TranslatorProxyInjector::class);

        /** @var TranslatorInterface $translator */
        $translator = $container->get(TranslatorInterface::class);
        self::assertSame('Bonjour', $translator->trans('hello'));

        $entries = $collector->getCollected();
        self::assertCount(1, $entries['translations']);
        $entry = $entries['translations'][0];
        self::assertSame('hello', $entry['message']);
        self::assertSame('Bonjour', $entry['translation']);
        self::assertFalse($entry['missing']);
        self::assertSame('messages', $entry['category']);
    }

    public function testMissingTranslationsAreFlagged(): void
    {
        $container = new Container();
        $binder = $container->getBinder();
        $collector = new TranslatorCollector();
        $collector->startup();

        $fake = self::recordingTranslator([]);
        $injector = new TranslatorProxyInjector($container, $binder, $collector);
        $injector->setUnderlying($fake);
        $container->bindSingleton(TranslatorProxyInjector::class, $injector);

        $binder->bindInjector(TranslatorInterface::class, TranslatorProxyInjector::class);

        /** @var TranslatorInterface $translator */
        $translator = $container->get(TranslatorInterface::class);
        self::assertSame('untranslated', $translator->trans('untranslated', [], 'errors', 'fr'));

        $entries = $collector->getCollected();
        self::assertCount(1, $entries['translations']);
        self::assertTrue($entries['translations'][0]['missing']);
        self::assertSame('errors', $entries['translations'][0]['category']);
        self::assertSame('fr', $entries['translations'][0]['locale']);
        self::assertSame(1, $entries['missingCount']);
    }

    public function testInactiveCollectorDoesNotRecord(): void
    {
        $container = new Container();
        $binder = $container->getBinder();
        $collector = new TranslatorCollector();

        $fake = self::recordingTranslator(['hi' => 'hello']);
        $injector = new TranslatorProxyInjector($container, $binder, $collector);
        $injector->setUnderlying($fake);
        $container->bindSingleton(TranslatorProxyInjector::class, $injector);

        $binder->bindInjector(TranslatorInterface::class, TranslatorProxyInjector::class);

        /** @var TranslatorInterface $translator */
        $translator = $container->get(TranslatorInterface::class);
        self::assertSame('hello', $translator->trans('hi'));

        $entries = $collector->getCollected();
        self::assertSame([], $entries['translations']);
    }

    /**
     * @param array<string, string> $dict
     */
    private static function recordingTranslator(array $dict): TranslatorInterface
    {
        return new class($dict) implements TranslatorInterface {
            private string $locale = 'en';

            /** @param array<string, string> $dict */
            public function __construct(
                private readonly array $dict,
            ) {}

            public function trans(
                string $string,
                array $options = [],
                ?string $bundle = null,
                ?string $locale = null,
            ): string {
                return $this->dict[$string] ?? $string;
            }

            public function setLocale(string $locale): self
            {
                $this->locale = $locale;
                return $this;
            }

            public function getLocale(): string
            {
                return $this->locale;
            }

            public function getCatalogue(?string $locale = null): CatalogueInterface
            {
                return new class implements CatalogueInterface {
                    public function getName(): string
                    {
                        return 'fake';
                    }
                };
            }
        };
    }
}
