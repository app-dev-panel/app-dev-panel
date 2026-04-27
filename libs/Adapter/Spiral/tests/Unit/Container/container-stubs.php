<?php

declare(strict_types=1);

/**
 * Compatibility stubs for optional Spiral packages used by the autofeed injectors —
 * `spiral/mailer`, `spiral/queue`, `spiral/translator`, `spiral/views`. None of these
 * are pulled into the root `vendor/`. The runtime code defends against missing
 * classes via `interface_exists` guards in `AppDevPanelBootloader::boot()`.
 *
 * The unit tests `require_once` this file (via {@see ContainerStubsBootstrap}) so
 * they can construct realistic doubles. Each stub is conditionally declared, so
 * this is a no-op when the real packages are installed.
 *
 * @noinspection PhpUnused
 */

namespace Spiral\Mailer {
    if (!interface_exists(MessageInterface::class)) {
        interface MessageInterface
        {
            public function getSubject(): string;

            /** @return array<int, string>|string|null */
            public function getFrom();

            /** @return array<int, string> */
            public function getTo(): array;

            /** @return array<string, mixed> */
            public function getOptions(): array;
        }
    }

    if (!interface_exists(MailerInterface::class)) {
        interface MailerInterface
        {
            public function send(MessageInterface ...$message): void;
        }
    }
}

namespace Spiral\Queue {
    if (!interface_exists(OptionsInterface::class)) {
        interface OptionsInterface
        {
            public function getQueue(): ?string;
        }
    }

    if (!interface_exists(QueueInterface::class)) {
        interface QueueInterface
        {
            public function push(string $name, array|object $payload = [], ?OptionsInterface $options = null): string;
        }
    }
}

namespace Spiral\Translator\Catalogue {
    if (!interface_exists(CatalogueInterface::class)) {
        interface CatalogueInterface
        {
            public function getName(): string;
        }
    }
}

namespace Spiral\Translator {
    if (!interface_exists(TranslatorInterface::class)) {
        interface TranslatorInterface
        {
            public function trans(
                string $string,
                array $options = [],
                ?string $bundle = null,
                ?string $locale = null,
            ): string;

            public function setLocale(string $locale): self;

            public function getLocale(): string;

            public function getCatalogue(?string $locale = null): \Spiral\Translator\Catalogue\CatalogueInterface;
        }
    }
}

namespace Spiral\Views {
    if (!interface_exists(ViewInterface::class)) {
        interface ViewInterface
        {
            public function render(array $data = []): string;
        }
    }

    if (!interface_exists(ViewsInterface::class)) {
        interface ViewsInterface
        {
            public function render(string $path, array $data = []): string;

            public function get(string $path): ViewInterface;

            public function compile(string $path): void;

            public function reset(string $path): void;
        }
    }
}
