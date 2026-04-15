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

    public function testParsesNestedMultipart(): void
    {
        $outer = 'outer-bnd';
        $inner = 'inner-bnd';
        $raw =
            "Subject: Nested\r\n"
            . "Content-Type: multipart/mixed; boundary=\"{$outer}\"\r\n"
            . "\r\n"
            . "--{$outer}\r\n"
            . "Content-Type: multipart/alternative; boundary=\"{$inner}\"\r\n"
            . "\r\n"
            . "--{$inner}\r\n"
            . "Content-Type: text/plain\r\n"
            . "\r\n"
            . "Plain inner\r\n"
            . "--{$inner}\r\n"
            . "Content-Type: text/html\r\n"
            . "\r\n"
            . "<p>Html inner</p>\r\n"
            . "--{$inner}--\r\n"
            . "--{$outer}\r\n"
            . "Content-Type: application/pdf\r\n"
            . "Content-Disposition: attachment; filename=\"a.pdf\"\r\n"
            . "\r\n"
            . "binary\r\n"
            . "--{$outer}--\r\n";

        $result = new MimeParser()->parse($raw);
        $this->assertSame('Plain inner', $result['textBody']);
        $this->assertSame('<p>Html inner</p>', $result['htmlBody']);
        $this->assertCount(1, $result['attachments']);
        $this->assertSame('a.pdf', $result['attachments'][0]['filename']);
    }

    public function testInvalidBase64DecodesToEmptyString(): void
    {
        $raw =
            "Subject: bad\r\n"
            . "Content-Type: text/plain\r\n"
            . "Content-Transfer-Encoding: base64\r\n"
            . "\r\n"
            . '!!!not-base64!!!';

        $result = new MimeParser()->parse($raw);
        $this->assertSame('', $result['textBody']);
    }

    public function testContentTypeParameterWithoutEqualsIsIgnored(): void
    {
        $raw = "Subject: weird\r\n" . "Content-Type: text/plain; charset=utf-8; orphan\r\n" . "\r\n" . 'body';

        $result = new MimeParser()->parse($raw);
        $this->assertSame('utf-8', $result['charset']);
        $this->assertSame('body', $result['textBody']);
    }

    public function testDispositionWithoutFilenameFallsBackToCtName(): void
    {
        $boundary = 'b';
        $raw =
            "Subject: att\r\n"
            . "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n"
            . "\r\n"
            . "--{$boundary}\r\n"
            . "Content-Type: text/plain; name=\"fallback.txt\"\r\n"
            . "Content-Disposition: attachment\r\n"
            . "\r\n"
            . "data\r\n"
            . "--{$boundary}--\r\n";

        $result = new MimeParser()->parse($raw);
        $this->assertCount(1, $result['attachments']);
        $this->assertSame('fallback.txt', $result['attachments'][0]['filename']);
    }

    public function testInlineDispositionTreatedAsAttachment(): void
    {
        $boundary = 'ib';
        $raw =
            "Subject: inl\r\n"
            . "Content-Type: multipart/related; boundary=\"{$boundary}\"\r\n"
            . "\r\n"
            . "--{$boundary}\r\n"
            . "Content-Type: image/png\r\n"
            . "Content-Disposition: inline; filename=\"pic.png\"\r\n"
            . "Content-ID: <logo@mail>\r\n"
            . "\r\n"
            . "bytes\r\n"
            . "--{$boundary}--\r\n";

        $result = new MimeParser()->parse($raw);
        $this->assertCount(1, $result['attachments']);
        $this->assertSame('logo@mail', $result['attachments'][0]['contentId']);
        $this->assertSame('image/png', $result['attachments'][0]['mime']);
    }

    public function testReplyToAndCcParsed(): void
    {
        $raw =
            "Subject: r\r\n"
            . "Reply-To: reply@x\r\n"
            . "Cc: cc1@x, cc2@y\r\n"
            . "Bcc: hidden@z\r\n"
            . "Content-Type: text/plain\r\n"
            . "\r\n"
            . 'b';

        $result = new MimeParser()->parse($raw);
        $this->assertArrayHasKey('reply@x', $result['replyTo']);
        $this->assertArrayHasKey('cc1@x', $result['cc']);
        $this->assertArrayHasKey('cc2@y', $result['cc']);
        $this->assertArrayHasKey('hidden@z', $result['bcc']);
    }

    public function testEmptyRawStringProducesEmptyResult(): void
    {
        $result = new MimeParser()->parse('');
        $this->assertSame('', $result['subject']);
        $this->assertSame([], $result['from']);
        $this->assertSame([], $result['to']);
        $this->assertNull($result['messageId']);
        $this->assertNull($result['sessionId']);
    }

    public function testHeadersWithoutColonSkipped(): void
    {
        $raw = "Subject: s\r\ngarbage-no-colon\r\nContent-Type: text/plain\r\n\r\nbody";
        $result = new MimeParser()->parse($raw);
        $this->assertSame('s', $result['subject']);
        $this->assertSame('body', $result['textBody']);
    }

    public function testBareAddressWithoutAngleBrackets(): void
    {
        $raw = "Subject: s\r\nTo: plain@example.com\r\nContent-Type: text/plain\r\n\r\nb";
        $result = new MimeParser()->parse($raw);
        $this->assertSame(['plain@example.com' => ''], $result['to']);
    }
}
