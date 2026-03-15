<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yiisoft\Collector\Mailer;

use AppDevPanel\Kernel\Collector\CollectorTrait;
use AppDevPanel\Kernel\Collector\SummaryCollectorInterface;
use Yiisoft\Mailer\MessageInterface;

use function count;

final class MailerCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    private array $messages = [];

    public function collectMessage(MessageInterface $message): void
    {
        $this->messages[] = $message;
    }

    public function collectMessages(array $messages): void
    {
        $this->messages = array_merge($this->messages, $messages);
    }

    public function getCollected(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        return [
            'messages' => array_map(fn(MessageInterface $message) => [
                'from' => (array) $message->getFrom(),
                'to' => (array) $message->getTo(),
                'subject' => $message->getSubject(),
                'textBody' => $message->getTextBody(),
                'htmlBody' => $message->getCharset() === 'quoted-printable'
                    ? quoted_printable_decode($message->getHtmlBody() ?? '')
                    : $message->getHtmlBody(),
                'replyTo' => (array) $message->getReplyTo(),
                'cc' => (array) $message->getCc(),
                'bcc' => (array) $message->getBcc(),
                'charset' => $message->getCharset(),
                'date' => $message->getDate(),
                'raw' => (string) $message,
            ], $this->messages),
        ];
    }

    public function getSummary(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        return [
            'total' => count($this->messages),
        ];
    }

    private function reset(): void
    {
        $this->messages = [];
    }
}
