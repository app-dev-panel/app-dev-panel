<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yiisoft\Collector\Mailer;

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
        $this->collector->collectMessage($message);
        $this->decorated->send($message);
    }

    public function sendMultiple(array $messages): SendResults
    {
        $this->collector->collectMessages($messages);
        return $this->decorated->sendMultiple($messages);
    }
}
