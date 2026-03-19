<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Unit\Collector;

use AppDevPanel\Adapter\Yii2\Collector\MailerCollector;
use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;

final class MailerCollectorTest extends AbstractCollectorTestCase
{
    protected function getCollector(): CollectorInterface
    {
        return new MailerCollector(new TimelineCollector());
    }

    protected function collectTestData(CollectorInterface $collector): void
    {
        /** @var MailerCollector $collector */
        $collector->logMessage('sender@example.com', ['recipient@example.com'], null, null, 'Test Subject', true);
        $collector->logMessage(
            ['noreply@app.com' => 'App'],
            ['user@example.com'],
            ['cc@example.com'],
            ['bcc@example.com'],
            'Welcome Email',
            false,
        );
    }

    protected function checkCollectedData(array $data): void
    {
        $this->assertArrayHasKey('messages', $data);
        $this->assertArrayHasKey('messageCount', $data);
        $this->assertSame(2, $data['messageCount']);
        $this->assertCount(2, $data['messages']);

        $first = $data['messages'][0];
        $this->assertSame(['sender@example.com'], $first['from']);
        $this->assertSame(['recipient@example.com'], $first['to']);
        $this->assertSame([], $first['cc']);
        $this->assertSame([], $first['bcc']);
        $this->assertSame('Test Subject', $first['subject']);
        $this->assertTrue($first['isSuccessful']);

        $second = $data['messages'][1];
        $this->assertSame(['noreply@app.com' => 'App'], $second['from']);
        $this->assertSame(['cc@example.com'], $second['cc']);
        $this->assertFalse($second['isSuccessful']);
    }

    protected function checkSummaryData(array $data): void
    {
        $this->assertArrayHasKey('mailer', $data);
        $this->assertSame(2, $data['mailer']['messageCount']);
    }

    public function testLogMessageIgnoredWhenInactive(): void
    {
        $collector = new MailerCollector(new TimelineCollector());

        $collector->logMessage('a@b.com', 'b@c.com', null, null, 'Test', true);

        $this->assertSame([], $collector->getCollected());
    }

    public function testResetClearsData(): void
    {
        $collector = new MailerCollector(new TimelineCollector());
        $collector->startup();

        $collector->logMessage('a@b.com', 'b@c.com', null, null, 'Test', true);
        $this->assertSame(1, $collector->getCollected()['messageCount']);

        $collector->shutdown();
        $collector->startup();
        $this->assertSame(0, $collector->getCollected()['messageCount']);
    }

    public function testNormalizeAddressesHandlesStringInput(): void
    {
        $collector = new MailerCollector(new TimelineCollector());
        $collector->startup();

        $collector->logMessage('sender@test.com', 'recipient@test.com', null, null, 'Test', true);

        $msg = $collector->getCollected()['messages'][0];
        $this->assertSame(['sender@test.com'], $msg['from']);
        $this->assertSame(['recipient@test.com'], $msg['to']);
    }
}
