<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use yii\base\Action;

final class TranslatorAction extends Action
{
    public function run(): array
    {
        // Found: en → "Welcome!"
        \Yii::t('app', 'welcome', [], 'en');

        // Found: de → "Willkommen!"
        \Yii::t('app', 'welcome', [], 'de');

        // Found: en → "Goodbye!"
        \Yii::t('app', 'goodbye', [], 'en');

        // Missing: fr has no translations file
        \Yii::t('app', 'welcome', [], 'fr');

        return ['fixture' => 'translator:basic', 'status' => 'ok'];
    }
}
