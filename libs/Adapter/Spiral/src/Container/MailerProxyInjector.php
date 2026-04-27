<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Container;

use AppDevPanel\Adapter\Spiral\Mailer\TracingMailer;
use AppDevPanel\Kernel\Collector\MailerCollector;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use Spiral\Core\BinderInterface;
use Spiral\Core\Container\InjectorInterface;
use Spiral\Mailer\MailerInterface;
use Spiral\Mailer\MessageInterface;

/**
 * Spiral container injector that wraps any `Spiral\Mailer\MailerInterface` resolution
 * with {@see TracingMailer} so every dispatched message is forwarded to
 * {@see MailerCollector}.
 *
 * Only registered by the bootloader when `interface_exists(MailerInterface::class)` —
 * `spiral/mailer` is an optional package.
 *
 * @implements InjectorInterface<MailerInterface>
 */
final class MailerProxyInjector implements InjectorInterface
{
    use InjectorTrait;

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly BinderInterface $binder,
        private readonly MailerCollector $collector,
    ) {}

    public function createInjection(ReflectionClass $class, ?string $context = null): MailerInterface
    {
        /** @var MailerInterface $original */
        $original = $this->resolveUnderlying(
            $this->container,
            $this->binder,
            MailerInterface::class,
            self::nullMailer(),
        );

        return new TracingMailer($original, $this->collector);
    }

    private static function nullMailer(): MailerInterface
    {
        return new class implements MailerInterface {
            public function send(MessageInterface ...$message): void
            {
                // No upstream mailer bound — silently drop.
            }
        };
    }
}
