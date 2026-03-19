<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use AppDevPanel\Kernel\Collector\MailerCollector;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/test/fixtures/mailer', name: 'test_mailer', methods: ['GET'])]
final readonly class MailerAction
{
    public function __construct(
        private MailerCollector $mailerCollector,
    ) {}

    public function __invoke(): JsonResponse
    {
        // Simulate a sent email by calling the collector directly.
        // This tests the MailerCollector without requiring symfony/mailer infrastructure.
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

        return new JsonResponse(['fixture' => 'mailer:basic', 'status' => 'ok']);
    }
}
