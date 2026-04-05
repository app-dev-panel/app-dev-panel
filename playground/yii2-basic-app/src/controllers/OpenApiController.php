<?php

declare(strict_types=1);

namespace App\controllers;

use OpenApi\Generator;
use yii\web\Controller;
use yii\web\Response;

final class OpenApiController extends Controller
{
    public function actionIndex(): array
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        \Yii::$app->response->headers->set('Access-Control-Allow-Origin', '*');
        \Yii::$app->response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
        \Yii::$app->response->headers->set('Access-Control-Allow-Headers', 'Content-Type');

        $openapi = Generator::scan([dirname(__DIR__)]);

        return json_decode($openapi->toJson(), true, 512, JSON_THROW_ON_ERROR);
    }
}
