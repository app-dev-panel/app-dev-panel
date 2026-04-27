<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Mailer;

use AppDevPanel\Kernel\Collector\MailerCollector;
use Spiral\Mailer\MailerInterface;
use Spiral\Mailer\MessageInterface;
use Stringable;

/**
 * Decorates `Spiral\Mailer\MailerInterface` so every dispatched message is forwarded
 * to {@see MailerCollector} before the underlying mailer transmits it.
 *
 * Lives under the adapter namespace because it implements a Spiral-specific contract.
 * The collector's `collectMessage()` handles inactive state internally — this proxy
 * never short-circuits.
 */
final class TracingMailer implements MailerInterface
{
    public function __construct(
        private readonly MailerInterface $inner,
        private readonly MailerCollector $collector,
    ) {}

    public function send(MessageInterface ...$message): void
    {
        foreach ($message as $msg) {
            $this->collector->collectMessage(self::normalize($msg));
        }

        $this->inner->send(...$message);
    }

    /**
     * @return array<string, mixed>
     */
    private static function normalize(MessageInterface $message): array
    {
        $options = self::callIfExists($message, 'getOptions');
        $options = is_array($options) ? $options : [];

        $body = self::stringOrEmpty($options['body'] ?? null);
        $contentType = self::stringOrDefault($options['contentType'] ?? null, 'text/plain');

        return [
            'from' => self::toAddressList(self::callIfExists($message, 'getFrom')),
            'to' => self::toAddressList(self::callIfExists($message, 'getTo')),
            'subject' => self::stringOrEmpty(self::callIfExists($message, 'getSubject')),
            'textBody' => $contentType === 'text/plain' ? $body : null,
            'htmlBody' => $contentType === 'text/html' ? $body : null,
            'raw' => $body,
            'headers' => [
                'Content-Type' => $contentType,
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function toAddressList(mixed $value): array
    {
        if ($value === null) {
            return [];
        }
        if (is_string($value)) {
            return [$value];
        }
        if (is_array($value)) {
            $out = [];
            foreach ($value as $entry) {
                $out[] = self::stringOrEmpty($entry);
            }
            return $out;
        }
        if ($value instanceof Stringable) {
            return [(string) $value];
        }
        return [];
    }

    private static function stringOrEmpty(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }
        if ($value instanceof Stringable) {
            return (string) $value;
        }
        return '';
    }

    private static function stringOrDefault(mixed $value, string $default): string
    {
        if (is_string($value) && $value !== '') {
            return $value;
        }
        return $default;
    }

    private static function callIfExists(object $message, string $method): mixed
    {
        if (!method_exists($message, $method)) {
            return null;
        }
        /** @var mixed */
        return $message->{$method}();
    }
}
