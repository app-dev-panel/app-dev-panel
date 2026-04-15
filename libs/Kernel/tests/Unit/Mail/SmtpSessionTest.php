<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Mail;

use AppDevPanel\Kernel\Mail\SmtpSession;
use PHPUnit\Framework\TestCase;

final class SmtpSessionTest extends TestCase
{
    public function testGreetingReturns220Banner(): void
    {
        $session = new SmtpSession('adp-test');
        $this->assertSame("220 adp-test ADP SMTP ready\r\n", $session->greeting());
    }

    public function testGreetingReturnsEmptyOnSecondCall(): void
    {
        $session = new SmtpSession('adp-test');
        $session->greeting();
        $this->assertSame('', $session->greeting());
    }

    public function testEhloAdvertisesExtensions(): void
    {
        $session = new SmtpSession('adp-test', 1024);
        $session->greeting();
        $response = $session->feed("EHLO client\r\n");
        $this->assertStringContainsString('250-adp-test', $response);
        $this->assertStringContainsString('250-SIZE 1024', $response);
        $this->assertStringContainsString('250-PIPELINING', $response);
        $this->assertStringContainsString('250-AUTH PLAIN LOGIN', $response);
        $this->assertStringContainsString("250 HELP\r\n", $response);
    }

    public function testHeloWithoutDomainReturns501(): void
    {
        $session = new SmtpSession();
        $session->greeting();
        $this->assertStringStartsWith('501', $session->feed("HELO\r\n"));
    }

    public function testMailBeforeHeloReturns503(): void
    {
        $session = new SmtpSession();
        $session->greeting();
        $this->assertStringStartsWith('503', $session->feed("MAIL FROM:<foo@bar>\r\n"));
    }

    public function testMalformedMailFromReturns501(): void
    {
        $session = new SmtpSession();
        $session->greeting();
        $session->feed("EHLO x\r\n");
        $this->assertStringStartsWith('501', $session->feed("MAIL FROM: foo\r\n"));
    }

    public function testMailWithSizeExceedingLimitReturns552(): void
    {
        $session = new SmtpSession('host', 100);
        $session->greeting();
        $session->feed("EHLO x\r\n");
        $this->assertStringStartsWith('552', $session->feed("MAIL FROM:<a@b> SIZE=1000\r\n"));
    }

    public function testHappyPathDelivery(): void
    {
        $session = new SmtpSession();
        $session->greeting();
        $session->feed("EHLO client\r\n");
        $this->assertStringStartsWith('250', $session->feed("MAIL FROM:<sender@test>\r\n"));
        $this->assertStringStartsWith('250', $session->feed("RCPT TO:<rcpt@test>\r\n"));
        $this->assertStringStartsWith('354', $session->feed("DATA\r\n"));
        $response = $session->feed("Subject: Hi\r\n\r\nHello\r\n.\r\n");
        $this->assertStringStartsWith('250', $response);
        $this->assertTrue($session->hasCompletedMessage());
        $envelope = $session->takeCompletedMessage();
        $this->assertNotNull($envelope);
        $this->assertSame('sender@test', $envelope['from']);
        $this->assertSame(['rcpt@test'], $envelope['rcpt']);
        $this->assertStringContainsString('Hello', $envelope['raw']);
    }

    public function testMultipleRecipients(): void
    {
        $session = new SmtpSession();
        $session->greeting();
        $session->feed("EHLO x\r\n");
        $session->feed("MAIL FROM:<a@b>\r\n");
        $session->feed("RCPT TO:<c@d>\r\n");
        $session->feed("RCPT TO:<e@f>\r\n");
        $session->feed("DATA\r\n");
        $session->feed("Body\r\n.\r\n");
        $envelope = $session->takeCompletedMessage();
        $this->assertNotNull($envelope);
        $this->assertSame(['c@d', 'e@f'], $envelope['rcpt']);
    }

    public function testDotStuffingIsReversed(): void
    {
        $session = new SmtpSession();
        $session->greeting();
        $session->feed("EHLO x\r\n");
        $session->feed("MAIL FROM:<a@b>\r\n");
        $session->feed("RCPT TO:<c@d>\r\n");
        $session->feed("DATA\r\n");
        // Line starting with "." → stuffed as "..": must be unstuffed on receive.
        $session->feed("line1\r\n..dotline\r\n.\r\n");
        $envelope = $session->takeCompletedMessage();
        $this->assertNotNull($envelope);
        $this->assertStringContainsString("\r\n.dotline", $envelope['raw']);
    }

