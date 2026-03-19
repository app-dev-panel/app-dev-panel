<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Unit\Collector;

use AppDevPanel\Adapter\Symfony\Collector\MailerCollector;
use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;
use Symfony\Component\Mime\Email;

final class MailerCollectorTest extends AbstractCollectorTestCase
{
    protected function getCollector(): CollectorInterface
    {
        return new MailerCollector();
    }

    /**
     * @param CollectorInterface|MailerCollector $collector
     */
    protected function collectTestData(CollectorInterface $collector): void
    {
        $email = (new Email())
            ->from('noreply@example.com')
            ->to('user@example.com', 'admin@example.com')
            ->subject('Welcome!')
            ->text('Hello')
            ->html('<p>Hello</p>');

        $collector->logEmail($email);
    }

    protected function checkCollectedData(array $data): void
    {
        parent::checkCollectedData($data);

        $this->assertArrayHasKey('messages', $data);
        $this->assertCount(1, $data['messages']);

        $message = $data['messages'][0];
        $this->assertSame(['noreply@example.com' => ''], $message['from']);
        $this->assertArrayHasKey('user@example.com', $message['to']);
        $this->assertArrayHasKey('admin@example.com', $message['to']);
        $this->assertSame('Welcome!', $message['subject']);
        $this->assertSame('Hello', $message['textBody']);
        $this->assertSame('<p>Hello</p>', $message['htmlBody']);
        $this->assertNotEmpty($message['raw']);
        $this->assertArrayHasKey('charset', $message);
        $this->assertArrayHasKey('date', $message);
        $this->assertSame([], $message['replyTo']);
        $this->assertSame([], $message['cc']);
        $this->assertSame([], $message['bcc']);
    }

    protected function checkSummaryData(array $data): void
    {
        parent::checkSummaryData($data);

        $this->assertArrayHasKey('mailer', $data);
        $this->assertSame(1, $data['mailer']['messageCount']);
    }

    public function testLogEmailWithAllFields(): void
    {
        $collector = new MailerCollector();
        $collector->startup();

        $email = (new Email())
            ->from('sender@example.com')
            ->to('recipient@example.com')
            ->cc('cc@example.com')
            ->bcc('bcc@example.com')
            ->replyTo('reply@example.com')
            ->subject('Full Email')
            ->text('Plain text')
            ->html('<p>HTML body</p>');

        $collector->logEmail($email);

        $data = $collector->getCollected();
        $message = $data['messages'][0];

        $this->assertSame(['cc@example.com' => ''], $message['cc']);
        $this->assertSame(['bcc@example.com' => ''], $message['bcc']);
        $this->assertSame(['reply@example.com' => ''], $message['replyTo']);
        $this->assertSame('Plain text', $message['textBody']);
        $this->assertSame('<p>HTML body</p>', $message['htmlBody']);
    }

    public function testLogEmailIgnoredWhenInactive(): void
    {
        $collector = new MailerCollector();

        $email = (new Email())
            ->from('a@b.com')
            ->to('b@c.com')
            ->subject('Test');

        $collector->logEmail($email);

        $this->assertSame([], $collector->getCollected());
    }
}
