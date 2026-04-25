<?php

declare(strict_types=1);

namespace App\Web\VarDumperPage;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\VarDumper\VarDumper;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final readonly class Action
{
    public function __construct(
        private ViewRenderer $viewRenderer,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $success = false;

        if ($request->getMethod() === 'POST') {
            $data = [
                'string' => 'Hello from ADP Playground!',
                'integer' => 42,
                'float' => 3.14,
                'boolean' => true,
                'null_value' => null,
                'array' => ['apples', 'oranges', 'bananas'],
                'nested' => [
                    'user' => [
                        'id' => 1,
                        'name' => 'Alice',
                        'email' => 'alice@example.com',
                        'roles' => ['admin', 'editor'],
                    ],
                    'metadata' => [
                        'created_at' => '2026-04-04T12:00:00Z',
                        'version' => '1.0.0',
                    ],
                ],
            ];

            VarDumper::dump($data);
            $success = true;
        }

        return $this->viewRenderer->render(__DIR__ . '/template', [
            'success' => $success,
        ]);
    }
}
