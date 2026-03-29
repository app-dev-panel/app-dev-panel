<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yiisoft\Collector\Translator;

use AppDevPanel\Kernel\Collector\TranslationRecord;
use AppDevPanel\Kernel\Collector\TranslatorCollector;
use Stringable;
use Yiisoft\Translator\CategorySource;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Decorates Yiisoft TranslatorInterface to feed translation lookups to TranslatorCollector.
 *
 * A translation is considered "missing" when translate() returns the original message ID unchanged.
 */
final class TranslatorInterfaceProxy implements TranslatorInterface
{
    private ?string $defaultCategory = null;

    public function __construct(
        private readonly TranslatorInterface $decorated,
        private readonly TranslatorCollector $collector,
    ) {}

    public function addCategorySources(CategorySource ...$categories): static
    {
        $this->decorated->addCategorySources(...$categories);
        return $this;
    }

    public function setLocale(string $locale): static
    {
        $this->decorated->setLocale($locale);
        return $this;
    }

    public function getLocale(): string
    {
        return $this->decorated->getLocale();
    }

    public function translate(
        string|Stringable $id,
        array $parameters = [],
        ?string $category = null,
        ?string $locale = null,
    ): string {
        $translation = $this->decorated->translate($id, $parameters, $category, $locale);

        $messageId = (string) $id;
        $effectiveLocale = $locale ?? $this->decorated->getLocale();
        $effectiveCategory = $category ?? $this->defaultCategory ?? 'app';
        $missing = $translation === $messageId;

        $this->collector->logTranslation(new TranslationRecord(
            category: $effectiveCategory,
            locale: $effectiveLocale,
            message: $messageId,
            translation: $missing ? null : $translation,
            missing: $missing,
        ));

        return $translation;
    }

    public function withDefaultCategory(string $category): static
    {
        $new = clone $this;
        $new->defaultCategory = $category;
        return $new;
    }

    public function withLocale(string $locale): static
    {
        $new = clone $this;
        $new->decorated->setLocale($locale);
        return $new;
    }
}
