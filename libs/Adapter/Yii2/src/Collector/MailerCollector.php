<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Collector;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\CollectorTrait;
use AppDevPanel\Kernel\Collector\SummaryCollectorInterface;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use yii\mail\MessageInterface;

/**
 * Captures mail messages sent via Yii 2's mailer component.
 *
 * Fed by BaseMailer::EVENT_AFTER_SEND, registered in Module::registerMailerProfiling().
 * Extracts full message data including body and raw content for MailerPanel compatibility.
 */
final class MailerCollector implements CollectorInterface, SummaryCollectorInterface
{
    use CollectorTrait;

    /** @var array<int, array{from: array, to: array, cc: array, bcc: array, replyTo: array, subject: string, textBody: ?string, htmlBody: ?string, raw: string, charset: string, date: string}> */
    private array $messages = [];

    public function __construct(
        private readonly TimelineCollector $timeline,
    ) {}

    /**
     * Log a Yii 2 mail message with full data extraction.
     */
    public function logMessage(MessageInterface $message): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->messages[] = [
            'from' => $this->normalizeAddresses($message->getFrom()),
            'to' => $this->normalizeAddresses($message->getTo()),
            'cc' => $this->normalizeAddresses($message->getCc()),
            'bcc' => $this->normalizeAddresses($message->getBcc()),
            'replyTo' => $this->normalizeAddresses($message->getReplyTo()),
            'subject' => $message->getSubject() ?? '',
            'textBody' => $this->extractTextBody($message),
            'htmlBody' => $this->extractHtmlBody($message),
            'raw' => $this->extractRaw($message),
            'charset' => $message->getCharset() ?? 'utf-8',
            'date' => date('r'),
        ];

        $this->timeline->collect($this, count($this->messages));
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
                'messageCount' => count($this->messages),
            ],
        ];
    }

    protected function reset(): void
    {
        $this->messages = [];
    }

    /**
     * @return array<string, string>
     */
    private function normalizeAddresses(mixed $addresses): array
    {
        if ($addresses === null) {
            return [];
        }

        if (is_string($addresses)) {
            return [$addresses => ''];
        }

        if (is_array($addresses)) {
            $result = [];
            foreach ($addresses as $key => $value) {
                if (is_int($key)) {
                    $result[(string) $value] = '';
                } else {
                    $result[$key] = (string) $value;
                }
            }
            return $result;
        }

        return [(string) $addresses => ''];
    }

    private function extractTextBody(MessageInterface $message): ?string
    {
        if (method_exists($message, 'getTextBody')) {
            return $message->getTextBody();
        }
        return null;
    }

    private function extractHtmlBody(MessageInterface $message): ?string
    {
        if (method_exists($message, 'getHtmlBody')) {
            return $message->getHtmlBody();
        }
        return null;
    }

    private function extractRaw(MessageInterface $message): string
    {
        if (method_exists($message, 'toString')) {
            return (string) $message->toString();
        }
        return '';
    }
}
