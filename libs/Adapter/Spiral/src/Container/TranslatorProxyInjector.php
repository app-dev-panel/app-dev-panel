<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Container;

use AppDevPanel\Adapter\Spiral\Translator\TracingTranslator;
use AppDevPanel\Kernel\Collector\TranslatorCollector;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use Spiral\Core\BinderInterface;
use Spiral\Core\Container\InjectorInterface;
use Spiral\Translator\Catalogue\CatalogueInterface;
use Spiral\Translator\TranslatorInterface;

/**
 * Spiral container injector that wraps any `Spiral\Translator\TranslatorInterface`
 * resolution with {@see TracingTranslator} so every translation lookup is forwarded
 * to {@see TranslatorCollector}.
 *
 * Only registered by the bootloader when `interface_exists(TranslatorInterface::class)` —
 * `spiral/translator` is an optional package.
 *
 * @implements InjectorInterface<TranslatorInterface>
 */
final class TranslatorProxyInjector implements InjectorInterface
{
    use InjectorTrait;

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly BinderInterface $binder,
        private readonly TranslatorCollector $collector,
    ) {}

    public function createInjection(ReflectionClass $class, ?string $context = null): TranslatorInterface
    {
        /** @var TranslatorInterface $original */
        $original = $this->resolveUnderlying(
            $this->container,
            $this->binder,
            TranslatorInterface::class,
            self::nullTranslator(),
        );

        return new TracingTranslator($original, $this->collector);
    }

    private static function nullTranslator(): TranslatorInterface
    {
        return new class implements TranslatorInterface {
            public function trans(
                string $string,
                array $options = [],
                ?string $bundle = null,
                ?string $locale = null,
            ): string {
                return $string;
            }

            public function setLocale(string $locale): self
            {
                return $this;
            }

            public function getLocale(): string
            {
                return 'en';
            }

            public function getCatalogue(?string $locale = null): CatalogueInterface
            {
                throw new \RuntimeException('No upstream translator bound to the container.');
            }
        };
    }
}
