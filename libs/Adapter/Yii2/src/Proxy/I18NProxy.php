<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Proxy;

use AppDevPanel\Kernel\Collector\TranslationRecord;
use AppDevPanel\Kernel\Collector\TranslatorCollector;
use yii\i18n\I18N;

/**
 * Decorates Yii 2's I18N component to feed translation lookups to TranslatorCollector.
 *
 * A translation is considered "missing" when the message source returns false
 * (i.e. no translation was found, and the original message is used).
 */
final class I18NProxy extends I18N
{
    private ?TranslatorCollector $collector = null;

    public function setCollector(TranslatorCollector $collector): void
    {
        $this->collector = $collector;
    }

    public function translate($category, $message, $params, $language): string
    {
        $messageSource = $this->getMessageSource($category);
        $rawTranslation = $messageSource->translate($category, $message, $language);
        $missing = $rawTranslation === false;

        $result = $missing
            ? $this->format($message, $params, $messageSource->sourceLanguage)
            : $this->format($rawTranslation, $params, $language);

        $this->collector?->logTranslation(new TranslationRecord(
            category: $category,
            locale: $language,
            message: $message,
            translation: $missing ? null : $result,
            missing: $missing,
        ));

        return $result;
    }
}
