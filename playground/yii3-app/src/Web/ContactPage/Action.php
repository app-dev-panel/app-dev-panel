<?php

declare(strict_types=1);

namespace App\Web\ContactPage;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final readonly class Action
{
    public function __construct(
        private ViewRenderer $viewRenderer,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $errors = [];
        $submitted = false;
        $formData = [
            'name' => '',
            'email' => '',
            'subject' => '',
            'message' => '',
        ];

        if ($request->getMethod() === 'POST' && is_array($body)) {
            $formData = [
                'name' => trim((string) ($body['name'] ?? '')),
                'email' => trim((string) ($body['email'] ?? '')),
                'subject' => trim((string) ($body['subject'] ?? '')),
                'message' => trim((string) ($body['message'] ?? '')),
            ];

            if ($formData['name'] === '') {
                $errors['name'] = 'Name is required.';
            }

            if ($formData['email'] === '') {
                $errors['email'] = 'Email is required.';
            } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Please enter a valid email address.';
            }

            if ($formData['subject'] === '') {
                $errors['subject'] = 'Subject is required.';
            }

            if ($formData['message'] === '') {
                $errors['message'] = 'Message is required.';
            }

            if ($errors === []) {
                $submitted = true;
            }
        }

        return $this->viewRenderer->render(__DIR__ . '/template', [
            'formData' => $formData,
            'errors' => $errors,
            'submitted' => $submitted,
        ]);
    }
}
