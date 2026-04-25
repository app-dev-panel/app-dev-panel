<?php

declare(strict_types=1);

namespace App\Web\LogDemoPage;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final readonly class Action
{
    public function __construct(
        private ViewRenderer $viewRenderer,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $success = false;
        $loggedLevel = '';
        $loggedMessage = '';

        if ($request->getMethod() === 'POST') {
            $body = $request->getParsedBody();
            $level = (string) ($body['level'] ?? 'info');
            $message = (string) ($body['message'] ?? '');
            $contextRaw = (string) ($body['context'] ?? '{}');

            $context = json_decode($contextRaw, true);
            if (!is_array($context)) {
                $context = [];
            }

            $this->logger->log($level, $message, $context);

            $success = true;
            $loggedLevel = $level;
            $loggedMessage = $message;
        }

        return $this->viewRenderer->render(__DIR__ . '/template', [
            'success' => $success,
            'loggedLevel' => $loggedLevel,
            'loggedMessage' => $loggedMessage,
        ]);
    }
}
