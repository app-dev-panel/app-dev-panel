<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Mail;

use AppDevPanel\Kernel\Mail\MimeParser;
use PHPUnit\Framework\TestCase;

final class MimeParserTest extends TestCase
{
    public function testParsesPlainTextMessage(): void
    {
        $raw =
            "From: Alice <alice@example.com>\r\n"
            . "To: Bob <bob@example.com>\r\n"
            . "Subject: Hello\r\n"
            . "Date: Thu, 19 Mar 2026 12:00:00 +0000\r\n"
            . "Content-Type: text/plain; charset=utf-8\r\n"
            . "\r\n"
            . 'Hello Bob!';

        $result = new MimeParser()->parse($raw);

        $this->assertSame(['alice@example.com' => 'Alice'], $result['from']);
        $this->assertSame(['bob@example.com' => 'Bob'], $result['to']);
        $this->assertSame('Hello', $result['subject']);
        $this->assertSame('Hello Bob!', $result['textBody']);
        $this->assertNull($result['htmlBody']);
        $this->assertSame('utf-8', $result['charset']);
        $this->assertSame('Thu, 19 Mar 2026 12:00:00 +0000', $result['date']);
    }

    public function testParsesHtmlMessage(): void
    {
        $raw = "Subject: HTML\r\n" . "Content-Type: text/html; charset=utf-8\r\n" . "\r\n" . '<p>Hello</p>';

        $result = new MimeParser()->parse($raw);

        $this->assertSame('<p>Hello</p>', $result['htmlBody']);
        $this->assertNull($result['textBody']);
    }

    public function testParsesMultipartAlternative(): void
    {
        $boundary = 'boundary123';
        $raw =
            "Subject: Multi\r\n"
            . "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n"
            . "\r\n"
            . "preamble\r\n"
            . "--{$boundary}\r\n"
            . "Content-Type: text/plain; charset=utf-8\r\n"
            . "\r\n"
            . "Plain version\r\n"
            . "--{$boundary}\r\n"
            . "Content-Type: text/html; charset=utf-8\r\n"
            . "\r\n"
            . "<b>HTML version</b>\r\n"
            . "--{$boundary}--\r\n";

        $result = new MimeParser()->parse($raw);

        $this->assertSame('Plain version', $result['textBody']);
        $this->assertSame('<b>HTML version</b>', $result['htmlBody']);
    }

    public function testDecodesBase64Body(): void
    {
        $raw =
            "Subject: b64\r\n"
            . "Content-Type: text/plain; charset=utf-8\r\n"
            . "Content-Transfer-Encoding: base64\r\n"
            . "\r\n"
            . base64_encode('Secret payload');

        $result = new MimeParser()->parse($raw);
        $this->assertSame('Secret payload', $result['textBody']);
    }

    public function testDecodesQuotedPrintableBody(): void
    {
        $raw =
            "Subject: qp\r\n"
            . "Content-Type: text/plain; charset=utf-8\r\n"
            . "Content-Transfer-Encoding: quoted-printable\r\n"
            . "\r\n"
            . 'Caf=C3=A9';

        $result = new MimeParser()->parse($raw);
        $this->assertSame('Café', $result['textBody']);
    }

    public function testDecodesRfc2047EncodedSubject(): void
    {
        $raw =
            'Subject: =?UTF-8?B?'
            . base64_encode('Привет')
            . "?=\r\n"
            . "Content-Type: text/plain; charset=utf-8\r\n"
            . "\r\n"
            . 'body';

        $result = new MimeParser()->parse($raw);
        $this->assertSame('Привет', $result['subject']);
    }

    public function testParsesAttachmentMetadata(): void
    {
        $boundary = 'mixed123';
        $raw =
            "Subject: Attach\r\n"
            . "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n"
            . "\r\n"
            . "--{$boundary}\r\n"
            . "Content-Type: text/plain; charset=utf-8\r\n"
            . "\r\n"
            . "See attached.\r\n"
            . "--{$boundary}\r\n"
            . "Content-Type: application/pdf; name=\"report.pdf\"\r\n"
            . "Content-Disposition: attachment; filename=\"report.pdf\"\r\n"
            . "Content-Transfer-Encoding: base64\r\n"
            . "\r\n"
            . base64_encode('PDF-BYTES')
            . "\r\n"
            . "--{$boundary}--\r\n";

        $result = new MimeParser()->parse($raw);

        $this->assertSame('See attached.', $result['textBody']);
        $this->assertCount(1, $result['attachments']);
        $this->assertSame('report.pdf', $result['attachments'][0]['filename']);
        $this->assertSame('application/pdf', $result['attachments'][0]['mime']);
        $this->assertSame(strlen('PDF-BYTES'), $result['attachments'][0]['size']);
    }

    public function testExtractsMessageIdAndSessionId(): void
    {
        $raw =
            "Message-ID: <abc123@host>\r\n"
            . "X-ADP-Session-Id: my-session-id\r\n"
            . "Subject: s\r\n"
            . "Content-Type: text/plain; charset=utf-8\r\n"
            . "\r\n"
            . 'body';

        $result = new MimeParser()->parse($raw);
        $this->assertSame('abc123@host', $result['messageId']);
        $this->assertSame('my-session-id', $result['sessionId']);
    }

    public function testParsesMultipleRecipientsInToHeader(): void
    {
        $raw =
            "Subject: s\r\n"
            . "From: sender@x\r\n"
            . "To: \"User One\" <u1@x>, u2@y, \"Smith, John\" <john@z>\r\n"
            . "Content-Type: text/plain\r\n"
            . "\r\n"
            . 'b';

        $result = new MimeParser()->parse($raw);
        $this->assertArrayHasKey('u1@x', $result['to']);
        $this->assertSame('User One', $result['to']['u1@x']);
        $this->assertArrayHasKey('u2@y', $result['to']);
        $this->assertArrayHasKey('john@z', $result['to']);
        $this->assertSame('Smith, John', $result['to']['john@z']);
    }

    public function testHandlesFoldedHeaders(): void
    {
        $raw = "Subject: A long\r\n" . " folded subject\r\n" . "Content-Type: text/plain\r\n" . "\r\n" . 'body';

        $result = new MimeParser()->parse($raw);
        $this->assertSame('A long folded subject', $result['subject']);
    }

    public function testHandlesMessageWithNoBody(): void
    {
        $raw = "Subject: empty\r\nContent-Type: text/plain\r\n";
        $result = new MimeParser()->parse($raw);
        $this->assertSame('empty', $result['subject']);
    }

    public function testHandlesLfOnlyLineEndings(): void
    {
        $raw = "Subject: lf\nContent-Type: text/plain\n\nbody";
        $result = new MimeParser()->parse($raw);
        $this->assertSame('lf', $result['subject']);
        $this->assertSame('body', $result['textBody']);
    }
}
