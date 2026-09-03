<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Debug\Exception;

use InvalidArgumentException;

/**
 * Client-side input error. {@see \AppDevPanel\Api\Debug\Middleware\ResponseDataWrapper}
 * maps it to HTTP 400 (a plain {@see InvalidArgumentException} still maps to 500).
 */
final class BadRequestException extends InvalidArgumentException {}
