<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use AppDevPanel\Kernel\Collector\ValidatorCollector;
use yii\base\Action;

final class ValidatorAction extends Action
{
    public function run(): array
    {
        /** @var \AppDevPanel\Adapter\Yii2\Module $module */
        $module = \Yii::$app->getModule('app-dev-panel');

        /** @var ValidatorCollector|null $validatorCollector */
        $validatorCollector = $module->getCollector(ValidatorCollector::class);

        if ($validatorCollector === null) {
            return ['fixture' => 'validator:basic', 'status' => 'error', 'message' => 'ValidatorCollector not found'];
        }

        $validatorCollector->collect(value: ['email' => 'user@example.com', 'name' => 'John'], isValid: true, rules: [
            'email' => 'required|email',
            'name' => 'required|string|min:2',
        ]);

        $validatorCollector->collect(
            value: ['email' => 'not-an-email', 'name' => ''],
            isValid: false,
            errors: [
                'email' => ['The email must be a valid email address.'],
                'name' => ['The name field is required.'],
            ],
            rules: ['email' => 'required|email', 'name' => 'required|string|min:2'],
        );

        return ['fixture' => 'validator:basic', 'status' => 'ok'];
    }
}
