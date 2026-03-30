<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Proxy;

use AppDevPanel\Kernel\Collector\TranslationRecord;
use AppDevPanel\Kernel\Collector\TranslatorCollector;
use AppDevPanel\Kernel\ProxyDecoratedCalls;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Decorates Symfony's TranslatorInterface to feed translation lookups to TranslatorCollector.
 *
 * Intercepts trans() calls, delegates to the real translator, then logs the result.
 * A translation is considered "missing" when trans() returns the original message ID unchanged.
 */
final class SymfonyTranslatorProxy implements TranslatorInterface
{
    use ProxyDecoratedCalls;

    public function __construct(
        private readonly TranslatorInterface $decorated,
        private readonly TranslatorCollector $collector,
    ) {}

    public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        $translation = $this->decorated->trans($id, $parameters, $domain, $locale);

        $effectiveLocale = $locale ?? $this->decorated->getLocale();
        $effectiveDomain = $domain ?? 'messages';
        $missing = $translation === $id;

        $this->collector->logTranslation(new TranslationRecord(
            category: $effectiveDomain,
            locale: $effectiveLocale,
            message: $id,
            translation: $missing ? null : $translation,
            missing: $missing,
        ));

        return $translation;
    }

    public function getLocale(): string
    {
        return $this->decorated->getLocale();
    }
}
