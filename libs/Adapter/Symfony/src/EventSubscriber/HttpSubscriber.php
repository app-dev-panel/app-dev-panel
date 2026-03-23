<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\EventSubscriber;

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
use Symfony\Component\VarDumper\VarDumper;

/**
 * Maps Symfony HTTP kernel events to the ADP Debugger lifecycle.
 *
 * kernel.request  -> Debugger::startup() + RequestCollector
 * kernel.response -> RequestCollector (captures response)
 * kernel.exception -> ExceptionCollector
 * kernel.terminate -> Debugger::shutdown()
 *
 * Uses Kernel's generic RequestCollector (PSR-7) and ExceptionCollector.
 * Symfony HttpFoundation objects are converted to PSR-7 via nyholm/psr7.
 */
final class HttpSubscriber implements EventSubscriberInterface
{
    private ?Psr17Factory $psr17Factory = null;

    private bool $varDumperHandlerRegistered = false;

    public function __construct(
        private readonly Debugger $debugger,
        private readonly HttpSubscriberCollectors $collectors = new HttpSubscriberCollectors(),
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

        if ($this->isAdpApiRequest($event->getRequest()->getPathInfo())) {
            return;
        }

        $symfonyRequest = $event->getRequest();
        $psrRequest = $this->convertSymfonyRequestToPsr7($symfonyRequest);

        $this->registerVarDumperHandler();

        $this->debugger->startup(StartupContext::forRequest($psrRequest));

        $this->collectors->webAppInfo?->markApplicationStarted();
        $this->collectors->webAppInfo?->markRequestStarted();
        $this->collectors->request?->collectRequest($psrRequest);
        $this->collectors->environment?->collectFromRequest($psrRequest);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if ($this->isAdpApiRequest($event->getRequest()->getPathInfo())) {
            return;
        }

        $this->collectors->webAppInfo?->markRequestFinished();

        if ($this->collectors->request !== null) {
            $psrResponse = $this->convertSymfonyResponseToPsr7($event->getResponse());
            $this->collectors->request->collectResponse($psrResponse);
        }

        $this->collectors->routerDataExtractor?->extract($event->getRequest());

        // Add debug ID header to the response
        $event->getResponse()->headers->set('X-Debug-Id', $this->debugger->getId());
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if ($this->isAdpApiRequest($event->getRequest()->getPathInfo())) {
            return;
        }

        $this->collectors->exception?->collect($event->getThrowable());
    }

    public function onKernelTerminate(TerminateEvent $event): void
    {
        if ($this->isAdpApiRequest($event->getRequest()->getPathInfo())) {
            return;
        }

        $this->collectors->webAppInfo?->markApplicationFinished();
        $this->debugger->shutdown();
    }

    private function isAdpApiRequest(string $path): bool
    {
        return str_starts_with($path, '/debug/api') || str_starts_with($path, '/inspect/api');
    }

    private function registerVarDumperHandler(): void
    {
        if ($this->varDumperHandlerRegistered || $this->collectors->varDumper === null) {
            return;
        }

        $collector = $this->collectors->varDumper;
        $previousHandler = VarDumper::setHandler(static function (mixed $var, ?string $label = null) use (
            $collector,
        ): void {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
            $line = '';
            foreach ($trace as $frame) {
                if (!(array_key_exists('file', $frame) && !str_contains($frame['file'], 'vendor/'))) {
                    continue;
                }

                $line = $frame['file'] . ':' . ($frame['line'] ?? 0);
                break;
            }

            $collector->collect($var, $label ?? $line);
        });

        $this->varDumperHandlerRegistered = true;
    }

    private function getPsr17Factory(): Psr17Factory
    {
        return $this->psr17Factory ??= new Psr17Factory();
    }

    private function convertSymfonyRequestToPsr7(\Symfony\Component\HttpFoundation\Request $symfonyRequest): \Psr\Http\Message\ServerRequestInterface
    {
        $psr17Factory = $this->getPsr17Factory();
        $psrRequest = new ServerRequestCreator(
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
        )->fromGlobals();

        // Override URI from the Symfony request to ensure accuracy
        $uri = $psr17Factory->createUri($symfonyRequest->getUri());
        $psrRequest = $psrRequest->withUri($uri)->withMethod($symfonyRequest->getMethod());

        foreach ($symfonyRequest->headers->all() as $name => $values) {
            $psrRequest = $psrRequest->withHeader($name, $values);
        }

        return $psrRequest;
    }

    private function convertSymfonyResponseToPsr7(\Symfony\Component\HttpFoundation\Response $symfonyResponse): \Psr\Http\Message\ResponseInterface
    {
        $psr17Factory = $this->getPsr17Factory();
        $psrResponse = $psr17Factory->createResponse($symfonyResponse->getStatusCode());

        foreach ($symfonyResponse->headers->all() as $name => $values) {
            $psrResponse = $psrResponse->withHeader($name, $values);
        }

        $content = $symfonyResponse->getContent();
        if ($content !== false) {
            $body = $psr17Factory->createStream($content);
            $psrResponse = $psrResponse->withBody($body);
        }

        return $psrResponse;
    }
}
