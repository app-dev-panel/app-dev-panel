<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel;

use Psr\Http\Message\ServerRequestInterface;

final class StartupContext
{
    private function __construct(
        private readonly ?ServerRequestInterface $request,
        private readonly ?string $commandName,
        private readonly bool $isCommand,
    ) {}

    public static function forRequest(ServerRequestInterface $request): self
    {
        return new self(request: $request, commandName: null, isCommand: false);
    }

    public static function forCommand(?string $commandName): self
    {
        return new self(request: null, commandName: $commandName, isCommand: true);
    }

    public static function generic(): self
    {
        return new self(request: null, commandName: null, isCommand: false);
    }

    public function getRequest(): ?ServerRequestInterface
    {
        return $this->request;
    }

    public function isCommand(): bool
    {
        return $this->isCommand;
    }

    public function getCommandName(): ?string
    {
        return $this->commandName;
    }
}
