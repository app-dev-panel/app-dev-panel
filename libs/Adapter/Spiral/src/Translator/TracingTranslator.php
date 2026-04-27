<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Translator;

use AppDevPanel\Kernel\Collector\TranslationRecord;
use AppDevPanel\Kernel\Collector\TranslatorCollector;
use Spiral\Translator\Catalogue\CatalogueInterface;
use Spiral\Translator\TranslatorInterface;

/**
 * Decorates `Spiral\Translator\TranslatorInterface` so every translation lookup is
 * forwarded to {@see TranslatorCollector} as a {@see TranslationRecord}.
 *
 * Lives under the adapter namespace because it implements a Spiral-specific contract.
 * The collector's `logTranslation()` handles inactive state internally.
 */
final class TracingTranslator implements TranslatorInterface
{
    public function __construct(
        private readonly TranslatorInterface $inner,
        private readonly TranslatorCollector $collector,
    ) {}

    public function trans(string $string, array $options = [], ?string $bundle = null, ?string $locale = null): string
    {
        $translated = $this->inner->trans($string, $options, $bundle, $locale);

        $this->collector->logTranslation(new TranslationRecord(
            category: $bundle ?? 'messages',
            locale: $locale ?? $this->inner->getLocale(),
            message: $string,
            translation: $translated,
            missing: $translated === $string,
        ));

        return $translated;
    }

    public function setLocale(string $locale): self
    {
        $this->inner->setLocale($locale);
        return $this;
    }

    public function getLocale(): string
    {
        return $this->inner->getLocale();
    }

    public function getCatalogue(?string $locale = null): CatalogueInterface
    {
        return $this->inner->getCatalogue($locale);
    }
}
