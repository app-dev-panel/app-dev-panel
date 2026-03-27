<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use AppDevPanel\Kernel\Collector\TranslationRecord;
use AppDevPanel\Kernel\Collector\TranslatorCollector;
use yii\base\Action;

final class TranslatorAction extends Action
{
    public function run(): array
    {
        /** @var \AppDevPanel\Adapter\Yii2\Module $module */
        $module = \Yii::$app->getModule('debug-panel');

        /** @var TranslatorCollector|null $translatorCollector */
        $translatorCollector = $module->getCollector(TranslatorCollector::class);

        if ($translatorCollector === null) {
            return ['fixture' => 'translator:basic', 'status' => 'error', 'message' => 'TranslatorCollector not found'];
        }

        $translatorCollector->logTranslation(new TranslationRecord(
            category: 'app',
            locale: 'en',
            message: 'welcome',
            translation: 'Welcome!',
        ));

        $translatorCollector->logTranslation(new TranslationRecord(
            category: 'app',
            locale: 'de',
            message: 'welcome',
            translation: 'Willkommen!',
        ));

        $translatorCollector->logTranslation(new TranslationRecord(
            category: 'app',
            locale: 'en',
            message: 'goodbye',
            translation: 'Goodbye!',
        ));

        $translatorCollector->logTranslation(new TranslationRecord(
            category: 'app',
            locale: 'fr',
            message: 'welcome',
            missing: true,
        ));

        return ['fixture' => 'translator:basic', 'status' => 'ok'];
    }
}
