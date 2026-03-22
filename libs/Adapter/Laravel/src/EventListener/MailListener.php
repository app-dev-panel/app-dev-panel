<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\EventListener;

use AppDevPanel\Kernel\Collector\MailerCollector;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Mail\Events\MessageSent;

/**
 * Listens for Illuminate\Mail\Events\MessageSent and feeds the MailerCollector.
 */
final class MailListener
{
    /** @var \Closure(): MailerCollector */
    private \Closure $collectorFactory;

    /**
     * @param \Closure(): MailerCollector $collectorFactory
     */
    public function __construct(\Closure $collectorFactory)
    {
        $this->collectorFactory = $collectorFactory;
    }

    public function register(Dispatcher $events): void
    {
        $events->listen(MessageSent::class, function (MessageSent $event): void {
            $message = $event->message;

            $from = [];
            if ($message->getFrom()) {
                foreach ($message->getFrom() as $address) {
                    $from[$address->getAddress()] = $address->getName() ?: $address->getAddress();
                }
            }

            $to = [];
            if ($message->getTo()) {
                foreach ($message->getTo() as $address) {
                    $to[$address->getAddress()] = $address->getName() ?: $address->getAddress();
                }
            }

            ($this->collectorFactory)()->collectMessage([
                'from' => $from,
                'to' => $to,
                'cc' => [],
                'bcc' => [],
                'replyTo' => [],
                'subject' => $message->getSubject() ?? '',
                'textBody' => $message->getTextBody() ?? '',
                'htmlBody' => $message->getHtmlBody() ?? '',
                'raw' => '',
                'charset' => 'utf-8',
                'date' => date('r'),
            ]);
        });
    }
}
