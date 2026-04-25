<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\EventSubscriber;

use AppDevPanel\Kernel\Collector\MailerCollector;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

/**
 * Listens to Symfony Mailer events and feeds MailerCollector.
 *
 * Captures email metadata (from, to, subject, body, attachments, headers) when
 * Symfony Mailer sends a message. Requires symfony/mailer. When not installed,
 * the subscriber is not registered (guarded by class_exists check in AppDevPanelExtension).
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

        $raw = $message->toString();
        $headers = [];
        foreach ($message->getHeaders()->all() as $header) {
            $headers[$header->getName()] = $header->getBodyAsString();
        }

        $attachments = [];
        foreach ($message->getAttachments() as $part) {
            $attachments[] = $this->normalizePart($part);
        }

        $messageIdHeader = $message->getHeaders()->get('Message-ID');
        $messageId = $messageIdHeader !== null ? $messageIdHeader->getBodyAsString() : null;

        $this->collector->collectMessage([
            'from' => $this->formatAddresses($message->getFrom()),
            'to' => $this->formatAddresses($message->getTo()),
            'cc' => $this->formatAddresses($message->getCc()),
            'bcc' => $this->formatAddresses($message->getBcc()),
            'replyTo' => $this->formatAddresses($message->getReplyTo()),
            'subject' => $message->getSubject() ?? '',
            'textBody' => $message->getTextBody(),
            'htmlBody' => $message->getHtmlBody(),
            'raw' => $raw,
            'charset' => $message->getTextCharset() ?? 'utf-8',
            'date' => $message->getDate()?->format('r'),
            'messageId' => $messageId,
            'headers' => $headers,
            'size' => \strlen($raw),
            'attachments' => $attachments,
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

    /**
     * @return array{
     *     filename: string,
     *     contentType: string,
     *     size: int,
     *     contentId: ?string,
     *     inline: bool,
     *     contentBase64: string,
     * }
     */
    private function normalizePart(DataPart $part): array
    {
        $body = $part->getBody();
        $contentType = $part->getMediaType() . '/' . $part->getMediaSubtype();
        $inline = $part->hasContentId() || $part->getDisposition() === 'inline';

        return [
            'filename' => $part->getFilename() ?? 'attachment',
            'contentType' => $contentType,
            'size' => \strlen($body),
            // `embed()` lazily generates the content id on first access — calling
            // getContentId() is the idiomatic way to read it for inline parts.
            'contentId' => $inline ? $part->getContentId() : null,
            'inline' => $inline,
            'contentBase64' => base64_encode($body),
        ];
    }
}
