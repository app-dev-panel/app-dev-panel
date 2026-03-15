<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Storage\StorageInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Strings\WildcardPattern;

final class Debugger
{
    private bool $skipCollect = false;
    private bool $active = false;
    private bool $shutdownRegistered = false;

    public function __construct(
        private readonly DebuggerIdGenerator $idGenerator,
        private readonly StorageInterface $target,
        /**
         * @var CollectorInterface[]
         */
        private readonly array $collectors,
        private array $ignoredRequests = [],
        private array $ignoredCommands = [],
    ) {}

    public function getId(): string
    {
        return $this->idGenerator->getId();
    }

    public function startup(StartupContext $context): void
    {
        $this->active = true;
        $this->skipCollect = false;

        if (!$this->shutdownRegistered) {
            register_shutdown_function([$this, 'shutdown']);
            $this->shutdownRegistered = true;
        }

        $request = $context->getRequest();
        if ($request !== null && $this->isRequestIgnored($request)) {
            $this->skipCollect = true;
            return;
        }

        if ($context->isCommand() && $this->isCommandIgnored($context->getCommandName())) {
            $this->skipCollect = true;
            return;
        }

        $this->idGenerator->reset();
        foreach ($this->collectors as $collector) {
            $this->target->addCollector($collector);
            $collector->startup();
        }
    }

    public function shutdown(): void
    {
        if (!$this->active) {
            return;
        }

        try {
            if (!$this->skipCollect) {
                $this->target->flush();
            }
        } finally {
            foreach ($this->collectors as $collector) {
                $collector->shutdown();
            }
            $this->active = false;
        }
    }

    public function stop(): void
    {
        if (!$this->active) {
            return;
        }

        foreach ($this->collectors as $collector) {
            $collector->shutdown();
        }
        $this->active = false;
    }

    private function isRequestIgnored(ServerRequestInterface $request): bool
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

    private function isCommandIgnored(?string $command): bool
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

    /**
     * @param array $ignoredRequests Patterns for ignored request URLs.
     *
     * @see WildcardPattern
     */
    public function withIgnoredRequests(array $ignoredRequests): self
    {
        $new = clone $this;
        $new->ignoredRequests = $ignoredRequests;
        return $new;
    }

    /**
     * @param array $ignoredCommands Patterns for ignored commands names.
     *
     * @see WildcardPattern
     */
    public function withIgnoredCommands(array $ignoredCommands): self
    {
        $new = clone $this;
        $new->ignoredCommands = $ignoredCommands;
        return $new;
    }
}
