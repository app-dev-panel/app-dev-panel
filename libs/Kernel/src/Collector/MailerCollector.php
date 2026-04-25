<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

/**
 * Captures email messages sent by the application.
 *
 * Framework adapters normalize their message objects into arrays and call collectMessage().
 * Each message is an associative array with keys: from, to, cc, bcc, replyTo, subject,
 * textBody, htmlBody, raw, charset, date, messageId, headers, size, attachments.
 *
 * Attachment entries: {filename, contentType, size, contentId?, inline, contentBase64}.
 * `inline` entries participate in `cid:` resolution in the HTML preview; non-inline
 * entries surface as downloadable attachments in the UI.
 */
final class MailerCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    /**
     * @var array<int, array{
     *     from: array,
     *     to: array,
     *     cc: array,
     *     bcc: array,
     *     replyTo: array,
     *     subject: string,
     *     textBody: ?string,
     *     htmlBody: ?string,
     *     raw: string,
     *     charset: string,
     *     date: ?string,
     *     messageId: ?string,
     *     headers: array<string, string>,
     *     size: int,
     *     attachments: array<int, array{
     *         filename: string,
     *         contentType: string,
     *         size: int,
     *         contentId: ?string,
     *         inline: bool,
     *         contentBase64: string,
     *     }>,
     * }>
     */
    private array $messages = [];

    public function __construct(
        private readonly TimelineCollector $timelineCollector,
    ) {}

    /**
     * Collect a single normalized mail message.
     *
     * Adapters may omit new fields (messageId, headers, size, attachments);
     * defaults are filled in here to keep older integrations backwards-compatible.
     *
     * @param array<string, mixed> $message
     */
    public function collectMessage(array $message): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->messages[] = $this->normalize($message);
        $this->timelineCollector->collect($this, count($this->messages));
    }

    /**
     * Collect multiple normalized messages at once.
     *
     * @param array<int, array<string, mixed>> $messages
     */
    public function collectMessages(array $messages): void
    {
        if (!$this->isActive()) {
            return;
        }

        foreach ($messages as $message) {
            $this->messages[] = $this->normalize($message);
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

    /**
     * @param array<string, mixed> $message
     * @return array{
     *     from: array,
     *     to: array,
     *     cc: array,
     *     bcc: array,
     *     replyTo: array,
     *     subject: string,
     *     textBody: ?string,
     *     htmlBody: ?string,
     *     raw: string,
     *     charset: string,
     *     date: ?string,
     *     messageId: ?string,
     *     headers: array<string, string>,
     *     size: int,
     *     attachments: array<int, array{
     *         filename: string,
     *         contentType: string,
     *         size: int,
     *         contentId: ?string,
     *         inline: bool,
     *         contentBase64: string,
     *     }>,
     * }
     */
    private function normalize(array $message): array
    {
        $raw = (string) ($message['raw'] ?? '');
        $declaredSize = $message['size'] ?? null;

        /** @var array<string, string> $headers */
        $headers = \is_array($message['headers'] ?? null) ? $message['headers'] : [];

        return [
            'from' => (array) ($message['from'] ?? []),
            'to' => (array) ($message['to'] ?? []),
            'cc' => (array) ($message['cc'] ?? []),
            'bcc' => (array) ($message['bcc'] ?? []),
            'replyTo' => (array) ($message['replyTo'] ?? []),
            'subject' => (string) ($message['subject'] ?? ''),
            'textBody' => self::nullableString($message['textBody'] ?? null),
            'htmlBody' => self::nullableString($message['htmlBody'] ?? null),
            'raw' => $raw,
            'charset' => (string) ($message['charset'] ?? 'utf-8'),
            'date' => self::nullableString($message['date'] ?? null),
            'messageId' => self::nullableString($message['messageId'] ?? null),
            'headers' => $headers,
            'size' => \is_int($declaredSize) ? $declaredSize : \strlen($raw),
            'attachments' => self::normalizeAttachments($message['attachments'] ?? null),
        ];
    }

    private static function nullableString(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }

    /**
     * @return array<int, array{
     *     filename: string,
     *     contentType: string,
     *     size: int,
     *     contentId: ?string,
     *     inline: bool,
     *     contentBase64: string,
     * }>
     */
    private static function normalizeAttachments(mixed $rawAttachments): array
    {
        if (!\is_array($rawAttachments)) {
            return [];
        }
        $attachments = [];
        foreach ($rawAttachments as $attachment) {
            if (!\is_array($attachment)) {
                continue;
            }
            $attachments[] = self::normalizeAttachment($attachment);
        }
        return $attachments;
    }

    /**
     * @param array<string, mixed> $attachment
     * @return array{
     *     filename: string,
     *     contentType: string,
     *     size: int,
     *     contentId: ?string,
     *     inline: bool,
     *     contentBase64: string,
     * }
     */
    private static function normalizeAttachment(array $attachment): array
    {
        $contentBase64 = (string) ($attachment['contentBase64'] ?? '');
        $declaredSize = $attachment['size'] ?? null;
        return [
            'filename' => (string) ($attachment['filename'] ?? ''),
            'contentType' => (string) ($attachment['contentType'] ?? 'application/octet-stream'),
            'size' => \is_int($declaredSize) ? $declaredSize : \strlen(base64_decode($contentBase64, true) ?: ''),
            'contentId' => self::nullableString($attachment['contentId'] ?? null),
            'inline' => (bool) ($attachment['inline'] ?? false),
            'contentBase64' => $contentBase64,
        ];
    }
}
