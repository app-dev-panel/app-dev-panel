<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Storage\StorageInterface;
use Psr\Http\Message\ServerRequestInterface;

final class Debugger
{
    private bool $skipCollect = false;
    private bool $active = false;

    public function __construct(
        private readonly DebuggerIdGenerator $idGenerator,
        private readonly StorageInterface $target,
        /**
         * @var CollectorInterface[]
         */
        private readonly array $collectors,
        private array $ignoredRequests = [],
        private array $ignoredCommands = [],
    ) {
        register_shutdown_function([$this, 'shutdown']);
    }

    public function getId(): string
    {
        return $this->idGenerator->getId();
    }

    /**
     * Start debugging a web request.
     */
    public function startupWeb(?ServerRequestInterface $request = null): void
    {
        $this->active = true;
        $this->skipCollect = false;

        if ($request !== null && $this->isRequestIgnored($request)) {
            $this->skipCollect = true;
            return;
        }

        $this->doStartup();
    }

    /**
     * Start debugging a console command.
     */
    public function startupConsole(?string $commandName = null): void
    {
        $this->active = true;
        $this->skipCollect = false;

        if ($this->isCommandIgnored($commandName)) {
            $this->skipCollect = true;
            return;
        }

        $this->doStartup();
    }

    /**
     * Generic startup for backward compatibility.
     * Prefer startupWeb() or startupConsole() for framework-agnostic usage.
     */
    public function startup(object $event): void
    {
        $this->active = true;
        $this->skipCollect = false;

        if (method_exists($event, 'getRequest')) {
            $request = $event->getRequest();
            if ($request instanceof ServerRequestInterface && $this->isRequestIgnored($request)) {
                $this->skipCollect = true;
                return;
            }
        }

        if (property_exists($event, 'commandName')) {
            if ($this->isCommandIgnored($event->commandName)) {
                $this->skipCollect = true;
                return;
            }
        }

        $this->doStartup();
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

    private function doStartup(): void
    {
        $this->idGenerator->reset();
        foreach ($this->collectors as $collector) {
            $this->target->addCollector($collector);
            $collector->startup();
        }
    }

    private function isRequestIgnored(ServerRequestInterface $request): bool
    {
        if ($request->hasHeader('X-Debug-Ignore') && $request->getHeaderLine('X-Debug-Ignore') === 'true') {
            return true;
        }
        $path = $request->getUri()->getPath();
        foreach ($this->ignoredRequests as $pattern) {
            if (fnmatch($pattern, $path)) {
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
            if (fnmatch($pattern, $command)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array $ignoredRequests Patterns for ignored request URLs (fnmatch syntax).
     */
    public function withIgnoredRequests(array $ignoredRequests): self
    {
        $new = clone $this;
        $new->ignoredRequests = $ignoredRequests;
        return $new;
    }

    /**
     * @param array $ignoredCommands Patterns for ignored commands names (fnmatch syntax).
     */
    public function withIgnoredCommands(array $ignoredCommands): self
    {
        $new = clone $this;
        $new->ignoredCommands = $ignoredCommands;
        return $new;
    }
}
