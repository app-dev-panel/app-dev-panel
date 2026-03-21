<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use AppDevPanel\Kernel\Collector\MailerCollector;
use Illuminate\Http\JsonResponse;

final readonly class MailerAction
{
    public function __construct(
        private MailerCollector $mailerCollector,
    ) {}

    public function __invoke(): JsonResponse
    {
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
