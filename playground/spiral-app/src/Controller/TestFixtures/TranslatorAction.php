<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use AppDevPanel\Kernel\Collector\TranslationRecord;
use AppDevPanel\Kernel\Collector\TranslatorCollector;

final class TranslatorAction
{
    public function __construct(
        private readonly TranslatorCollector $translator,
    ) {}

    /**
     * @return array<string, string>
     */
    public function __invoke(): array
    {
        $this->translator->logTranslation(new TranslationRecord('messages', 'en', 'welcome', 'Welcome', false));
        $this->translator->logTranslation(new TranslationRecord('messages', 'en', 'goodbye', 'Goodbye', false));
        $this->translator->logTranslation(new TranslationRecord('validation', 'en', 'invalid', 'Invalid value', false));
        $this->translator->logTranslation(new TranslationRecord('messages', 'en', 'missing.key', null, true));
        $this->translator->logTranslation(new TranslationRecord('messages', 'ru', 'hello', 'Hello', false, 'en'));

        return ['fixture' => 'translator:basic', 'status' => 'ok'];
    }
}
