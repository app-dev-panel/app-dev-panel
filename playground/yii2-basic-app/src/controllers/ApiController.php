<?php

declare(strict_types=1);

namespace App\controllers;

use yii\web\Controller;
use yii\web\Response;

final class ApiController extends Controller
{
    public function beforeAction($action): bool
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return parent::beforeAction($action);
    }

    public function actionIndex(): array
    {
        \Yii::info('API index accessed', 'application');

        \Yii::t('app', 'welcome', [], 'en');
        \Yii::t('app', 'welcome', [], 'de');
        \Yii::t('app', 'goodbye', [], 'fr');

        return [
            'message' => 'Welcome to the ADP Yii 2 Playground API!',
            'debug_panel' => '/debug/api/',
            'endpoints' => [
                'GET /api' => 'This page',
                'GET /api/users' => 'List users (demo)',
                'GET /api/error' => 'Trigger an exception (demo)',
            ],
        ];
    }

    public function actionUsers(): array
    {
        \Yii::info('Users API called', 'application');
        \Yii::debug('Fetching users from database', 'application');

        $users = [
            ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com'],
            ['id' => 2, 'name' => 'Bob', 'email' => 'bob@example.com'],
            ['id' => 3, 'name' => 'Charlie', 'email' => 'charlie@example.com'],
        ];

        \Yii::info('Users fetched: ' . count($users), 'application');

        return ['users' => $users];
    }

    public function actionErrorDemo(): never
    {
        \Yii::warning('About to trigger a demo exception', 'application');

        throw new \RuntimeException('This is a demo exception for ADP debugging');
    }
}
