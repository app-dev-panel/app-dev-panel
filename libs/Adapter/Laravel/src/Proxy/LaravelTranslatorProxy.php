<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Proxy;

use AppDevPanel\Kernel\Collector\TranslationRecord;
use AppDevPanel\Kernel\Collector\TranslatorCollector;
use AppDevPanel\Kernel\ProxyDecoratedCalls;
use Illuminate\Contracts\Translation\Translator;

/**
 * Decorates Laravel's Translator to feed translation lookups to TranslatorCollector.
 *
 * Intercepts get() and choice() calls. A translation is considered "missing"
 * when get() returns the original key unchanged (no translation file entry found).
 */
final class LaravelTranslatorProxy implements Translator
{
    use ProxyDecoratedCalls;

    public function __construct(
        private readonly Translator $decorated,
        private readonly TranslatorCollector $collector,
    ) {}

    public function get($key, array $replace = [], $locale = null): mixed
    {
        $result = $this->decorated->get($key, $replace, $locale);

        $effectiveLocale = $locale ?? $this->decorated->getLocale();

        // Laravel keys use "group.key" format; the group acts as category.
        // For JSON translations, there is no group (category = 'messages').
        $dotPos = strpos($key, '.');
        if ($dotPos !== false) {
            $category = substr($key, 0, $dotPos);
            $messageId = substr($key, $dotPos + 1);
        } else {
            $category = 'messages';
            $messageId = $key;
        }

        // Laravel returns the key itself when translation is missing,
        // or an array for group-level access (e.g., get('validation')).
        $missing = $result === $key;
        $translation = is_string($result) && !$missing ? $result : null;

        $this->collector->logTranslation(new TranslationRecord(
            category: $category,
            locale: $effectiveLocale,
            message: $messageId,
            translation: $translation,
            missing: $missing,
        ));

        return $result;
    }

    public function choice($key, $number, array $replace = [], $locale = null): string
    {
        $result = $this->decorated->choice($key, $number, $replace, $locale);

        $effectiveLocale = $locale ?? $this->decorated->getLocale();

        $dotPos = strpos($key, '.');
        if ($dotPos !== false) {
            $category = substr($key, 0, $dotPos);
            $messageId = substr($key, $dotPos + 1);
        } else {
            $category = 'messages';
            $messageId = $key;
        }

        $missing = $result === $key;

        $this->collector->logTranslation(new TranslationRecord(
            category: $category,
            locale: $effectiveLocale,
            message: $messageId,
            translation: $missing ? null : $result,
            missing: $missing,
        ));

        return $result;
    }

    public function getLocale(): string
    {
        return $this->decorated->getLocale();
    }

    public function setLocale($locale): void
    {
        $this->decorated->setLocale($locale);
    }
}
