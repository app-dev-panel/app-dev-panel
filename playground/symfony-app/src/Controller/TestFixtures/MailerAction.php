<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/test/fixtures/mailer', name: 'test_mailer', methods: ['GET'])]
final readonly class MailerAction
{
    public function __construct(
        private MailerInterface $mailer,
    ) {}

    public function __invoke(): JsonResponse
    {
        $plain = new Email()
            ->from('noreply@example.com')
            ->to('user@example.com')
            ->subject('ADP fixture — plain text')
            ->text("Hello!\n\nThis is a plain-text email sent from the ADP Symfony fixture.\n\nCheers,\nADP");

        $table = new Email()
            ->from('noreply@example.com')
            ->to('user@example.com')
            ->subject('ADP fixture — HTML table report')
            ->text("Weekly report:\n- Requests: 1240\n- Errors: 12\n- Avg response: 84ms")
            ->html(MailerFixtureContent::tableHtml());

        $rich = new Email()
            ->from('noreply@example.com')
            ->to('user@example.com')
            ->subject('ADP fixture — newsletter with attachments')
            ->text('Please see the attached TXT and PDF files.')
            ->html(MailerFixtureContent::newsletterHtml())
            ->attach(MailerFixtureContent::textAttachment(), 'release-notes.txt', 'text/plain')
            ->attach(MailerFixtureContent::pdfAttachment(), 'adp-fixture.pdf', 'application/pdf');

        $sent = 0;
        foreach ([$plain, $table, $rich] as $email) {
            try {
                $this->mailer->send($email);
                $sent++;
            } catch (\Throwable) {
                // Mailer may fail without a configured transport — that's OK for fixtures.
                // The MailerSubscriber captures the email before the transport sends it.
            }
        }

        return new JsonResponse(['fixture' => 'mailer:basic', 'status' => 'ok', 'sent' => $sent]);
    }
}