    public function testDataArrivingInMultipleFeedsIsAccumulated(): void
    {
        $session = new SmtpSession();
        $session->greeting();
        $session->feed("EHLO x\r\n");
        $session->feed("MAIL FROM:<a@b>\r\n");
        $session->feed("RCPT TO:<c@d>\r\n");
        $session->feed("DATA\r\n");
        $session->feed('Subject: Split');
        $session->feed(" message\r\n\r\nPart one\r\n");
        $session->feed("Part two\r\n.\r\n");
        $envelope = $session->takeCompletedMessage();
        $this->assertNotNull($envelope);
        $this->assertStringContainsString('Subject: Split message', $envelope['raw']);
        $this->assertStringContainsString('Part one', $envelope['raw']);
        $this->assertStringContainsString('Part two', $envelope['raw']);
    }

    public function testRsetClearsTransaction(): void
    {
        $session = new SmtpSession();
        $session->greeting();
        $session->feed("EHLO x\r\n");
        $session->feed("MAIL FROM:<a@b>\r\n");
        $session->feed("RCPT TO:<c@d>\r\n");
        $this->assertStringStartsWith('250', $session->feed("RSET\r\n"));
        // After RSET, RCPT out of order must fail.
        $this->assertStringStartsWith('503', $session->feed("RCPT TO:<e@f>\r\n"));
    }

    public function testQuitClosesSession(): void
    {
        $session = new SmtpSession();
        $session->greeting();
        $session->feed("EHLO x\r\n");
        $response = $session->feed("QUIT\r\n");
        $this->assertStringStartsWith('221', $response);
        $this->assertTrue($session->isClosed());
    }

    public function testNoopReturns250(): void
    {
        $session = new SmtpSession();
        $session->greeting();
        $session->feed("EHLO x\r\n");
        $this->assertStringStartsWith('250', $session->feed("NOOP\r\n"));
    }

    public function testStartTlsReturns502(): void
    {
        $session = new SmtpSession();
        $session->greeting();
        $session->feed("EHLO x\r\n");
        $this->assertStringStartsWith('502', $session->feed("STARTTLS\r\n"));
    }

    public function testAuthPlainAcceptedWithoutCheck(): void
    {
        $session = new SmtpSession();
        $session->greeting();
        $session->feed("EHLO x\r\n");
        $response = $session->feed("AUTH PLAIN abc\r\n");
        $this->assertStringStartsWith('235', $response);
    }

    public function testAuthLoginTwoStepFlow(): void
    {
        $session = new SmtpSession();
        $session->greeting();
        $session->feed("EHLO x\r\n");
        $this->assertStringStartsWith('334', $session->feed("AUTH LOGIN\r\n"));
        $this->assertStringStartsWith('334', $session->feed("dXNlcg==\r\n"));
        $this->assertStringStartsWith('235', $session->feed("cGFzcw==\r\n"));
        // After auth, we should be back in READY and able to run a transaction.
        $this->assertStringStartsWith('250', $session->feed("MAIL FROM:<a@b>\r\n"));
    }

    public function testUnknownCommandReturns500(): void
    {
        $session = new SmtpSession();
        $session->greeting();
        $this->assertStringStartsWith('500', $session->feed("FROBNICATE\r\n"));
    }

    public function testOversizedLineRejected(): void
    {
        $session = new SmtpSession();
        $session->greeting();
        $payload = str_repeat('A', 1100) . "\r\n";
        $this->assertStringStartsWith('500', $session->feed($payload));
    }

    public function testPipeliningCommandsInOneFeed(): void
    {
        $session = new SmtpSession();
        $session->greeting();
        $response = $session->feed("EHLO x\r\nMAIL FROM:<a@b>\r\nRCPT TO:<c@d>\r\n");
        // EHLO emits one multi-line 250-... capability list plus two single 250 OK replies.
        $this->assertStringContainsString('250-', $response);
        $this->assertStringContainsString("250 OK\r\n", $response);
        // Exactly two "250 OK" lines: one for MAIL FROM, one for RCPT TO.
        $this->assertSame(2, substr_count($response, "250 OK\r\n"));
    }

    public function testFeedAfterCloseReturnsEmpty(): void
    {
        $session = new SmtpSession();
        $session->greeting();
        $session->feed("QUIT\r\n");
        $this->assertSame('', $session->feed("EHLO x\r\n"));
    }

