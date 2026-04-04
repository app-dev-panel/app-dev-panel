<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\EventSubscriber;

use AppDevPanel\Kernel\Collector\MailerCollector;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Listens to Symfony Mailer events and feeds MailerCollector.
 *
 * Captures email metadata (from, to, subject, body) when Symfony Mailer sends a message.
 * Requires symfony/mailer. When not installed, the subscriber is not registered
 * (guarded by class_exists check in AppDevPanelExtension).
 */
final class MailerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly MailerCollector $collector,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            MessageEvent::class => ['onMessage', 0],
        ];
    }

    public function onMessage(MessageEvent $event): void
    {
        $message = $event->getMessage();

        if (!$message instanceof Email) {
            return;
        }

        $this->collector->collectMessage([
            'from' => $this->formatAddresses($message->getFrom()),
            'to' => $this->formatAddresses($message->getTo()),
            'cc' => $this->formatAddresses($message->getCc()),
            'bcc' => $this->formatAddresses($message->getBcc()),
            'replyTo' => $this->formatAddresses($message->getReplyTo()),
            'subject' => $message->getSubject() ?? '',
            'textBody' => $message->getTextBody(),
            'htmlBody' => $message->getHtmlBody(),
            'raw' => '',
            'charset' => $message->getTextCharset() ?? 'utf-8',
            'date' => $message->getDate()?->format('r'),
        ]);
    }

    /**
     * @param Address[] $addresses
     * @return array<string, string>
     */
    private function formatAddresses(array $addresses): array
    {
        $result = [];
        foreach ($addresses as $address) {
            $result[$address->getAddress()] = $address->getName() !== '' ? $address->getName() : $address->getAddress();
        }

        return $result;
    }
}
