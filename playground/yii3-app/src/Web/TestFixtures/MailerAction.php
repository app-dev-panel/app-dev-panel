<?php

declare(strict_types=1);

namespace App\Web\TestFixtures;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Mailer\MailerInterface;
use Yiisoft\Mailer\Message;

final readonly class MailerAction implements RequestHandlerInterface
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private MailerInterface $mailer,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Send a real email via yiisoft/mailer — the MailerInterfaceProxy intercepts
        // send() calls and feeds email metadata to MailerCollector.
        $message = new Message()
            ->withFrom('noreply@example.com')
            ->withTo('user@example.com')
            ->withSubject('ADP Test Fixture Email')
            ->withTextBody('This is a test email from the ADP mailer fixture.')
            ->withHtmlBody('<p>This is a test email from the ADP mailer fixture.</p>');

        $this->mailer->send($message);

        return $this->responseFactory->createResponse(['fixture' => 'mailer:basic', 'status' => 'ok']);
    }
}
