<?php

declare(strict_types=1);

namespace App\controllers;

use yii\web\Controller;
use yii\web\Response;

final class SiteController extends Controller
{
    public $layout = 'main';

    public function actionIndex(): string
    {
        \Yii::info('Home page accessed', 'application');

        return $this->render('index');
    }

    public function actionUsers(): string
    {
        \Yii::info('Users page accessed', 'application');

        $users = [
            ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com', 'role' => 'Admin'],
            ['id' => 2, 'name' => 'Bob', 'email' => 'bob@example.com', 'role' => 'Editor'],
            ['id' => 3, 'name' => 'Charlie', 'email' => 'charlie@example.com', 'role' => 'Viewer'],
        ];

        return $this->render('users', ['users' => $users]);
    }

    public function actionContact(): string
    {
        $errors = [];
        $success = false;
        $data = [];

        if (\Yii::$app->request->isPost) {
            $data = \Yii::$app->request->post();

            if (empty($data['name'])) {
                $errors['name'] = 'Name is required.';
            }
            if (empty($data['email']) || !filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'A valid email is required.';
            }
            if (empty($data['message'])) {
                $errors['message'] = 'Message is required.';
            }

            if (empty($errors)) {
                \Yii::info('Contact form submitted', 'application');
                $success = true;
                $data = [];
            }
        }

        return $this->render('contact', [
            'errors' => $errors,
            'success' => $success,
            'data' => $data,
        ]);
    }

    public function actionApiPlayground(): string
    {
        return $this->render('api-playground');
    }

    public function actionErrorDemo(): never
    {
        \Yii::warning('About to trigger a demo exception', 'application');

        throw new \RuntimeException('This is a demo exception for ADP debugging');
    }

    public function actionError(): string
    {
        $exception = \Yii::$app->getErrorHandler()->exception;

        return $this->render('error', [
            'exception' => $exception,
        ]);
    }
}
