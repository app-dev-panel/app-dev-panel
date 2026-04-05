<?php

declare(strict_types=1);

namespace App\controllers;

use OpenApi\Attributes as OA;
use yii\web\Controller;
use yii\web\Response;

#[OA\Info(
    version: '1.0.0',
    title: 'ADP Yii 2 Playground API',
    description: 'Demo API for the ADP Yii 2 Playground application.',
)]
final class ApiController extends Controller
{
    public function beforeAction($action): bool
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return parent::beforeAction($action);
    }

    #[OA\Get(
        path: '/api',
        summary: 'API index',
        tags: ['General'],
        responses: [
            new OA\Response(response: 200, description: 'API information', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'debug_panel', type: 'string'),
                new OA\Property(property: 'endpoints', type: 'object'),
            ])),
        ],
    )]
    public function actionIndex(): array
    {
        \Yii::info('API index accessed', 'application');

        \Yii::t('app', 'welcome', [], 'en');
        \Yii::t('app', 'welcome', [], 'de');
        \Yii::t('app', 'goodbye', [], 'fr');

        return [
            'message' => 'Welcome to the ADP Yii 2 Playground API!',
            'debug_panel' => '/debug/',
            'endpoints' => [
                'GET /api' => 'This page',
                'GET /api/users' => 'List users (demo)',
                'GET /api/error' => 'Trigger an exception (demo)',
            ],
        ];
    }

    #[OA\Get(
        path: '/api/users',
        summary: 'List users',
        tags: ['Users'],
        responses: [
            new OA\Response(response: 200, description: 'List of users', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'users', type: 'array', items: new OA\Items(properties: [
                    new OA\Property(property: 'id', type: 'integer'),
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'email', type: 'string'),
                ])),
            ])),
        ],
    )]
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

    #[OA\Get(
        path: '/api/error',
        summary: 'Trigger a demo exception',
        tags: ['General'],
        responses: [
            new OA\Response(response: 500, description: 'Demo exception'),
        ],
    )]
    public function actionErrorDemo(): never
    {
        \Yii::warning('About to trigger a demo exception', 'application');

        throw new \RuntimeException('This is a demo exception for ADP debugging');
    }
}
