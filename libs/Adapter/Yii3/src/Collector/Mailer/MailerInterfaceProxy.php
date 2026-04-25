<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii3\Collector\Mailer;

use AppDevPanel\Kernel\Collector\MailerCollector;
use Yiisoft\Mailer\File;
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

    /**
     * @return array<string, mixed>
     */
    private function normalizeMessage(MessageInterface $message): array
    {
        $raw = (string) $message;
        $htmlBody = $message->getCharset() === 'quoted-printable'
            ? quoted_printable_decode($message->getHtmlBody() ?? '')
            : $message->getHtmlBody();

        return [
            'from' => (array) $message->getFrom(),
            'to' => (array) $message->getTo(),
            'subject' => $message->getSubject() ?? '',
            'textBody' => $message->getTextBody(),
            'htmlBody' => $htmlBody,
            'replyTo' => (array) $message->getReplyTo(),
            'cc' => (array) $message->getCc(),
            'bcc' => (array) $message->getBcc(),
            'charset' => $message->getCharset() ?? 'utf-8',
            'date' => $message->getDate()?->format('Y-m-d H:i:s'),
            'raw' => $raw,
            'messageId' => null,
            'headers' => [],
            'size' => \strlen($raw),
            'attachments' => self::collectAttachments($message),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function collectAttachments(MessageInterface $message): array
    {
        // Attachments and embeddings live on the concrete Message class, not the interface.
        $attachments = [];
        if (method_exists($message, 'getAttachments')) {
            /** @var array<int, File>|null $list */
            $list = $message->getAttachments();
            foreach ($list ?? [] as $file) {
                $attachments[] = self::normalizeFile($file, false);
            }
        }
        if (method_exists($message, 'getEmbeddings')) {
            /** @var array<int, File>|null $list */
            $list = $message->getEmbeddings();
            foreach ($list ?? [] as $file) {
                $attachments[] = self::normalizeFile($file, true);
            }
        }
        return $attachments;
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
    private static function normalizeFile(File $file, bool $inline): array
    {
        $content = self::resolveFileContent($file);
        return [
            'filename' => $file->name() ?? 'attachment',
            'contentType' => $file->contentType() ?? 'application/octet-stream',
            'size' => \strlen($content),
            'contentId' => $inline ? $file->id() : null,
            'inline' => $inline,
            'contentBase64' => base64_encode($content),
        ];
    }

    private static function resolveFileContent(File $file): string
    {
        $content = $file->content();
        if ($content !== null) {
            return $content;
        }
        $path = $file->path();
        if ($path === null || !is_readable($path)) {
            return '';
        }
        $bytes = file_get_contents($path);
        return $bytes === false ? '' : $bytes;
    }
}
