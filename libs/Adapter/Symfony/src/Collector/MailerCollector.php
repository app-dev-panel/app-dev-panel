<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Collector;

use AppDevPanel\Kernel\Collector\CollectorTrait;
use AppDevPanel\Kernel\Collector\SummaryCollectorInterface;

/**
 * Collects Symfony Mailer data.
 *
 * Captures:
 * - Emails sent (from, to, subject)
 * - Email body preview
 * - Transport used
 *
 * Data is fed from Symfony Mailer's MessageEvent listener.
 */
final class MailerCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    /** @var array<int, array{from: string, to: array, subject: string, transport: string}> */
    private array $messages = [];

    public function logMessage(string $from, array $to, string $subject, string $transport = 'default'): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->messages[] = [
            'from' => $from,
            'to' => $to,
            'subject' => $subject,
            'transport' => $transport,
        ];
    }

    public function getCollected(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        return [
            'messages' => $this->messages,
            'messageCount' => count($this->messages),
        ];
    }

    public function getSummary(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        return [
            'mailer' => [
                'messageCount' => count($this->messages),
            ],
        ];
    }

    private function reset(): void
    {
        $this->messages = [];
    }
}