    public function testLoneLfLineTerminatorAccepted(): void
    {
        $session = new SmtpSession();
        $session->greeting();
        // Some legacy clients send LF only.
        $this->assertStringStartsWith('250', $session->feed("EHLO x\n"));
    }

    public function testDataOversizeReturns552(): void
    {
        $session = new SmtpSession('h', 50);
        $session->greeting();
        $session->feed("EHLO x\r\n");
        $session->feed("MAIL FROM:<a@b>\r\n");
        $session->feed("RCPT TO:<c@d>\r\n");
        $session->feed("DATA\r\n");
        $response = $session->feed(str_repeat('A', 200));
        $this->assertStringContainsString('552', $response);
    }

    public function testEmptyCommandLineReturns500(): void
    {
        $session = new SmtpSession();
        $session->greeting();
        $this->assertStringStartsWith('500', $session->feed("\r\n"));
    }

    public function testHeloHappyPath(): void
    {
        $session = new SmtpSession('srv');
        $session->greeting();
        $response = $session->feed("HELO client\r\n");
        $this->assertSame("250 srv\r\n", $response);
        // After HELO, MAIL FROM must succeed.
        $this->assertStringStartsWith('250', $session->feed("MAIL FROM:<a@b>\r\n"));
    }

    public function testEhloWithoutDomainReturns501(): void
    {
        $session = new SmtpSession();
        $session->greeting();
        $this->assertStringStartsWith('501', $session->feed("EHLO\r\n"));
    }

    public function testRcptBeforeMailReturns503(): void
    {
        $session = new SmtpSession();
        $session->greeting();
        $session->feed("EHLO x\r\n");
        $this->assertStringStartsWith('503', $session->feed("RCPT TO:<a@b>\r\n"));
    }

    public function testMalformedRcptReturns501(): void
    {
        $session = new SmtpSession();
        $session->greeting();
        $session->feed("EHLO x\r\n");
        $session->feed("MAIL FROM:<a@b>\r\n");
        $this->assertStringStartsWith('501', $session->feed("RCPT blah\r\n"));
    }

    public function testDataBeforeRcptReturns503(): void
    {
        $session = new SmtpSession();
        $session->greeting();
        $session->feed("EHLO x\r\n");
        $session->feed("MAIL FROM:<a@b>\r\n");
        $this->assertStringStartsWith('503', $session->feed("DATA\r\n"));
    }

    public function testAuthUnknownMechanismReturns504(): void
    {
        $session = new SmtpSession();
        $session->greeting();
        $session->feed("EHLO x\r\n");
        $this->assertStringStartsWith('504', $session->feed("AUTH CRAM-MD5\r\n"));
    }

    public function testVrfyReturns502(): void
    {
        $session = new SmtpSession();
        $session->greeting();
        $this->assertStringStartsWith('502', $session->feed("VRFY foo\r\n"));
    }

    public function testExpnReturns502(): void
    {
        $session = new SmtpSession();
        $session->greeting();
        $this->assertStringStartsWith('502', $session->feed("EXPN list\r\n"));
    }

    public function testHelpReturns214(): void
    {
        $session = new SmtpSession();
        $session->greeting();
        $this->assertStringStartsWith('214', $session->feed("HELP\r\n"));
    }

    public function testCloseMarksSessionClosed(): void
    {
        $session = new SmtpSession();
        $session->greeting();
        $session->close();
        $this->assertTrue($session->isClosed());
    }

    public function testTakeCompletedMessageReturnsNullWhenEmpty(): void
    {
        $session = new SmtpSession();
        $this->assertFalse($session->hasCompletedMessage());
        $this->assertNull($session->takeCompletedMessage());
    }

    public function testMultipleEnvelopesInOneSession(): void
    {
        $session = new SmtpSession();
        $session->greeting();
        $session->feed("EHLO x\r\n");

        // First envelope
        $session->feed("MAIL FROM:<a@b>\r\n");
        $session->feed("RCPT TO:<c@d>\r\n");
        $session->feed("DATA\r\nFirst\r\n.\r\n");

        // Second envelope (after auto-reset)
        $session->feed("MAIL FROM:<e@f>\r\n");
        $session->feed("RCPT TO:<g@h>\r\n");
        $session->feed("DATA\r\nSecond\r\n.\r\n");

        $first = $session->takeCompletedMessage();
        $second = $session->takeCompletedMessage();
        $this->assertNotNull($first);
        $this->assertNotNull($second);
        $this->assertSame('a@b', $first['from']);
        $this->assertSame('e@f', $second['from']);
    }
}
