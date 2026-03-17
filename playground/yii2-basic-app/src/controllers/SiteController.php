<?php

declare(strict_types=1);

namespace App\controllers;

use yii\web\Controller;

final class SiteController extends Controller
{
    public function actionIndex(): array
    {
        \Yii::info('Home page accessed', 'application');

        return [
            'message' => 'Welcome to the ADP Yii 2 Playground!',
            'debug_panel' => '/debug/api/',
            'endpoints' => [
                'GET /' => 'This page',
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

    public function actionError(): array
    {
        $exception = \Yii::$app->getErrorHandler()->exception;

        return [
            'error' => $exception?->getMessage() ?? 'Unknown error',
            'code' => $exception?->getCode() ?? 500,
        ];
    }

    public function beforeAction($action): bool
    {
        // Return JSON for all actions
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return parent::beforeAction($action);
    }
}
