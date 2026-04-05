<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Ensures CORS headers are present on all ADP API responses.
 *
 * Runs at extreme priorities to guarantee headers are added even when
 * exceptions occur or other subscribers interfere. Handles OPTIONS
 * preflight requests directly without reaching the controller.
 */
final class CorsSubscriber implements EventSubscriberInterface
{
    private const CORS_HEADERS = [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, PATCH, OPTIONS',
        'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Debug-Token, X-Requested-With, X-Acp-Session',
        'Access-Control-Max-Age' => '86400',
    ];

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 4096],
            KernelEvents::RESPONSE => ['onKernelResponse', -4096],
            KernelEvents::EXCEPTION => ['onKernelException', 4096],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!self::isAdpApiPath($request->getPathInfo())) {
            return;
        }

        if ($request->getMethod() === 'OPTIONS') {
            $response = new Response('', 204, self::CORS_HEADERS);
            $event->setResponse($response);
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!self::isAdpApiPath($event->getRequest()->getPathInfo())) {
            return;
        }

        $response = $event->getResponse();
        foreach (self::CORS_HEADERS as $name => $value) {
            $response->headers->set($name, $value);
        }
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!self::isAdpApiPath($event->getRequest()->getPathInfo())) {
            return;
        }

        // Ensure the exception response (set by Symfony's error handler) gets CORS headers.
        // We also listen on kernel.response, but adding headers here covers the case where
        // the exception is converted to a response after this event.
        $response = $event->getResponse();
        if ($response !== null) {
            foreach (self::CORS_HEADERS as $name => $value) {
                $response->headers->set($name, $value);
            }
        }
    }

    private static function isAdpApiPath(string $path): bool
    {
        return str_starts_with($path, '/debug/api') || str_starts_with($path, '/inspect/api');
    }
}
