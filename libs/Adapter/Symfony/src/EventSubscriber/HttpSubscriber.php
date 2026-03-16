<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\EventSubscriber;

use AppDevPanel\Adapter\Symfony\Collector\SymfonyRequestCollector;
use AppDevPanel\Adapter\Symfony\Collector\SymfonyExceptionCollector;
use AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\StartupContext;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Maps Symfony HTTP kernel events to the ADP Debugger lifecycle.
 *
 * kernel.request  → Debugger::startup() + RequestCollector
 * kernel.response → RequestCollector (captures response)
 * kernel.exception → ExceptionCollector
 * kernel.terminate → Debugger::shutdown()
 */
final class HttpSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Debugger $debugger,
        private readonly ?SymfonyRequestCollector $requestCollector = null,
        private readonly ?WebAppInfoCollector $webAppInfoCollector = null,
        private readonly ?SymfonyExceptionCollector $exceptionCollector = null,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 1024],
            KernelEvents::RESPONSE => ['onKernelResponse', -1024],
            KernelEvents::EXCEPTION => ['onKernelException', 0],
            KernelEvents::TERMINATE => ['onKernelTerminate', -2048],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $symfonyRequest = $event->getRequest();

        $psr17Factory = new Psr17Factory();
        $psrRequest = (new ServerRequestCreator($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory))
            ->fromGlobals();

        // Override URI from the Symfony request to ensure accuracy
        $uri = $psr17Factory->createUri($symfonyRequest->getUri());
        $psrRequest = $psrRequest->withUri($uri)->withMethod($symfonyRequest->getMethod());

        foreach ($symfonyRequest->headers->all() as $name => $values) {
            foreach ($values as $value) {
                $psrRequest = $psrRequest->withAddedHeader($name, $value);
            }
        }

        $this->debugger->startup(StartupContext::forRequest($psrRequest));

        $this->webAppInfoCollector?->collect($event);
        $this->requestCollector?->collectRequest($symfonyRequest);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->webAppInfoCollector?->collect($event);
        $this->requestCollector?->collectResponse($event->getResponse());

        // Add debug ID header to the response
        $event->getResponse()->headers->set('X-Debug-Id', $this->debugger->getId());
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $this->exceptionCollector?->collect($event->getThrowable());
    }

    public function onKernelTerminate(TerminateEvent $event): void
    {
        $this->webAppInfoCollector?->collect($event);
        $this->debugger->shutdown();
    }
}
