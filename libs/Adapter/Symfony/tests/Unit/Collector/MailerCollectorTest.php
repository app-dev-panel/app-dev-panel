<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Unit\Collector;

use AppDevPanel\Adapter\Symfony\Collector\MailerCollector;
use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;

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
        $collector->logMessage(
            'noreply@example.com',
            ['user@example.com', 'admin@example.com'],
            'Welcome!',
            'smtp',
        );
    }

    protected function checkCollectedData(array $data): void
    {
        parent::checkCollectedData($data);

        $this->assertSame(1, $data['messageCount']);
        $this->assertCount(1, $data['messages']);

        $message = $data['messages'][0];
        $this->assertSame('noreply@example.com', $message['from']);
        $this->assertSame(['user@example.com', 'admin@example.com'], $message['to']);
        $this->assertSame('Welcome!', $message['subject']);
        $this->assertSame('smtp', $message['transport']);
    }

    protected function checkSummaryData(array $data): void
    {
        parent::checkSummaryData($data);

        $this->assertArrayHasKey('mailer', $data);
        $this->assertSame(1, $data['mailer']['messageCount']);
    }
}
