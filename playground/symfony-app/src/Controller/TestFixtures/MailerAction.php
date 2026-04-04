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
        // Send a real email via Symfony Mailer — the MailerSubscriber listens to
        // MessageEvent and feeds email metadata to MailerCollector.
        $email = new Email()
            ->from('noreply@example.com')
            ->to('user@example.com')
            ->subject('ADP Test Fixture Email')
            ->text('This is a test email from the ADP mailer fixture.')
            ->html('<p>This is a test email from the ADP mailer fixture.</p>');

        try {
            $this->mailer->send($email);
            $sent = true;
        } catch (\Throwable) {
            // Mailer may fail without a configured transport — that's OK for fixtures.
            // The MailerSubscriber captures the email before the transport sends it.
            $sent = false;
        }

        return new JsonResponse(['fixture' => 'mailer:basic', 'status' => 'ok', 'sent' => $sent]);
    }
}
