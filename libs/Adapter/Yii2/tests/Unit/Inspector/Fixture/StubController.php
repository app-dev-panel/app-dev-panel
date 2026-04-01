<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Unit\Inspector\Fixture;

use yii\web\Controller;

final class StubController extends Controller
{
    public function actionIndex(): string
    {
        return '';
    }

    public function actionAbout(): string
    {
        return '';
    }

    public function actionLogin(): string
    {
        return '';
    }
}
