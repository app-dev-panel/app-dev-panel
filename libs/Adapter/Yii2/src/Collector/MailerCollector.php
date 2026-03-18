<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Collector;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\CollectorTrait;
use AppDevPanel\Kernel\Collector\SummaryCollectorInterface;
use AppDevPanel\Kernel\Collector\TimelineCollector;

/**
 * Captures mail messages sent via Yii 2's mailer component.
 *
 * Fed by BaseMailer::EVENT_AFTER_SEND, registered in Module::registerMailerProfiling().
 */
final class MailerCollector implements CollectorInterface, SummaryCollectorInterface
{
    use CollectorTrait;

    /** @var array<int, array{from: mixed, to: mixed, cc: mixed, bcc: mixed, subject: string, isSuccessful: bool}> */
    private array $messages = [];

    public function __construct(
        private readonly TimelineCollector $timeline,
    ) {}

    public function logMessage(
        mixed $from,
        mixed $to,
        mixed $cc,
        mixed $bcc,
        string $subject,
        bool $isSuccessful,
    ): void {
        if (!$this->isActive()) {
            return;
        }

        $this->messages[] = [
            'from' => $this->normalizeAddresses($from),
            'to' => $this->normalizeAddresses($to),
            'cc' => $this->normalizeAddresses($cc),
            'bcc' => $this->normalizeAddresses($bcc),
            'subject' => $subject,
            'isSuccessful' => $isSuccessful,
        ];

        $this->timeline->collect($this, count($this->messages));
    }

    public function getCollected(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        return [
            'messages' => $this->messages,
            'messageCount' => count($this->messages),
        ];
    }

    public function getSummary(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        return [
            'mailer' => [
                'messageCount' => count($this->messages),
            ],
        ];
    }

    protected function reset(): void
    {
        $this->messages = [];
    }

    private function normalizeAddresses(mixed $addresses): array
    {
        if ($addresses === null) {
            return [];
        }

        if (is_string($addresses)) {
            return [$addresses];
        }

        if (is_array($addresses)) {
            return $addresses;
        }

        return [(string) $addresses];
    }
}
