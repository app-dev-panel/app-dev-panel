<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii3\Collector\Mailer;

use AppDevPanel\Kernel\Collector\MailerCollector;
use Yiisoft\Mailer\MailerInterface;
use Yiisoft\Mailer\MessageInterface;
use Yiisoft\Mailer\SendResults;

final class MailerInterfaceProxy implements MailerInterface
{
    public function __construct(
        private MailerInterface $decorated,
        private MailerCollector $collector,
    ) {}

    public function send(MessageInterface $message): void
    {
        $this->collector->collectMessage($this->normalizeMessage($message));
        $this->decorated->send($message);
    }

    public function sendMultiple(array $messages): SendResults
    {
        $this->collector->collectMessages(array_map($this->normalizeMessage(...), $messages));
        return $this->decorated->sendMultiple($messages);
    }

    private function normalizeMessage(MessageInterface $message): array
    {
        return [
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
        ];
    }
}
