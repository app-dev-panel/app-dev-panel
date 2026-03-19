<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

/**
 * Captures email messages sent by the application.
 *
 * Framework adapters normalize their message objects into arrays and call collectMessage().
 * Each message is an associative array with keys: from, to, cc, bcc, replyTo, subject,
 * textBody, htmlBody, raw, charset, date.
 */
final class MailerCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    /** @var array<int, array{from: array, to: array, cc: array, bcc: array, replyTo: array, subject: string, textBody: ?string, htmlBody: ?string, raw: string, charset: string, date: ?string}> */
    private array $messages = [];

    public function __construct(
        private readonly TimelineCollector $timelineCollector,
    ) {}

    /**
     * Collect a single normalized mail message.
     *
     * @param array{from: array, to: array, cc: array, bcc: array, replyTo: array, subject: string, textBody: ?string, htmlBody: ?string, raw: string, charset: string, date: ?string} $message
     */
    public function collectMessage(array $message): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->messages[] = $message;
        $this->timelineCollector->collect($this, count($this->messages));
    }

    /**
     * Collect multiple normalized messages at once.
     *
     * @param array<int, array{from: array, to: array, cc: array, bcc: array, replyTo: array, subject: string, textBody: ?string, htmlBody: ?string, raw: string, charset: string, date: ?string}> $messages
     */
    public function collectMessages(array $messages): void
    {
        if (!$this->isActive()) {
            return;
        }

        foreach ($messages as $message) {
            $this->messages[] = $message;
        }

        $this->timelineCollector->collect($this, count($this->messages));
    }

    public function getCollected(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        return [
            'messages' => $this->messages,
        ];
    }

    public function getSummary(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        return [
            'mailer' => [
                'total' => count($this->messages),
            ],
        ];
    }

    protected function reset(): void
    {
        $this->messages = [];
    }
}
