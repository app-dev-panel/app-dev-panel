<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use AppDevPanel\Kernel\Collector\MailerCollector;

final class MailerAction
{
    public function __construct(
        private readonly MailerCollector $mailer,
    ) {}

    /**
     * @return array<string, string>
     */
    public function __invoke(): array
    {
        $this->mailer->collectMessage([
            'from' => 'noreply@adp.test',
            'to' => ['user@adp.test'],
            'subject' => 'Welcome',
            'body' => 'Hello from ADP Spiral fixture',
            'contentType' => 'text/plain',
        ]);

        $this->mailer->collectMessage([
            'from' => 'noreply@adp.test',
            'to' => ['alice@adp.test', 'bob@adp.test'],
            'subject' => 'Newsletter',
            'body' => '<p>Newsletter content</p>',
            'contentType' => 'text/html',
        ]);

        return ['fixture' => 'mailer:basic', 'status' => 'ok'];
    }
}
