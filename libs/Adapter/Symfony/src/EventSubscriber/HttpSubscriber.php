<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\EventSubscriber;

use AppDevPanel\Api\Toolbar\ToolbarInjector;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\DebugServer\Broadcaster;
use AppDevPanel\Kernel\DebugServer\Connection;
use AppDevPanel\Kernel\StartupContext;
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
    private readonly Psr7Converter $psr7Converter;

    private bool $varDumperHandlerRegistered = false;

    public function __construct(
        private readonly Debugger $debugger,
        private readonly HttpSubscriberCollectors $collectors = new HttpSubscriberCollectors(),
        private readonly ?ToolbarInjector $toolbarInjector = null,
    ) {
        $this->psr7Converter = new Psr7Converter();
    }

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
        $psrRequest = $this->psr7Converter->convertRequest($symfonyRequest);

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
            $psrResponse = $this->psr7Converter->convertResponse($event->getResponse());
            $this->collectors->request->collectResponse($psrResponse);
        }

        $this->collectors->routerDataExtractor?->extract($event->getRequest());

        // Add debug ID header to the response
        $event->getResponse()->headers->set('X-Debug-Id', $this->debugger->getId());

        $this->injectToolbar($event);
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

    private function injectToolbar(ResponseEvent $event): void
    {
        if ($this->toolbarInjector === null || !$this->toolbarInjector->isEnabled()) {
            return;
        }

        $response = $event->getResponse();
        $contentType = $response->headers->get('Content-Type', '');

        if (!$this->toolbarInjector->isHtmlResponse($contentType)) {
            return;
        }

        $content = $response->getContent();
        if ($content === false || $content === '') {
            return;
        }

        $request = $event->getRequest();
        $backendUrl = $request->getSchemeAndHttpHost();

        $injected = $this->toolbarInjector->inject($content, $backendUrl, $this->debugger->getId());
        $response->setContent($injected);
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
        $broadcaster = new Broadcaster();
        $previousHandler = VarDumper::setHandler(static function (mixed $var, ?string $label = null) use (
            $collector,
            $broadcaster,
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

            // Broadcast for Live Feed
            try {
                $broadcaster->broadcast(
                    Connection::MESSAGE_TYPE_VAR_DUMPER,
                    \Yiisoft\VarDumper\VarDumper::create($var)->asJson(false),
                );
            } catch (\Throwable) {
            }
        });

        $this->varDumperHandlerRegistered = true;
    }
}
