<?php

declare(strict_types=1);

namespace App\Web\TestFixtures;

use AppDevPanel\Kernel\Collector\MailerCollector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;

final readonly class MailerAction implements RequestHandlerInterface
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private MailerCollector $mailerCollector,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Simulate a sent email by calling the collector directly.
        // This tests the MailerCollector without requiring yiisoft/mailer infrastructure.
        $this->mailerCollector->collectMessage([
            'from' => ['noreply@example.com' => 'ADP Test'],
            'to' => ['user@example.com' => 'Test User'],
            'cc' => [],
            'bcc' => [],
            'replyTo' => [],
            'subject' => 'ADP Test Fixture Email',
            'textBody' => 'This is a test email from the ADP mailer fixture.',
            'htmlBody' => '<p>This is a test email from the ADP mailer fixture.</p>',
            'raw' => '',
            'charset' => 'utf-8',
            'date' => date('r'),
        ]);

        return $this->responseFactory->createResponse(['fixture' => 'mailer:basic', 'status' => 'ok']);
    }
}
