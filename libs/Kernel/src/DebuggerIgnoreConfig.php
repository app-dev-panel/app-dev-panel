<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel;

use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Strings\WildcardPattern;

final readonly class DebuggerIgnoreConfig
{
    /**
     * @param array $requests Patterns for ignored request URLs.
     * @param array $commands Patterns for ignored command names.
     */
    public function __construct(
        public array $requests = [],
        public array $commands = [],
    ) {}

    public function isRequestIgnored(ServerRequestInterface $request): bool
    {
        if ($request->hasHeader('X-Debug-Ignore') && $request->getHeaderLine('X-Debug-Ignore') === 'true') {
            return true;
        }
        $path = $request->getUri()->getPath();
        foreach ($this->requests as $pattern) {
            if (new WildcardPattern($pattern)->match($path)) {
                return true;
            }
        }
        return false;
    }

    public function isCommandIgnored(?string $command): bool
    {
        if ($command === null || $command === '') {
            return true;
        }
        if (getenv('YII_DEBUG_IGNORE') === 'true') {
            return true;
        }
        foreach ($this->commands as $pattern) {
            if (new WildcardPattern($pattern)->match($command)) {
                return true;
            }
        }
        return false;
    }
}
