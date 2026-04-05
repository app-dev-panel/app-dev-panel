<?php

declare(strict_types=1);

namespace App\controllers;

use OpenApi\Generator;
use yii\web\Controller;
use yii\web\Response;

final class OpenApiController extends Controller
{
    public function actionIndex(): string
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;

        $openapi = Generator::scan([dirname(__DIR__)]);

        return $openapi->toJson();
    }
}
