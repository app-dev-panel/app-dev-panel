<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel;

use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Strings\WildcardPattern;

final class IgnoreConfig
{
    public function __construct(
        private array $ignoredRequests = [],
        private array $ignoredCommands = [],
    ) {}

    public function isRequestIgnored(ServerRequestInterface $request): bool
    {
        if ($request->hasHeader('X-Debug-Ignore') && $request->getHeaderLine('X-Debug-Ignore') === 'true') {
            return true;
        }
        $path = $request->getUri()->getPath();
        foreach ($this->ignoredRequests as $pattern) {
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
        foreach ($this->ignoredCommands as $pattern) {
            if (new WildcardPattern($pattern)->match($command)) {
                return true;
            }
        }
        return false;
    }

    public function withIgnoredRequests(array $ignoredRequests): self
    {
        return new self($ignoredRequests, $this->ignoredCommands);
    }

    public function withIgnoredCommands(array $ignoredCommands): self
    {
        return new self($this->ignoredRequests, $ignoredCommands);
    }
}
