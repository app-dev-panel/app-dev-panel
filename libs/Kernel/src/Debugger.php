<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Storage\StorageInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class Debugger
{
    private bool $skipCollect = false;
    private bool $active = false;
    private bool $shutdownRegistered = false;
    private readonly LoggerInterface $logger;
    private DebuggerIgnoreConfig $ignoreConfig;

    public function __construct(
        private readonly DebuggerIdGenerator $idGenerator,
        private readonly StorageInterface $target,
        /**
         * @var CollectorInterface[]
         */
        private readonly array $collectors,
        ?DebuggerIgnoreConfig $ignoreConfig = null,
        ?LoggerInterface $logger = null,
    ) {
        $this->ignoreConfig = $ignoreConfig ?? new DebuggerIgnoreConfig();
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
        if ($request !== null && $this->ignoreConfig->isRequestIgnored($request)) {
            $this->logger->debug('Debugger: skipping ignored request', [
                'path' => $request->getUri()->getPath(),
            ]);
            $this->skipCollect = true;
            return;
        }

        if ($context->isCommand() && $this->ignoreConfig->isCommandIgnored($context->getCommandName())) {
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

        // Phase 1: shutdown collectors — they detach observers (stream wrappers,
        // error handlers, decorated services) but PRESERVE their buffers, so the
        // upcoming flush can still read collected data via getCollected() /
        // getSummary(). Without this order the storage flush itself would feed
        // the still-active stream wrappers and tail-pollute the entry.
        foreach ($this->collectors as $collector) {
            $collector->shutdown();
        }

        try {
            // Phase 2: flush — serialize collector data to storage.
            if (!$this->skipCollect) {
                $this->logger->debug('Debugger: flushing', [
                    'id' => $this->idGenerator->getId(),
                ]);
                $this->target->flush();
            }
        } finally {
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

    /**
     * @param array $ignoredRequests Patterns for ignored request URLs.
     */
    public function withIgnoredRequests(array $ignoredRequests): self
    {
        $new = clone $this;
        $new->ignoreConfig = new DebuggerIgnoreConfig($ignoredRequests, $this->ignoreConfig->commands);
        return $new;
    }

    /**
     * @param array $ignoredCommands Patterns for ignored commands names.
     */
    public function withIgnoredCommands(array $ignoredCommands): self
    {
        $new = clone $this;
        $new->ignoreConfig = new DebuggerIgnoreConfig($this->ignoreConfig->requests, $ignoredCommands);
        return $new;
    }
}
