<?php

declare(strict_types=1);

namespace App\Web\TestFixtures;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Mailer\File;
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
        $plain = new Message()
            ->withFrom('noreply@example.com')
            ->withTo('user@example.com')
            ->withSubject('ADP fixture — plain text')
            ->withTextBody("Hello!\n\nThis is a plain-text email sent from the ADP Yii 3 fixture.\n\nCheers,\nADP");

        $table = new Message()
            ->withFrom('noreply@example.com')
            ->withTo('user@example.com')
            ->withSubject('ADP fixture — HTML table report')
            ->withTextBody("Weekly report:\n- Requests: 1240\n- Errors: 12\n- Avg response: 84ms")
            ->withHtmlBody(MailerFixtureContent::tableHtml());

        $rich = new Message()
            ->withFrom('noreply@example.com')
            ->withTo('user@example.com')
            ->withSubject('ADP fixture — newsletter with attachments')
            ->withTextBody('Please see the attached TXT and PDF files.')
            ->withHtmlBody(MailerFixtureContent::newsletterHtml())
            ->withAttachments(
                File::fromContent(MailerFixtureContent::textAttachment(), 'release-notes.txt', 'text/plain'),
                File::fromContent(MailerFixtureContent::pdfAttachment(), 'adp-fixture.pdf', 'application/pdf'),
            );

        $this->mailer->send($plain);
        $this->mailer->send($table);
        $this->mailer->send($rich);

        return $this->responseFactory->createResponse(['fixture' => 'mailer:basic', 'status' => 'ok', 'sent' => 3]);
    }
}
