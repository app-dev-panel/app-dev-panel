<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Mail;

use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\StorageInterface;

/**
 * Writes captured SMTP messages directly into storage as standalone debug entries.
 *
 * Each envelope (MAIL FROM/RCPT TO/DATA triple) produces one entry with a synthetic
 * debug ID. The payload mirrors the shape that {@see \AppDevPanel\Kernel\Collector\MailerCollector}
 * produces, so the existing MailerPanel frontend consumes it without changes.
 */
final class StandaloneMailerIngestion
{
    public function __construct(
        private readonly StorageInterface $storage,
        private readonly MimeParser $parser = new MimeParser(),
        private readonly DebuggerIdGenerator $idGenerator = new DebuggerIdGenerator(),
    ) {}

    /**
     * @param array{from: ?string, rcpt: list<string>, raw: string} $envelope
     * @param array<string, scalar> $smtpMeta e.g. ['peer' => '127.0.0.1:54321', 'ehlo' => 'localhost']
     */
    public function ingest(array $envelope, array $smtpMeta = []): string
    {
        $this->idGenerator->reset();
        $id = 'smtp-' . $this->idGenerator->getId();

        $message = $this->parser->parse($envelope['raw']);
        $recipientsFromEnvelope = $envelope['rcpt'];
        $senderFromEnvelope = $envelope['from'];

        if ($message['to'] === [] && $recipientsFromEnvelope !== []) {
            $message['to'] = array_fill_keys($recipientsFromEnvelope, '');
        }
        if ($message['from'] === [] && $senderFromEnvelope !== null && $senderFromEnvelope !== '') {
            $message['from'] = [$senderFromEnvelope => ''];
        }

        $collectorPayload = [
            'messages' => [
                [
                    'from' => $message['from'],
                    'to' => $message['to'],
                    'cc' => $message['cc'],
                    'bcc' => $message['bcc'],
                    'replyTo' => $message['replyTo'],
                    'subject' => $message['subject'],
                    'textBody' => $message['textBody'],
                    'htmlBody' => $message['htmlBody'],
                    'raw' => $message['raw'],
                    'charset' => $message['charset'],
                    'date' => $message['date'],
                ],
            ],
        ];

        $summary = [
            'id' => $id,
            'collectors' => [
                ['id' => 'mailer', 'name' => 'mailer'],
            ],
            'context' => [
                'type' => 'smtp',
                'service' => 'smtp-listener',
                'sessionId' => $message['sessionId'],
                'messageId' => $message['messageId'],
                'envelopeFrom' => $senderFromEnvelope,
                'envelopeRcpt' => $recipientsFromEnvelope,
                'smtp' => $smtpMeta,
                'receivedAt' => date(DATE_ATOM),
            ],
            'mailer' => ['total' => 1],
        ];

        $data = ['mailer' => $collectorPayload];
        $this->storage->write($id, $summary, $data, []);

        return $id;
    }
}
