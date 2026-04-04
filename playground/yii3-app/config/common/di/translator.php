<?php

declare(strict_types=1);

use Yiisoft\Translator\CategorySource;
use Yiisoft\Translator\InMemoryMessageSource;
use Yiisoft\Translator\IntlMessageFormatter;
use Yiisoft\Translator\Translator;
use Yiisoft\Translator\TranslatorInterface;

return [
    TranslatorInterface::class => static function (): TranslatorInterface {
        $source = new InMemoryMessageSource();
        $source->write('app', 'en', [
            'welcome' => 'Welcome!',
            'goodbye' => 'Goodbye!',
        ]);
        $source->write('app', 'de', [
            'welcome' => 'Willkommen!',
        ]);

        $translator = new Translator('en');
        $translator->addCategorySources(new CategorySource('app', $source, new IntlMessageFormatter()));

        return $translator;
    },
];
