<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

final readonly class MessageRecord
{
    public function __construct(
        public string $messageClass,
        public string $bus = 'default',
        public ?string $transport = null,
        public bool $dispatched = true,
        public bool $handled = false,
        public bool $failed = false,
        public float $duration = 0.0,
        public mixed $message = null,
    ) {}

    public function toArray(): array
    {
        return [
            'messageClass' => $this->messageClass,
            'bus' => $this->bus,
            'transport' => $this->transport,
            'dispatched' => $this->dispatched,
            'handled' => $this->handled,
            'failed' => $this->failed,
            'duration' => $this->duration,
            'message' => $this->message,
        ];
    }
}
