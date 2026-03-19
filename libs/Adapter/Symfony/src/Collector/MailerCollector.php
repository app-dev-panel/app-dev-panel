<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Collector;

use AppDevPanel\Kernel\Collector\CollectorTrait;
use AppDevPanel\Kernel\Collector\SummaryCollectorInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

/**
 * Collects Symfony Mailer data.
 *
 * Captures email messages with full content (from, to, cc, bcc, subject, body, raw).
 * Data is fed from Symfony Mailer's MessageEvent listener.
 */
final class MailerCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    /** @var array<int, array{from: array, to: array, subject: string, textBody: ?string, htmlBody: ?string, raw: string, charset: string, date: ?string, replyTo: array, cc: array, bcc: array}> */
    private array $messages = [];

    /**
     * Log a raw message (minimal data extraction).
     */
    public function logRawMessage(RawMessage $message): void
    {
        if (!$this->isActive()) {
            return;
        }

        if ($message instanceof Email) {
            $this->logEmail($message);
            return;
        }

        $this->messages[] = [
            'from' => [],
            'to' => [],
            'subject' => '',
            'textBody' => null,
            'htmlBody' => null,
            'raw' => $message->toString(),
            'charset' => 'utf-8',
            'date' => date('r'),
            'replyTo' => [],
            'cc' => [],
            'bcc' => [],
        ];
    }

    /**
     * Log a Symfony Email with full data extraction.
     */
    public function logEmail(Email $email): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->messages[] = [
            'from' => self::addressesToMap($email->getFrom()),
            'to' => self::addressesToMap($email->getTo()),
            'subject' => $email->getSubject() ?? '',
            'textBody' => $email->getTextBody(),
            'htmlBody' => $email->getHtmlBody(),
            'raw' => $email->toString(),
            'charset' => $email->getTextCharset() ?? 'utf-8',
            'date' => date('r'),
            'replyTo' => self::addressesToMap($email->getReplyTo()),
            'cc' => self::addressesToMap($email->getCc()),
            'bcc' => self::addressesToMap($email->getBcc()),
        ];
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

    private function reset(): void
    {
        $this->messages = [];
    }

    /**
     * @param Address[] $addresses
     * @return array<string, string>
     */
    private static function addressesToMap(array $addresses): array
    {
        $map = [];
        foreach ($addresses as $address) {
            $map[$address->getAddress()] = $address->getName();
        }
        return $map;
    }
}
