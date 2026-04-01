<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii3\Proxy;

/**
 * Describes the type of proxy configuration for a decorated service.
 */
enum ServiceConfigType
{
    /** Service config is a callable factory. */
    case Callable;

    /** Service config is an associative array of method callbacks. */
    case MethodCallbacks;

    /** Service config is a sequential array [proxyClass, ...params]. */
    case ArrayDefinition;

    /** Service has no explicit proxy config. */
    case None;
}
