<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Storage\StorageInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class Debugger
{
    private bool $skipCollect = false;
    private bool $active = false;
    private bool $shutdownRegistered = false;
    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly DebuggerIdGenerator $idGenerator,
        private readonly StorageInterface $target,
        /**
         * @var CollectorInterface[]
         */
        private readonly array $collectors,
        private array $ignoredRequests = [],
        private array $ignoredCommands = [],
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

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
            $this->logger->debug('Debugger: skipping ignored request', [
                'path' => $request->getUri()->getPath(),
            ]);
            $this->skipCollect = true;
            return;
        }

        if ($context->isCommand() && $this->isCommandIgnored($context->getCommandName())) {
            $this->logger->debug('Debugger: skipping ignored command', [
                'command' => $context->getCommandName(),
            ]);
            $this->skipCollect = true;
            return;
        }

        $this->idGenerator->reset();
        $id = $this->idGenerator->getId();
        $this->logger->debug('Debugger: startup', [
            'id' => $id,
            'collectors' => count($this->collectors),
        ]);

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
                $this->logger->debug('Debugger: flushing', [
                    'id' => $this->idGenerator->getId(),
                ]);
                $this->target->flush();
            }
        } finally {
            foreach ($this->collectors as $collector) {
                $collector->shutdown();
            }
            $this->active = false;
            $this->logger->debug('Debugger: shutdown complete');
        }
    }

    public function stop(): void
    {
        if (!$this->active) {
            return;
        }

        $this->logger->debug('Debugger: stopped without flush');
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
     * @param array $ignoredCommands Patterns for ignored command names (fnmatch syntax).
     */
    public function withIgnoredCommands(array $ignoredCommands): self
    {
        $new = clone $this;
        $new->ignoredCommands = $ignoredCommands;
        return $new;
    }
}
