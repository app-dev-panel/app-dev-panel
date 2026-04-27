<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Tests\Unit\Container;

use AppDevPanel\Adapter\Spiral\Container\MailerProxyInjector;
use AppDevPanel\Adapter\Spiral\Mailer\TracingMailer;
use AppDevPanel\Kernel\Collector\MailerCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Spiral\Core\Container;
use Spiral\Mailer\MailerInterface;
use Spiral\Mailer\MessageInterface;

final class MailerProxyInjectorTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        ContainerStubsBootstrap::install();
    }

    public function testProxyDecoratesUnderlyingService(): void
    {
        $container = new Container();
        $binder = $container->getBinder();
        $collector = new MailerCollector(new TimelineCollector());

        $fake = self::recordingMailer();

        $container->bindSingleton(MailerInterface::class, $fake);

        $injector = new MailerProxyInjector($container, $binder, $collector);
        $injector->setUnderlying($fake);
        $container->bindSingleton(MailerProxyInjector::class, $injector);

        $binder->bindInjector(MailerInterface::class, MailerProxyInjector::class);

        $resolved = $container->get(MailerInterface::class);

        self::assertInstanceOf(TracingMailer::class, $resolved);
        $reflection = new ReflectionProperty(TracingMailer::class, 'inner');
        self::assertSame($fake, $reflection->getValue($resolved));
    }

    public function testFallsBackToDefaultWhenNothingBound(): void
    {
        $container = new Container();
        $binder = $container->getBinder();
        $collector = new MailerCollector(new TimelineCollector());

        $injector = new MailerProxyInjector($container, $binder, $collector);
        $container->bindSingleton(MailerProxyInjector::class, $injector);

        $binder->bindInjector(MailerInterface::class, MailerProxyInjector::class);

        $resolved = $container->get(MailerInterface::class);

        self::assertInstanceOf(TracingMailer::class, $resolved);
        $reflection = new ReflectionProperty(TracingMailer::class, 'inner');
        $inner = $reflection->getValue($resolved);
        self::assertInstanceOf(MailerInterface::class, $inner);

        // Fallback drops the message silently.
        $inner->send(self::makeMessage('test', ['recipient@example.test']));
        self::addToAssertionCount(1);
    }

    public function testCollectorReceivesIntercept(): void
    {
        $container = new Container();
        $binder = $container->getBinder();
        $collector = new MailerCollector(new TimelineCollector());
        $collector->startup();

        $fake = self::recordingMailer();

        $injector = new MailerProxyInjector($container, $binder, $collector);
        $injector->setUnderlying($fake);
        $container->bindSingleton(MailerProxyInjector::class, $injector);

        $binder->bindInjector(MailerInterface::class, MailerProxyInjector::class);

        /** @var MailerInterface $mailer */
        $mailer = $container->get(MailerInterface::class);
        $mailer->send(self::makeMessage('hi there', ['recipient@example.test']));

        $entries = $collector->getCollected();
        self::assertCount(1, $entries['messages']);
        self::assertSame('hi there', $entries['messages'][0]['subject']);
        self::assertSame(['recipient@example.test'], $entries['messages'][0]['to']);
        // The fake mailer also received the original messages.
        self::assertCount(1, $fake->sent);
    }

    public function testInactiveCollectorDoesNotRecord(): void
    {
        $container = new Container();
        $binder = $container->getBinder();
        $collector = new MailerCollector(new TimelineCollector());
        // collector NOT started up — must be inactive

        $fake = self::recordingMailer();

        $injector = new MailerProxyInjector($container, $binder, $collector);
        $injector->setUnderlying($fake);
        $container->bindSingleton(MailerProxyInjector::class, $injector);

        $binder->bindInjector(MailerInterface::class, MailerProxyInjector::class);

        /** @var MailerInterface $mailer */
        $mailer = $container->get(MailerInterface::class);
        $mailer->send(self::makeMessage('inactive', ['x@example.test']));

        $entries = $collector->getCollected();
        self::assertSame([], $entries['messages']);
        self::assertCount(1, $fake->sent);
    }

    private static function recordingMailer(): object
    {
        return new class implements MailerInterface {
            /** @var list<MessageInterface> */
            public array $sent = [];

            public function send(MessageInterface ...$message): void
            {
                foreach ($message as $msg) {
                    $this->sent[] = $msg;
                }
            }
        };
    }

    /**
     * @param list<string> $to
     */
    private static function makeMessage(string $subject, array $to, string $body = 'plain text'): MessageInterface
    {
        return new class($subject, $to, $body) implements MessageInterface {
            public function __construct(
                private readonly string $subject,
                private readonly array $to,
                private readonly string $body,
            ) {}

            public function getSubject(): string
            {
                return $this->subject;
            }

            public function getFrom()
            {
                return ['no-reply@example.test'];
            }

            public function getTo(): array
            {
                return $this->to;
            }

            public function getOptions(): array
            {
                return [
                    'body' => $this->body,
                    'contentType' => 'text/plain',
                ];
            }
        };
    }
}
